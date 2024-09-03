<x-filament::page>
    <x-filament::card>
        <h1 class="text-xl font-bold">{{ _('Account Details') }}</h1><br>
        <x-filament::card>
            <h2 class="text-xl font-semibold">Account</h2>
            <div>
                <p><strong>{{ _('API User:') }}</strong> {{ $this->record->username }}</p>
                <p><strong>{{ _('API Key:') }}</strong> {{ $this->record->api_key }}</p>
                <p><strong>{{ _('Email:') }}</strong> {{ $this->record->email }}</p>
            </div>
        </x-filament::card><br>

        @if ($this->apiStatusAccounts === 'success' && !empty($this->balanceData))
        <x-filament::card>
            <h2 class="text-xl font-semibold">{{ _('Balance Information') }}</h2>
            <div>
                <p><strong>{{ _('Available Balance:') }}</strong> {{ $this->balanceData['_Currency'] ?? '' }} {{ $this->balanceData['_AvailableBalance'] ?? '' }}</p>
                <p><strong>{{ _('Account Balance:') }}</strong> {{ $this->balanceData['_Currency'] ?? '' }} {{ $this->balanceData['_AccountBalance'] ?? '' }}</p>
                <p><strong>{{ _('Earned Amount:') }}</strong> {{ $this->balanceData['_Currency'] ?? '' }} {{ $this->balanceData['_EarnedAmount'] ?? '' }}</p>
                <p><strong>{{ _('Withdrawable Amount:') }}</strong> {{ $this->balanceData['_Currency'] ?? '' }} {{ $this->balanceData['_WithdrawableAmount'] ?? '' }}</p>
                <p><strong>{{ _('Funds Required for Auto Renew:') }}</strong> {{ $this->balanceData['_Currency'] ?? '' }} {{ $this->balanceData['_FundsRequiredForAutoRenew'] ?? '' }}</p>
            </div>
        </x-filament::card><br>
        @elseif ($this->apiStatusAccounts === 'failed')
        <x-filament::card>
            <h2 class="text-xl font-semibold">{{ _('Errors') }}</h2>
            <div>
                <p><strong>{{ _('Error Message:') }}</strong> {{ $this->balanceData['Errors']['Error']['__text'] ?? 'Unknown error' }}</p>
                <p><strong>{{ _('Error Number:') }}</strong> {{ $this->balanceData['Errors']['Error']['_Number'] ?? 'N/A' }}</p>
            </div>
        </x-filament::card>
        @endif

        <x-filament::button>
            <a href="{{ route('filament.pages.purchase-domain', ['accountId' => $this->record->id]) }}">
                {{ _('Purchase Domain for this Account') }}
            </a>
        </x-filament::button>
    </x-filament::card>

    <x-filament::card>
        <h1 class="text-xl font-bold mb-4">{{ _('Domains List') }}</h1>

        <div class="mb-4">
            <form wire:submit.prevent="search">
                <div class="flex">
                    <x-filament::input
                        type="text"
                        wire:model="searchQuery"
                        placeholder="Search domains..."
                        class="flex-grow" />
                    <x-filament::button type="submit" class="ml-2">
                        {{ _('Search') }}
                    </x-filament::button>
                </div>
            </form>
        </div>

        @if(empty($this->domains))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">{{ _('No domains found.') }}</strong>
            <span class="block sm:inline">{{ _('Try adjusting your search criteria.') }}</span>
        </div>
        @else
        <div class="overflow-x-auto w-full">
            <table class="w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _('Domain') }}</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _('Created') }}</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _('Expires') }}</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _('Status') }}</th>
                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ _('Control') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($this->domains as $domain)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <a href="{{ route('filament.pages.edit-domain-contacts', ['accountId' => $this->record->id, 'domain' => $domain['_Name']]) }}">
                                {{ $domain['_Name'] ?? '' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $domain['_Created'] ?? '' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $domain['_Expires'] ?? '' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if ($domain['_IsExpired'] === 'true')
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                {{ _('Expired') }}
                            </span>
                            @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                {{ _('Active') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a style="color:red;text-decoration:underline" href="{{ route('filament.pages.edit-domain-dns', ['accountId' => $this->record->id, 'domain' => $domain['_Name']]) }}" title="Настройки DNS">
                                {{ _('DNS') }}
                            </a> | 
                            <a style="color:red;text-decoration:underline" href="{{ route('filament.pages.edit-domain-contacts', ['accountId' => $this->record->id, 'domain' => $domain['_Name']]) }}">
                                {{ _('Contacts') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        @if($this->paging['TotalItems'] > 0)
        <div class="mt-4 flex justify-between items-center">
            <x-filament::button wire:click="previousPage" :disabled="$this->paging['CurrentPage'] <= 1">
                {{ _('Previous') }}
            </x-filament::button>
            <span class="text-sm text-gray-700">
                {{ _('Page') }} {{ $this->paging['CurrentPage'] }} {{ _('of') }} {{ ceil($this->paging['TotalItems'] / $this->paging['PageSize']) }}
                (Total Items: {{ $this->paging['TotalItems'] }})
            </span>
            <x-filament::button wire:click="nextPage" :disabled="$this->paging['CurrentPage'] >= ceil($this->paging['TotalItems'] / $this->paging['PageSize'])">
                {{ _('Next') }}
            </x-filament::button>
        </div>
        @endif
    </x-filament::card>

</x-filament::page>