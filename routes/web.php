<?php

use App\Filament\Pages\EditDomainContacts;
use App\Filament\Pages\EditDomainDns;
use App\Filament\Pages\EditDomainNameservers;
use App\Filament\Pages\PurchaseDomain;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/purchase-domain/{accountId}', PurchaseDomain::class)->name('filament.pages.purchase-domain');

Route::get('/admin/edit-domain-dns/{accountId}/{domain}', EditDomainDns::class)->name('filament.pages.edit-domain-dns');

Route::get('/admin/edit-domain-contacts/{accountId}/{domain}', EditDomainContacts::class)->name('filament.pages.edit-domain-contacts');
