<?php

use App\Http\Controllers\DjautoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WidgetController;
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

Route::post('/statistics/tracks', function () {
    $user = 'admin';
    $pass = 'RSRnet2018';
    $url  = "http://$user:$pass@51.81.208.185:8800/admin.cgi?sid=1&mode=viewxml&page=3";

    $obj  = json_decode(json_encode(simplexml_load_file($url)));
    print_r($obj);
    // return view('welcome');
});

Route::post('/trylogin', [LoginController::class, 'TryLogin']);

Route::get('/reports', [StatisticsController::class, 'Report']);
Route::post('/statistics/listeners', [StatisticsController::class, 'StatisticsListeners']);
Route::post('/statistics/liveListeners', [StatisticsController::class, 'StatisticsLiveListeners']);
Route::post('/statistics/countries', [StatisticsController::class, 'StatisticsCountries']);
// Route::post('/statistics/tracks', [StatisticsController::class, 'StatisticsTracks']);
Route::post('/statistics/userAgents', [StatisticsController::class, 'StatisticsUserAgents']);
Route::post('/statistics/historical', [StatisticsController::class, 'StatisticsHistorical']);

Route::post('/djauto/playlists', [DjautoController::class, 'Playlists']);


Route::post('/widgets/update/recent_tracks', [WidgetController::class, 'UpdateRecentTracks']);
Route::post('/widgets/update/song_requests', [WidgetController::class, 'UpdateSongRequests']);
