<?php

use Illuminate\Support\Facades\Route;

// Startowa strona HTML z szablonu Laravel; frontend wydarzeń korzysta z osobnych tras API.
Route::get('/', function () {
    return view('welcome');
});
