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

Route::get('/', function () {
    return view('welcome');
});

Route::group(['prefix' => 'telegram'], function () {
    Route::get('/me', 'TelegramBotController@getMe');
    Route::get('/updates', 'TelegramBotController@getUpdates');
    Route::get('/response', 'TelegramBotController@getResponse');

    //webhook bot
    Route::get('/setWebhook', 'TelegramBotController@setWebhook');
    Route::get('/webhook', 'TelegramBotController@webhook');
    Route::post('/webhook', 'TelegramBotController@webhook');

    //remove webhook
    Route::get('/removeWebhook','TelegramBotController@removeWebhook');
});
