<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use App\Http\Controllers\AlertController;
use App\Http\Controllers\CanteenController;
use App\Http\Controllers\Auth\OidcController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventsImportController;
use App\Http\Controllers\HappyHourController;
use App\Http\Controllers\LocalizationController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PictureController;
use App\Http\Controllers\RealmController;
use App\Http\Controllers\SlideController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VideoController;
use App\Livewire\EventsList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/monitors');
Route::get('lang/{locale}', [LocalizationController::class, 'index']);
Route::get('status.json', function () {
    return ['status' => 'OK'];
});
Route::get('requestpull/{realm:orders_pull}', [RealmController::class, 'forceUpdateOrdersList'])
    ->name('realm.requestpull')
    ->middleware('throttle:10,1');

// Public monitor & display endpoints
Route::get('/latest/{monitor:api_token}', [MonitorController::class, 'collectLastUpdate'])->name('latest');
Route::get('/showEvents/{monitor:api_token}', [MonitorController::class, 'display'])->name('showEventsToken')
    ->missing(function (Request $request) {
        abort(403);
    });
Route::get('Content/{monitor:api_token}/data.json', [MonitorController::class, 'getJson']);

// Authentication routes
Auth::routes(['register' => false, 'verify' => true]);

// Optional OIDC login (the controller itself 404s unless services.openidconnect.enabled is true)
Route::get('login/oidc/redirect', [OidcController::class, 'redirect'])->name('oidc.redirect');
Route::get('login/oidc/callback', [OidcController::class, 'callback'])->name('oidc.callback');

// Back-channel logout: server-to-server call from the IdP, not a browser request.
Route::post('oidc/backchannel-logout', [OidcController::class, 'backchannelLogout'])
    ->name('oidc.backchannel-logout')
    ->withoutMiddleware(['web']);

// Authenticated & verified users
Route::middleware(['verified', 'auth'])->group(function () {
    // User profile
    Route::resource('users', UserController::class)->only(['edit', 'update']);
    Route::post('realm-switch/{realm}', [UserController::class, 'switchRealm'])->name('realm.switch');

    // Events
    Route::get('/events', EventsList::class)->name('events.index');
    Route::get('/events/create/{template_id}', [EventController::class, 'create'])->name('events.create.template');
    Route::resource('events', EventController::class)->only(['create', 'edit']);
    Route::get('/events/{event}/happy-hours/create', [HappyHourController::class, 'create'])->name('events.happyHours.create');
    Route::get('/events/{event}/happy-hours/{happyHour}/edit', [HappyHourController::class, 'edit'])->name('events.happyHours.edit');

    // Templates
    Route::resource('templates', TemplateController::class)->only(['index', 'create', 'edit']);

    // Picture sources (per-slide media formats; the picture itself is managed
    // entirely through its Slide, see below)
    Route::post('pics/{pic}/source', [PictureController::class, 'storeSource'])->name('pics.storeSource');
    Route::put('pics/source/{source}', [PictureController::class, 'updateSource'])->name('pics.updateSource');
    Route::delete('pics/source/{source}', [PictureController::class, 'destroySource'])->name('pics.destroySource');

    // Slides (picture & video, unified) - a Picture/Video belongs to exactly
    // one Slide and is created/edited/deleted through it, not separately.
    Route::resource('slides', SlideController::class)->only(['index', 'create', 'edit']);

    // Menus
    Route::resource('menus', MenuController::class)->only(['index', 'create', 'edit', 'update', 'destroy']);

    // Canteens
    Route::resource('canteens', CanteenController::class)->only(['index', 'create', 'edit']);

    // Monitors
    Route::resource('monitors', MonitorController::class)->except(['destroy', 'store', 'update']);
    Route::get('/show/{monitor}', [MonitorController::class, 'display'])
        ->name('showEvents')
        ->middleware('can:view,monitor');
});

// Token-protected routes
Route::middleware('token')->group(function () {
    // Event API edits
    Route::get('/events/{event}/edit/{api_token}', [EventController::class, 'edit'])->name('events.avedit');
    Route::get('/events/{event}/happy-hours/create/{api_token}', [HappyHourController::class, 'create'])->name('events.happyHours.avcreate');
    Route::get('/events/{event}/happy-hours/{happyHour}/edit/{api_token}', [HappyHourController::class, 'edit'])->name('events.happyHours.avedit');
    // Route::put('/events/{event}/{api_token}', [EventController::class, 'update'])->name('events.avupdate');

    // Media views
    Route::get('pics/{pic}', [PictureController::class, 'show'])->name('pics.show');
    Route::get('menus/{menu}', [MenuController::class, 'show'])->name('menus.show');
    Route::get('videos/{video}', [VideoController::class, 'show'])->name('videos.show');

    // Monitor statistics
    Route::post('monitors/stats/{monitor:api_token}', [MonitorController::class, 'storeStats']);
});

// Admins only
Route::middleware(['verified', 'auth', 'isAdmin'])->group(function () {
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts', [AlertController::class, 'create']);
    Route::resource('realms', RealmController::class)->only(['edit']);
    Route::resource('eventsImports', EventsImportController::class)->only(['index', 'create', 'edit']);
    Route::post('/eventsImports/run/{import}/{force?}/{disabled?}', [EventsImportController::class, 'runImport'])->name('eventsImports.run');
    Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [RegisterController::class, 'register']);
});
