<?php

namespace App\Livewire\Forms;

use App\Livewire\Traits\ErrorBanner;
use App\Models\Menu;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MenuForm extends Form
{
    use ErrorBanner;

    public ?Menu $menu = null;

    #[Validate('required|max:191')]
    public $name = '';

    #[Validate('nullable|max:60')]
    public $icon = '';

    #[Validate('required|max:5')]
    public $currency = '€';

    #[Validate('nullable|max:1000')]
    public $description = '';

    #[Validate([
        'products' => 'required|array|min:1',
        'products.*.name' => 'required|max:191',
        'products.*.price' => 'required|max:20',
        'products.*.size' => 'nullable|max:20',
        'products.*.special' => 'boolean',
        'products.*.disabled' => 'boolean',
        'products.*.price2' => 'nullable|max:20',
        'products.*.size2' => 'nullable|max:20',
    ])]
    public $products = [];

    public $monitors = [];

    public function setMenu(Menu $menu): void
    {
        $this->menu = $menu;
        $this->name = $menu->name;
        $this->description = $menu->description ?? '';
        $this->monitors = $menu->monitors->pluck('id')->toArray();

        $content = $menu->json_content ?? [];
        $this->icon = $content['icon'] ?? '';
        $this->currency = $content['currency'] ?? '€';
        $this->products = array_map(fn ($p) => [
            'name' => $p['name'] ?? '',
            'price' => $p['price'] ?? '',
            'size' => $p['size'] ?? '',
            'special' => (bool) ($p['special'] ?? false),
            'disabled' => (bool) ($p['disabled'] ?? false),
            'price2' => $p['price2'] ?? '',
            'size2' => $p['size2'] ?? '',
        ], $content['products'] ?? []);
    }

    public function addProduct(): void
    {
        $this->products[] = [
            'name' => '',
            'price' => '',
            'size' => '',
            'special' => false,
            'disabled' => false,
            'price2' => '',
            'size2' => '',
        ];
    }

    public function removeProduct(int $index): void
    {
        unset($this->products[$index]);
        $this->products = array_values($this->products);
    }

    public function save(): Menu
    {
        $this->validate();

        $isNew = ! $this->menu;
        if ($isNew) {
            $this->menu = new Menu;
            $this->menu->realm_id = Auth::user()->realm_id;
            $this->menu->path = Str::slug($this->name).'_'.rand(0, 32000000).'.json';
        }

        $this->menu->name = $this->name;
        $this->menu->description = $this->description ?: null;
        $this->menu->user_id = Auth::id();

        $content = [
            'category_name' => $this->name,
            'icon' => $this->icon,
            'currency' => $this->currency,
            'products' => array_map(function ($p) {
                return array_filter([
                    'name' => $p['name'],
                    'price' => $p['price'],
                    'size' => $p['size'],
                    'special' => ((bool) ($p['special'] ?? false)) ?: null,
                    'disabled' => (bool) ($p['disabled'] ?? false),
                    'price2' => ($p['price2'] ?? '') !== '' ? $p['price2'] : null,
                    'size2' => ($p['size2'] ?? '') !== '' ? $p['size2'] : null,
                ], fn ($v) => ! is_null($v));
            }, $this->products),
        ];

        Storage::disk('public')->put(
            config('ads.menu_basepath').$this->menu->path,
            json_encode($content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        if (! $isNew) {
            // Force the Menu's `updated` observer event to fire: the file
            // content changed but no DB column did, so a plain save()
            // wouldn't detect a change on its own.
            $this->menu->updateTimestamps();
        }
        $this->menu->save();

        $validMonitorIds = Monitor::ofRealm($this->menu->realm_id)
            ->whereIn('id', (array) $this->monitors)
            ->pluck('id');
        $this->menu->monitors()->sync($validMonitorIds);

        Log::channel('crud')->info($isNew ? 'Menu created' : 'Menu updated', [
            'menu' => $this->menu,
            'user' => Auth::id(),
        ]);

        return $this->menu;
    }
}
