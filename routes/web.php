<?php

use Illuminate\Support\Facades\Route;

// Route test existante
Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is working!'
    ]);
});

// Nouvelle route de test
Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'Ping route is working!',
        'timestamp' => now()
    ]);
});
