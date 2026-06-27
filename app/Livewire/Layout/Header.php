<?php

namespace App\Livewire\Layout;

use Livewire\Component;

class Header extends Component
{
    public bool $mobileMenuOpen = false;

    public function toggleMobileMenu(): void
    {
        $this->mobileMenuOpen = ! $this->mobileMenuOpen;
    }

    public function render()
    {
        return view('livewire.layout.header');
    }
}
