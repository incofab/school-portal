<?php

use App\Http\Controllers\Institutions as Web;
use Illuminate\Support\Facades\Route;

Route::resource('/libraries', Web\Libraries\LibraryController::class)
    ->names('libraries');
