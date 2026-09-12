<?php

use App\Http\Controllers\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Trasy otrzymują prefiks /api z konfiguracji aplikacji.
// auth:sanctum sprawdza uwierzytelnienie; dostęp do konkretnego organizatora sprawdzamy osobno.
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Parametr {organizer} jest wiązany z modelem Organizer w kontrolerze.
Route::post(
    '/organizers/{organizer}/events',
    [EventController::class, 'store']
)->middleware('auth:sanctum');

// PATCH służy do częściowej aktualizacji; pominięcie pola nie powinno go wyzerować.
Route::patch(
    '/events/{event}',
    [EventController::class, 'update']
)->middleware('auth:sanctum');

Route::post(
    // Osobna operacja biznesowa zamiast dowolnej zmiany statusu przez PATCH.
    '/events/{event}/publish',
    [EventController::class, 'publish']
)->middleware('auth:sanctum');

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);