<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Scheme;

/**
 * CSP for the kiosk display (showevents.blade.php / slider.ts), served by
 * MonitorController::display(). Not applied to the authenticated admin
 * panel, which relies on Livewire's bundled Alpine.js and would need
 * `unsafe-eval` (or the Alpine CSP build) plus nonces on several more
 * inline <script> blocks before it could run under a strict policy.
 */
class MonitorPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->add(Directive::STYLE, Keyword::SELF)
            // The page has several `style="display:none"` attributes; CSP has no
            // nonce mechanism for style attributes, only for <style>/<link> tags.
            ->add(Directive::STYLE_ATTR, Keyword::UNSAFE_INLINE)
            ->add(Directive::FONT, Keyword::SELF)
            // Bootstrap (imported into slider.scss too) references its form-validation
            // icons as data: SVG background-images.
            ->add(Directive::IMG, [Keyword::SELF, Scheme::DATA, 'https://openweathermap.org'])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::CONNECT, array_filter([Keyword::SELF, $this->reverbOrigin()]))
            // slider.ts spins up its scheduler/video workers from blob: URLs.
            ->add(Directive::WORKER, [Keyword::SELF, 'blob:'])
            ->add(Directive::FRAME, Keyword::NONE)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->addNonce(Directive::SCRIPT);
    }

    private function reverbOrigin(): ?string
    {
        $host = config('broadcasting.connections.reverb.options.host');

        if (! $host) {
            return null;
        }

        $scheme = config('broadcasting.connections.reverb.options.scheme', 'https') === 'https' ? 'wss' : 'ws';
        $port = config('broadcasting.connections.reverb.options.port');

        return "{$scheme}://{$host}".($port ? ":{$port}" : '');
    }
}
