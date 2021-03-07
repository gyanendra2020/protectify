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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/users/{user}/update', 'Api\UserController@update')->middleware('auth:sanctum');
Route::post('/users/{user}/ub/pages', 'Api\Ub\UbPageController@create');
Route::delete('/ub/pages/{ub_page}', 'Api\Ub\UbPageController@delete')->middleware('auth:sanctum');
Route::get('/users/autocomplete', 'Api\UserController@autocomplete')->middleware('auth:sanctum');
Route::post('/ub/pages/{ub_page_key}/events', 'Api\Ub\UbEventController@create');
Route::get('/ub/pages/{ub_page_key}/events', 'Api\Ub\UbEventController@list');
Route::post('/ub/pages/{ub_page_key}/form_inputs', 'Api\Ub\UbFormInputController@create');
