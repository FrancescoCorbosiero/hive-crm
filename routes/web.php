<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])
    ->middleware(['web'])
    ->name('locale.switch');
