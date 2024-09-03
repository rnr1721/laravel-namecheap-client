<x-filament::page>
    <form wire:submit.prevent="save">
        <h2 class="text-lg font-medium mb-4">{{ __('Registrant Contact Information') }}</h2>
        
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            {{ __('Save Changes') }}
        </x-filament::button>
    </form>
</x-filament::page>