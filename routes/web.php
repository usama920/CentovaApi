<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\DjautoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\WidgetController;
use App\Models\Track;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

Route::get('/tryftp', [LoginController::class, 'ftp']);

Route::post('/trylogin', [LoginController::class, 'TryLogin']);
Route::post('/changePassword', [LoginController::class, 'ChangePassword']);
Route::post('/allAppUsers', [LoginController::class, 'AllAppUsers']);
Route::post('/playlist/download', [StatisticsController::class, 'PlaylistDownload']);
Route::post('/performance/download', [StatisticsController::class, 'PerformanceDownload']);

Route::post('/getAccount', [LoginController::class, 'GetAccount']);
Route::post('/forgotPasswordCode', [LoginController::class, 'ForgotPasswordCode']);
Route::post('/forgotPasswordVerifyCode', [LoginController::class, 'ForgotPasswordVerifyCode']);
Route::post('/forgotPasswordSave', [LoginController::class, 'ForgotPasswordSave']);
Route::post('/updateAccount', [LoginController::class, 'UpdateAccount']);
Route::post('/updateBannedCountries', [LoginController::class, 'UpdateBannedCountries']);
Route::get('/getBannedCountries/{username}', [LoginController::class, 'GetBannedCountries']);
Route::post('/version/switch', [LoginController::class, 'UpdateVersion']);


Route::get('/reports', [StatisticsController::class, 'Report']);
Route::post('/statistics/listeners', [StatisticsController::class, 'StatisticsListeners']);
Route::post('/statistics/liveListeners', [StatisticsController::class, 'StatisticsLiveListeners']);
Route::post('/statistics/countries', [StatisticsController::class, 'StatisticsCountries']);
Route::post('/statistics/tracks', [StatisticsController::class, 'StatisticsTracks']);
Route::post('/statistics/userAgents', [StatisticsController::class, 'StatisticsUserAgents']);
Route::post('/statistics/historical', [StatisticsController::class, 'StatisticsHistorical']);

Route::post('/djauto/enable', [DjautoController::class, 'EnableAutodj']);
Route::post('/djauto/playlists', [DjautoController::class, 'Playlists']);
Route::post('/djauto/update/settings', [DjautoController::class, 'UpdateSettings']);
Route::post('/djauto/createJingle', [DjautoController::class, 'CreateJingle']);
Route::post('/djauto/createPlaylist', [DjautoController::class, 'CreatePlaylist']);
Route::post('/djauto/updatePlaylist', [DjautoController::class, 'UpdateAllPlaylist']);
Route::post('/djauto/playlist/tracks', [DjautoController::class, 'PlaylistTracks']);
Route::post('/djauto/delete/playlist', [DjautoController::class, 'DeletePlaylist']);
Route::post('/djauto/update/playlist/{type}', [DjautoController::class, 'UpdatePlaylist']);

Route::post('/update/playlist/cron', [CronController::class, 'UpdatePlaylist']);



Route::post('/widgets/update/recent_tracks', [WidgetController::class, 'UpdateRecentTracks']);
Route::post('/widgets/update/song_requests', [WidgetController::class, 'UpdateSongRequests']);
