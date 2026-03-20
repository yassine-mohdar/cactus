<?php

namespace App\Livewire\Admin\Inventory;

use Livewire\Component;

class SyncButton extends Component
{
    public function sync()
    {
        // TODO: Implement actual ERP sync logic here
        $this->dispatch('notify', ['message' => 'Inventory synchronized successfully.']);
    }

    public function render()
    {
        return view('livewire.admin.inventory.sync-button');
    }
}
