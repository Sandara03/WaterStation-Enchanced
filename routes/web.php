<?php

use Illuminate\Support\Facades\Route;

Route::get('/{path?}', function () {
    $index = public_path('app/index.html');
    if (file_exists($index)) return response(file_get_contents($index), 200, ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache']);
    return response('AHY API is running. Start the frontend at http://127.0.0.1:9000, or run BUILD_LOCAL.ps1.', 200);
})->where('path', '^(?!api(?:/|$)|up$|app/).*');
