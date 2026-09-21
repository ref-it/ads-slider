<?php

namespace App\Http\Controllers\Auth;

use App\Events\SecurityAuditEvent;
use App\Http\Controllers\Controller;
use App\Models\Realm;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OidcController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('backchannelLogout');
    }

    /**
     * Redirect the user to the configured OIDC provider.
     */
    public function redirect(): RedirectResponse
    {
        $this->ensureEnabled();

        return Socialite::driver('openidconnect')->redirect();
    }

    /**
     * Handle the callback from the OIDC provider.
     */
    public function callback(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        try {
            /** @var SocialiteUser $oidcUser */
            $oidcUser = Socialite::driver('openidconnect')->user();
        } catch (\Throwable $e) {
            Log::warning('OIDC callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'email' => __('Login via the identity provider failed. Please try again or use your password.'),
            ]);
        }

        $email = $oidcUser->getEmail();
        $sub = $oidcUser->getId();

        if (! $email || ! $sub) {
            return redirect()->route('login')->withErrors([
                'email' => __('The identity provider did not return the required account information.'),
            ]);
        }

        $groups = $this->extractGroups($oidcUser);
        $matchedRealmIds = $this->matchedRealmIds($groups);

        if ($matchedRealmIds->isEmpty()) {
            event(new SecurityAuditEvent(
                action: 'auth.oidc_denied_group',
                description: "OIDC login denied for {$email}: no realm's required group is present",
                context: ['email' => $email, 'oidc_sub' => $sub, 'groups' => $groups],
            ));

            return redirect()->route('login')->withErrors([
                'email' => __('Your account is not a member of the group required to access this application.'),
            ]);
        }

        $user = $this->findOrProvisionUser($sub, $email, $oidcUser, $matchedRealmIds);

        if ($user === null) {
            event(new SecurityAuditEvent(
                action: 'auth.oidc_denied_conflict',
                description: "OIDC login denied for {$email}: account already linked to a different subject",
                context: ['email' => $email, 'oidc_sub' => $sub],
            ));

            return redirect()->route('login')->withErrors([
                'email' => __('This account cannot be signed in via the identity provider.'),
            ]);
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->realms()->sync($matchedRealmIds);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $this->rememberSession($oidcUser, $request, $user);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Back-channel logout endpoint, called server-to-server by the IdP when a
     * session ends elsewhere. Not a browser request: no session/CSRF present.
     */
    public function backchannelLogout(Request $request)
    {
        try {
            $claims = Socialite::driver('openidconnect')->verifyLogoutToken($request->input('logout_token'));
        } catch (\InvalidArgumentException $e) {
            return response('', 400);
        }

        if ($sid = $claims['sid'] ?? null) {
            $rows = DB::table('oidc_sessions')->where('sid', $sid)->get();
        } else {
            $user = User::where('oidc_sub', $claims['sub'] ?? null)->first();
            $rows = $user ? DB::table('oidc_sessions')->where('user_id', $user->id)->get() : collect();
        }

        $handler = app('session')->getHandler();
        foreach ($rows as $row) {
            $handler->destroy($row->laravel_session_id);
        }

        DB::table('oidc_sessions')->whereIn('sid', $rows->pluck('sid'))->delete();

        return response('', 200);
    }

    private function ensureEnabled(): void
    {
        if (! config('services.openidconnect.enabled')) {
            throw new HttpException(404);
        }
    }

    /**
     * Extract the group/role claim (configurable dot-notation key) as a flat array.
     */
    private function extractGroups(SocialiteUser $oidcUser): array
    {
        $claimKey = config('services.openidconnect.group_claim', 'groups');
        $claimValue = Arr::get($oidcUser->getRaw(), $claimKey);

        return match (true) {
            is_array($claimValue) => $claimValue,
            is_string($claimValue) => preg_split('/[\s,]+/', $claimValue, -1, PREG_SPLIT_NO_EMPTY),
            default => [],
        };
    }

    /**
     * Realms whose configured required group is present in the user's groups.
     */
    private function matchedRealmIds(array $groups): \Illuminate\Support\Collection
    {
        return Realm::query()
            ->whereNotNull('oidc_required_group')
            ->get()
            ->filter(fn (Realm $realm) => in_array($realm->oidc_required_group, $groups, true))
            ->pluck('id');
    }

    /**
     * Find a user by oidc_sub, link an existing email match, or JIT-provision a new one.
     * Returns null if the email belongs to an account already linked to a different subject.
     */
    private function findOrProvisionUser(string $sub, string $email, SocialiteUser $oidcUser, \Illuminate\Support\Collection $matchedRealmIds): ?User
    {
        $user = User::where('oidc_sub', $sub)->first();

        if ($user) {
            return $user;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->oidc_sub !== null) {
                return null;
            }

            $user->oidc_sub = $sub;
            $user->save();

            event(new SecurityAuditEvent(
                action: 'auth.oidc_account_linked',
                description: "Existing account linked to OIDC subject: {$email}",
                userId: $user->id,
                realmId: $user->realm_id,
                context: ['oidc_sub' => $sub],
            ));

            return $user;
        }

        $user = User::create([
            'name' => $oidcUser->getName() ?: $email,
            'email' => $email,
            'password' => null,
            'realm_id' => $matchedRealmIds->min(),
            'user_type' => 'member',
            'oidc_sub' => $sub,
        ]);

        event(new SecurityAuditEvent(
            action: 'auth.oidc_account_created',
            description: "New user auto-provisioned via OIDC: {$email}",
            userId: $user->id,
            realmId: $user->realm_id,
            context: ['oidc_sub' => $sub],
        ));

        return $user;
    }

    /**
     * Map the IdP session (sid claim) to this Laravel session, for back-channel logout.
     */
    private function rememberSession(SocialiteUser $oidcUser, Request $request, User $user): void
    {
        $sid = Arr::get($oidcUser->getRaw(), 'sid');

        if (! $sid) {
            return;
        }

        DB::table('oidc_sessions')->updateOrInsert(['sid' => $sid], [
            'laravel_session_id' => $request->session()->getId(),
            'user_id' => $user->id,
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }
}
