<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Managers as Web;

Route::get('/dummy', function () {
    dd('dskmsdsf');    
});

Route::get('/', [Web\ManagerController::class, 'index'])
    ->name('dashboard');

Route::get('/generate-pin', [Web\GeneratePinController::class, 'create'])
    ->name('generate-pin.create');
Route::post('/generate-pin', [Web\GeneratePinController::class, 'store'])
    ->name('generate-pin.store');

Route::get('/institutions', [Web\InstitutionsController::class, 'index'])
    ->name('institutions.index');
Route::get('/pins/{pinGenerator?}', [Web\PinController::class, 'index'])
    ->name('pins.index');
Route::get('/pin-generators', [Web\PinGeneratorController::class, 'index'])
    ->name('pin-generators.index');
