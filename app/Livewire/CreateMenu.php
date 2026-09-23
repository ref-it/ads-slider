<?php

namespace App\Livewire;

use App\Livewire\Forms\MenuForm;
use App\Models\Menu;
use App\Models\Monitor;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreateMenu extends Component
{
    public MenuForm $form;

    public function mount(): void
    {
        $this->form->addProduct();
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
        $this->authorize('create', Menu::class);
        $this->form->save();
        flash()->success('Menu created');

        return $this->redirectRoute('menus.index');
    }

    public function render()
    {
        return view('livewire.create-menu', [
            'monitors' => Monitor::ofRealm(Auth::user()->realm_id)->orderBy('name')->get(),
        ]);
    }
}
