<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;
use Spatie\Csp\Scheme;

/**
 * CSP for the authenticated admin panel (layouts/app.blade.php and
 * everything extending it). Requires 'unsafe-eval' because Livewire's
 * bundled Alpine.js evaluates directive expressions (x-data, wire:model,
 * @click, ...) via `new Function`; Livewire's csp_safe mode avoids that but
 * broke nested-component bindings and @script blocks when tried here (see
 * MonitorPreset's docblock for the CSP-safe kiosk policy, which doesn't
 * need Livewire/Alpine at all).
 */
class AdminPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::SCRIPT, [Keyword::SELF, Keyword::UNSAFE_EVAL])
            // Alpine.js injects a small <style> tag itself (for x-cloak); it has
            // no way to carry a nonce, so style-src needs unsafe-inline too.
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            ->add(Directive::STYLE_ATTR, Keyword::UNSAFE_INLINE)
            ->add(Directive::FONT, Keyword::SELF)
            // Bootstrap's compiled CSS references its form-validation icons as
            // data: SVG background-images.
            ->add(Directive::IMG, [Keyword::SELF, Scheme::DATA])
            ->add(Directive::MEDIA, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FRAME, Keyword::NONE)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->addNonce(Directive::SCRIPT);
    }
}
