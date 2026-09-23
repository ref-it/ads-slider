<?php

namespace App\Livewire;

use App\Events\SecurityAuditEvent;
use App\Livewire\Forms\MenuForm;
use App\Models\Menu;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class EditMenu extends Component
{
    public MenuForm $form;

    public function mount(Menu $menu): void
    {
        $this->authorize('update', $menu);
        $this->form->setMenu($menu);
    }

    public function addProduct(): void
    {
        $this->form->addProduct();
    }

    public function removeProduct(int $index): void
    {
        $this->form->removeProduct($index);
    }

    public function save()
    {
        $this->authorize('update', $this->form->menu);
        $this->form->save();
        flash()->success('Menu updated');

        return $this->redirectRoute('menus.index');
    }

    public function deleteMenu()
    {
        $this->authorize('delete', $this->form->menu);

        $menu = $this->form->menu;
        $menu->events()->detach();
        $menu->monitors()->detach();
        $menu->templates()->detach();
        $menu->delete();
        Storage::disk('public')->delete(config('ads.menu_basepath').$menu->path);

        event(new SecurityAuditEvent(
            action: 'menu.deleted',
            description: "Menu '{$menu->name}' (ID: {$menu->id}) deleted by user ID: ".Auth::id(),
            userId: Auth::id(),
            realmId: $menu->realm_id,
            context: ['menu_id' => $menu->id, 'menu_name' => $menu->name]
        ));

        flash()->success('Menu deleted');

        return $this->redirectRoute('menus.index');
    }

    public function render()
    {
        return view('livewire.edit-menu', [
            'monitors' => Monitor::ofRealm(Auth::user()->realm_id)->orderBy('name')->get(),
        ]);
    }
}
