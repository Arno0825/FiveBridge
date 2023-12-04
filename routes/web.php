<?php

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
    $point = [
        'player_1' => 5,
        'player_2' => 0,
        'player_3' => 0,
        'player_4' => 1,
        'player_5' => 0,
    ];
    return view('welcome');
});
