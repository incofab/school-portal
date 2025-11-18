<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Managers as Web;

Route::get('/dummy', function () {
    dd('dskmsdsf');
}); 

Route::get('/', [Web\ManagerController::class, 'dashboard'])
    ->name('dashboard');

Route::get('institutions/create/{institutionGroup?}', [Web\Institutions\InstitutionRegistrationController::class, 'create'])
    ->name('institutions.create');
Route::post('institutions/store', [Web\Institutions\InstitutionRegistrationController::class, 'store'])
    ->name('institutions.store');
Route::get('/institutions', Web\Institutions\ListInstitutionsController::class)
    ->name('institutions.index');
Route::delete('/institutions/{institution}/destroy', [Web\Institutions\InstitutionManagementController::class, 'destroy'])
    ->name('institutions.destroy');
Route::get('/institutions/{institution}/show', [Web\Institutions\InstitutionManagementController::class, 'show'])
    ->name('institutions.show');
Route::post('/institutions/{institution}/update-status', [Web\Institutions\InstitutionManagementController::class, 'updateStatus'])
    ->name('institutions.update.status');

Route::get('/institution-groupss/{institutionGroup}/invoice/{academicSession}/{term}', [Web\InstitutionGroups\InstitutionGroupsController::class, 'generateInvoice'])
    ->name('institution-groups.invoice.generate');

Route::get('/institution-groups/search', [Web\InstitutionGroups\InstitutionGroupsController::class, 'search'])
    ->name('institution-groups.search');
Route::resource('institution-groups', Web\InstitutionGroups\InstitutionGroupsController::class)
    ->except('show');
Route::post('/institution-groups/{institution_group}/upload-photo', [Web\InstitutionGroups\InstitutionGroupsController::class, 'uploadBanner'])
  ->name('institution-groups.upload-banner');
Route::post('/institution-groups/{institution_group}/update-status', [Web\InstitutionGroups\InstitutionGroupsController::class, 'updateStatus'])
  ->name('institution-groups.update.status');

Route::get('/registration-requests/search', [Web\RegistrationRequests\RegistrationRequestsController::class, 'search'])
    ->name('registration-requests.search');
Route::get('/registration-requests', [Web\RegistrationRequests\RegistrationRequestsController::class, 'index'])
    ->name('registration-requests.index');
Route::post('/registration-requests/create-institution-group/{registrationRequest}', [Web\RegistrationRequests\RegistrationRequestsController::class, 'createInstitutionGroup'])
    ->name('registration-requests.institution-groups.store');
Route::post('/registration-requests/create-institution/{institutionGroup}/{registrationRequest}', [Web\RegistrationRequests\RegistrationRequestsController::class, 'createInstitution'])
    ->name('registration-requests.institutions.store');
Route::delete('/registration-requests/{registrationRequest}', [Web\RegistrationRequests\RegistrationRequestsController::class, 'destroy'])
    ->name('registration-requests.destroy');

Route::get('partner-registrations', [Web\PartnerRequests\PartnerRegistrationRequestsController::class, 'index'])->name('partner-registration-requests.index');
Route::post('/partner-registrations/{partnerRegistrationRequest}/onboard', [Web\PartnerRequests\PartnerRegistrationRequestsController::class, 'onboardPartner'])
->name('partner-registration-requests.onboard');
Route::delete('/partner-registrations/{partnerRegistrationRequest}', [Web\PartnerRequests\PartnerRegistrationRequestsController::class, 'destroy'])
->name('partner-registration-requests.destroy');


//Admin section
Route::group(['middleware' => 'admin'], function () {
    /*
    Route::get('/generate-pin', [Web\GeneratePinController::class, 'create'])
        ->name('generate-pin.create');
    Route::post('/generate-pin', [Web\GeneratePinController::class, 'store'])
        ->name('generate-pin.store');

    Route::get('/pins/{pinGenerator?}', [Web\PinController::class, 'index'])
        ->name('pins.index');
    Route::get('/pin-generators', [Web\PinGeneratorController::class, 'index'])
        ->name('pin-generators.index');
    */
    Route::get('/index', [Web\ManagerController::class, 'index'])
        ->name('index');
    Route::get('/create', [Web\ManagerController::class, 'create'])
        ->name('create');
    Route::post('/store', [Web\ManagerController::class, 'store'])
        ->name('store');
    Route::post('/update/{user}', [Web\ManagerController::class, 'update'])
        ->name('update');
    Route::delete('/destroy/{user}', [Web\ManagerController::class, 'destroy'])
        ->name('destroy');
});

Route::post('funding/record-debt', [Web\Fundings\FundingController::class, 'recordDebt'])->name('funding.record-debt');
Route::resource('funding', Web\Fundings\FundingController::class);
Route::resource('billings', Web\Billings\BillingsController::class);

//== BANK ACCOUNT DETAILS
Route::resource('/bank-accounts', Web\BankAccounts\BankAccountController::class);

//== COMMISSIONS
Route::resource('/commissions', Web\Commissions\CommissionController::class);

//== WITHDRAWALS
Route::resource('/withdrawals', Web\Withdrawals\WithdrawalController::class);