<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

if (app()->environment('local')) {
    Route::get('/mcp-test', fn () => view('debug.mcp-tester'))->name('mcp.debug-tester');
}
