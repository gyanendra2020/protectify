<?php

use Illuminate\Support\Facades\Route;

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

Auth::routes();

Route::get('/', function () {
    return redirect()->route('dashboard.index');
})->middleware('auth');

Route::prefix('admin')->middleware('role:admin')->group(function () {
    Route::get('/', 'Admin\MainController@index')->name('admin.index');
    Route::get('users', 'Admin\MainController@users')->name('admin.users');
});

Route::prefix('dashboard')->middleware('auth')->group(function () {
    Route::get('/', 'Dashboard\MainController@index')->name('dashboard.index');
    Route::get('get-code', 'Dashboard\MainController@getCode')->name('dashboard.get_code');
    Route::get('ub-pages', 'Dashboard\UbPageController@list')->name('dashboard.ub_pages');
    Route::get('ub-pages/{ub_page_id}', 'Dashboard\UbPageController@view')->name('dashboard.ub_page');
    Route::get('subscription', 'Dashboard\SubscriptionController@index')->name('dashboard.subscription');
    Route::post('create', 'Dashboard\SubscriptionController@create')->name('dashboard.create');
    Route::get('user-list', 'Dashboard\SubscriptionController@index')->name('dashboard.user-list');

    /*********************************** Add users  *************************/
    
    Route::get('user-list', 'Dashboard\RegisterUserController@index')->name('dashboard.user-list');
    Route::get('add-user', 'Dashboard\RegisterUserController@create')->name('dashboard.add-user');
    Route::post('add-user', 'Dashboard\RegisterUserController@store')->name('dashboard.add-user');
    Route::get('edit-user/{id}', 'Dashboard\RegisterUserController@edit')->name('dashboard.edit-user');
    Route::post('edit-user', 'Dashboard\RegisterUserController@update')->name('dashboard.edit-user');

});

