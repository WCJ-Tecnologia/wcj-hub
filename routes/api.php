<?php

use Illuminate\Support\Facades\Route;

// The only public API endpoint. No data, no version, no auth needed.
Route::get('health', fn () => response()->json(['ok' => true]))->name('api.health');
