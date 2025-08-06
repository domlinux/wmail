<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmailController;

Route::get('/', [EmailController::class, 'create'])->name('email.create');
Route::post('/', [EmailController::class, 'send'])->name('email.send');


