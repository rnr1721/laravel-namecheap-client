<x-filament-panels::page>
    <form wire:submit.prevent="saveDnsRecords">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            {{ _('Save DNS Records') }}
        </x-filament::button>
        <x-filament::button
            tag="a"
            href="{{ url('/admin/namecheap-accounts/' . $this->accountId) }}"
            color="secondary">
            {{ _('Back to Account') }}
        </x-filament::button>
    </form>
</x-filament-panels::page>
