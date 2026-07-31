<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| SIPERDO is an SPA. Every route not matched by /api is served by the
| single-page application shell, so the client-side router can take over.
|
*/

Route::get('/{any}', fn () => view('app'))->where('any', '.*');
