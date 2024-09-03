<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit.prevent="checkDomainAvailability">
            <div class="flex items-center space-x-4">
                <x-filament::input
                    wire:model="domainName"
                    placeholder="Enter domain name"
                    required
                />
                <x-filament::button type="submit">
                    {{ _('Check Availability') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    @if ($domainStatus)
        <x-filament::card class="mt-4">
            @if ($domainStatus === 'available')
                <p class="text-green-600 font-semibold">{{ $domainMessage }}</p>
                @if ($premiumInfo)
                    <div class="mt-4">
                        <h4 class="font-bold text-lg">{{ _('Premium Domain Information:') }}</h4>
                        <ul class="list-disc list-inside ml-6 mt-2">
                            <li>{{ _('Registration Price:') }} ${{ $premiumInfo['registrationPrice'] }}</li>
                            <li>{{ _('Renewal Price:') }} ${{ $premiumInfo['renewalPrice'] }}</li>
                            <li>{{ _('Restore Price:') }} ${{ $premiumInfo['restorePrice'] }}</li>
                            <li>{{ _('Transfer Price:') }} ${{ $premiumInfo['transferPrice'] }}</li>
                        </ul>
                    </div>
                @endif
                <form wire:submit.prevent="purchaseDomain" class="mt-4">
                    {{ $this->form }}
                    <br>
                    <x-filament::button type="submit" class="mt-4">
                    {{ _('Purchase Domain') }}
                    </x-filament::button>
                </form>
            @elseif ($domainStatus === 'unavailable')
                <p class="text-red-600 font-semibold">{{ $domainMessage }}</p>
            @else
                <p class="text-yellow-600 font-semibold">{{ $domainMessage }}</p>
            @endif
        </x-filament::card>
    @endif
</x-filament-panels::page>
