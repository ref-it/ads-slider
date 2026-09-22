<?php

namespace App\Http\Controllers;

use App\Events\SecurityAuditEvent;
use App\Models\Monitor;
use App\Models\Picture;
use App\Models\PictureSource;
use App\Providers\ItemUpdated;
use App\Traits\UploadTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PictureController extends Controller
{
    use UploadTrait;

    public function __construct()
    {
        $this->authorizeResource(Picture::class, 'pic');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Picture $pic): BinaryFileResponse
    {
        if (Auth::check()) {
            $this->authorize('view', $pic);
        } else {
            // Enforce monitor token realm scoping
            $token = $request->header('X-API-TOKEN') ?? $request->query('api_token');
            $monitor = Monitor::where('api_token', $token)->first();

            if (! $monitor || $monitor->realm_id !== $pic->realm_id) {
                abort(403, 'Unauthorized media access across tenants.');
            }
        }

        $targetWidth = (int) $request->input('width');
        $targetHeight = (int) $request->input('height');

        $source = $pic->getBestSource($targetWidth, $targetHeight);
        $path = $source ? $source->path : '';

        return response()->file(Storage::disk('public')->path(config('ads.pic_basepath').$path));
    }

    public function storeSource(Request $request, Picture $pic): RedirectResponse
    {
        $this->authorize('update', $pic);

        $request->validate([
            'upload' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->has('upload')) {
            $image = $request->file('upload');
            $name = Str::slug($pic->name).'_'.rand(0, 32000000).'.'.$image->getClientOriginalExtension();
            $folder = config('ads.pic_basepath');

            $this->uploadOne($image, $folder, 'public', $name);

            $size = getimagesize($image->getPathname());

            $pic->sources()->create([
                'path' => $name,
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'clock_location' => $pic->clock_location,
            ]);

            flash()->success('Source added');
        }

        return redirect()->back();
    }

    public function updateSource(Request $request, PictureSource $source): JsonResponse
    {
        $this->authorize('update', $source->picture);

        $validated = $request->validate([
            'clock_location' => 'required|integer|min:0|max:9',
        ]);

        $source->update($validated);

        event(new ItemUpdated('ps', (object) ['id' => $source->picture->id, 'realm_id' => $source->picture->realm_id]));

        return response()->json(['status' => 'success']);
    }

    public function destroySource(PictureSource $source): RedirectResponse
    {
        $this->authorize('update', $source->picture);
        if ($source->picture->sources()->count() <= 1) {
            flash()->error(__('Cannot delete the last source. Delete the entire picture configuration instead'));

            return redirect()->back();
        }
        $filePath = config('ads.pic_basepath').$source->path;
        $picture = $source->picture;
        DB::transaction(function () use ($source, $filePath) {
            $source->delete();
            Storage::disk('public')->delete($filePath);
        });
        event(new SecurityAuditEvent(
            action: 'picture.source_deleted',
            description: "Picture source (ID: {$source->id}, Path: {$source->path}) deleted for Picture ID: {$picture->id}",
            userId: Auth::id(),
            realmId: $picture->realm_id,
            context: ['picture_id' => $picture->id, 'source_id' => $source->id]
        ));
        event(new ItemUpdated('ps', (object) ['id' => $picture->id, 'realm_id' => $picture->realm_id]));
        flash()->success('Source deleted');

        return redirect()->back();
    }
}
