<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Managers as Web;

Route::get('/dummy', function () {
    dd('dskmsdsf');
});

Route::get('/', [Web\ManagerController::class, 'dashboard'])
    ->name('dashboard');

Route::get('institutions/create', [Web\Institutions\InstitutionRegistrationController::class, 'create'])
    ->name('institutions.create');
Route::post('institutions/store', [Web\Institutions\InstitutionRegistrationController::class, 'store'])
    ->name('institutions.store');
Route::get('/institutions', Web\Institutions\ListInstitutionsController::class)
    ->name('institutions.index');
Route::delete('/institutions/{institution}/destroy', Web\Institutions\DeleteInstitutionController::class)
    ->name('institutions.destroy');

Route::get('/institution-groups/search', [Web\InstitutionGroups\InstitutionGroupsController::class, 'search'])
    ->name('institution-groups.search');
Route::resource('institution-groups', Web\InstitutionGroups\InstitutionGroupsController::class)
    ->except('show');

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

//Admin section
Route::group(['middleware' => 'admin'], function () {
    Route::get('/generate-pin', [Web\GeneratePinController::class, 'create'])
        ->name('generate-pin.create');
    Route::post('/generate-pin', [Web\GeneratePinController::class, 'store'])
        ->name('generate-pin.store');

    Route::get('/pins/{pinGenerator?}', [Web\PinController::class, 'index'])
        ->name('pins.index');
    Route::get('/pin-generators', [Web\PinGeneratorController::class, 'index'])
        ->name('pin-generators.index');

    Route::get('/index', [Web\ManagerController::class, 'index'])
        ->name('index');
    Route::get('/create', [Web\ManagerController::class, 'create'])
        ->name('create');
    Route::post('/store', [Web\ManagerController::class, 'store'])
        ->name('store');
    Route::delete('/destroy/{user}', [Web\ManagerController::class, 'destroy'])
        ->name('destroy');
});


Route::resource('funding', Web\Fundings\FundingsController::class);
Route::resource('billings', Web\Billings\BillingsController::class);