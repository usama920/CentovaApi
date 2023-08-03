<?php

use App\Http\Controllers\DjautoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StatisticsController;
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

Route::get('/reports', [StatisticsController::class, 'Report']);
Route::post('/statistics/listeners', [StatisticsController::class, 'StatisticsListeners']);
Route::post('/statistics/countries', [StatisticsController::class, 'StatisticsCountries']);
Route::post('/statistics/tracks', [StatisticsController::class, 'StatisticsTracks']);
Route::post('/statistics/userAgents', [StatisticsController::class, 'StatisticsUserAgents']);
Route::post('/statistics/historical', [StatisticsController::class, 'StatisticsHistorical']);

Route::post('/djauto/playlists', [DjautoController::class, 'Playlists']);
