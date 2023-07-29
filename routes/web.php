<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::post('/trylogin', [LoginController::class, 'TryLogin']);

Route::get('/reports', [APIController::class, 'Report']);
Route::post('/statistics/listeners', [APIController::class, 'StatisticsListeners']);
Route::post('/statistics/countries', [APIController::class, 'StatisticsCountries']);
Route::post('/statistics/tracks', [APIController::class, 'StatisticsTracks']);
Route::post('/statistics/userAgents', [APIController::class, 'StatisticsUserAgents']);
Route::post('/statistics/historical', [APIController::class, 'StatisticsHistorical']);
