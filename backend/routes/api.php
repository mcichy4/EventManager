<?php

use App\Http\Controllers\OrganizerController;
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

Route::post('/organizers', [OrganizerController::class, 'store'])->middleware('auth:sanctum');

Route::get('/events', [EventController::class, 'index']);
Route::delete('/events/{event}', [EventController::class, 'destroy'])->middleware('auth:sanctum');
Route::get('/events/{event}', [EventController::class, 'show']);
Route::post('/events/{event}/cancel', [EventController::class, 'cancelEvent'])->middleware('auth:sanctum');
Route::post('/events/{event}/applications', [EventController::class, 'apply'])->middleware('auth:sanctum');
Route::delete('/event-applications/{eventApplication}', [EventController::class, 'cancelApplication'])->middleware('auth:sanctum');
Route::post('/event-applications/{eventApplication}/accept', [EventController::class, 'acceptApplication'])->middleware('auth:sanctum');
Route::post('/event-applications/{eventApplication}/reject', [EventController::class, 'rejectApplication'])->middleware('auth:sanctum');
Route::get('/me/event-applications', [EventController::class, 'myApplications'])->middleware('auth:sanctum');
