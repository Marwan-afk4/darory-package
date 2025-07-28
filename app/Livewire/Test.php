<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\Attributes\On;


class Test extends Component
{

    public ?Supplier $supplier;

    #[On('supplierSelected')]
    public function supplier(Supplier $supplier)
    {
        $this->supplier = $supplier;
        
    }
    

    public function render()
    {
        return view('livewire.test');
    }
}
