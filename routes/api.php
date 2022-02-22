<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::post('/register/member', 'Api\V1\Auth\RegisterController@actionRegister')->name('api.register');
Route::post('/password/email', 'Api\V1\Auth\ForgotPasswordController@actionSendResetLinkEmail')->name('api.reset');
Route::get('email/verify/{id}', 'Api\V1\Auth\VerificationApiController@verify')->name('api.verification.verify');
Route::get('email/resend', 'Api\V1\Auth\VerificationApiController@resend')->name('api.verification.resend');

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', 'Api\V1\Auth\AuthController@actionLogin')->name('api.login');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('/me', 'Api\V1\Auth\AuthController@actionMe')->name('api.me')
            ->middleware('verified');
        Route::post('/logout', 'Api\V1\Auth\AuthController@actionLogout')->name('api.logout');
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/me/{id}', 'Api\V1\ProfileController@actionProfile')->name('api.profile');
        Route::put('/me/update', 'Api\V1\ProfileController@actionProfileUpdate')->name('api.profile_update');
        Route::post('/photo/update', 'Api\V1\ProfileController@actionPhotoUpdate')->name('api.photo_update');
    });

    Route::get('/users', 'Api\V1\UserController@actionUsers')->name('api.users');
    Route::post('/usersListSearch', 'Api\V1\UserController@actionUsersListSearch')->name('api.usersListSearch');
    Route::post('/usersPageSearch', 'Api\V1\UserController@actionUsersPageSearch')->name('api.usersPageSearch');
    Route::get('/user/{id}', 'Api\V1\UserController@actionUser')->name('api.user');
    Route::post('/user/store', 'Api\V1\UserController@actionUserStore')->name('api.user_store');
    Route::put('/user/update', 'Api\V1\UserController@actionUserUpdate')->name('api.user_update');
    Route::delete('/user/delete/{id}', 'Api\V1\UserController@actionUserDelete')->name('api.user_delete');
    Route::prefix('user')->group(function () {
        Route::get('/{id}/calendars', 'Api\V1\UserController@actionUserCalendars')->name('api.user_calendars');
    });

    Route::get('/calendars', 'Api\V1\CalendarController@actionCalendars')->name('api.calendars');
    Route::post('/calendarsListSearch', 'Api\V1\CalendarController@actionCalendarsListSearch')->name('api.calendarsListSearch');
    Route::post('/calendarsPageSearch', 'Api\V1\CalendarController@actionCalendarsPageSearch')->name('api.calendarsPageSearch');
    Route::get('/calendar/{id}', 'Api\V1\CalendarController@actionCalendar')->name('api.calendar');
    Route::post('/calendar/store', 'Api\V1\CalendarController@actionCalendarStore')->name('api.calendar_store');
    Route::prefix('calendar')->group(function () {
        Route::put('/{id}/update', 'Api\V1\CalendarController@actionCalendarUpdate')->name('api.calendar_update');
        Route::delete('/{id}/delete', 'Api\V1\CalendarController@actionCalendarDelete')->name('api.calendar_delete');
    });

});
