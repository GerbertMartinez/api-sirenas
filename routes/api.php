<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MainController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WebController;

/* USER INTERACTION */
Route::post('/login', [UserController::class, 'login']);
Route::get('/hash', [UserController::class, 'hash']);
Route::post('/change', [UserController::class, 'change']);
Route::get('/test_token', [UserController::class, 'test_token']);
Route::get('/test_receipt', [UserController::class, 'test_receipt']);

/* WRITE */
Route::post('/register_activity', [MainController::class, 'register_activity']);
Route::post('/register_info', [MainController::class, 'register_info']);

/* DATA */
Route::get('/get_main_data', [MainController::class, 'get_main_data']);
Route::post('/get_data', [MainController::class, 'get_data']);
Route::get('/get_sirens', [MainController::class, 'get_sirens']);
Route::post('/get_sirens_user', [MainController::class, 'get_sirens_user']);
Route::get('/get_activities', [MainController::class, 'get_activities']);
Route::post('/get_historic', [MainController::class, 'get_historic']);
Route::get('/get_webs', [MainController::class, 'get_webs']);
Route::post('/get_web', [MainController::class, 'get_web']);
Route::post('/get_siren', [MainController::class, 'get_siren']);

/* ACTIONS */
Route::get('/on_sirens', [MainController::class, 'on_sirens']);
Route::get('/off_sirens', [MainController::class, 'off_sirens']);
Route::get('/test_sirens', [MainController::class, 'test_sirens']);

Route::post('/on_all', [MainController::class, 'on_all']);
Route::post('/off_all', [MainController::class, 'off_all']);
Route::post('/test_all', [MainController::class, 'test_all']);

Route::post('/on_web', [MainController::class, 'on_web']);
Route::post('/off_web', [MainController::class, 'off_web']);
Route::post('/test_web', [MainController::class, 'test_web']);

Route::post('/on_siren', [MainController::class, 'on_siren']);
Route::post('/off_siren', [MainController::class, 'off_siren']);
Route::post('/test', [MainController::class, 'test']);
Route::post('/ping', [MainController::class, 'ping']);

/* SPEAKERS */
Route::get('/on_speakers',[MainController::class, 'on_speakers']);
Route::get('/off_speakers',[MainController::class, 'off_speakers']);
Route::get('/test_speakers',[MainController::class, 'test_speakers']);

/* ADMIN */
Route::post('/edit_web_sirens/{id}', [WebController::class, 'edit_web_sirens']);
Route::post('/edit_user_webs/{id}', [UserController::class, 'edit_user_webs']);
Route::post('/create_user', [UserController::class, 'create']);
Route::post('/delete_user', [UserController::class, 'delete']);
Route::post('/create_web', [WebController::class, 'create']);
Route::post('/delete_web', [WebController::class, 'delete']);
Route::get('/get_users', [UserController::class, 'get_users']);