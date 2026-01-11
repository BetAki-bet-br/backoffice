<?php

use Illuminate\Support\Facades\Route;

Route::view('/login', 'auth.login')->name('login');

Route::view('/', 'dashboard')->name('dashboard');

// Conteúdo
Route::view('/banners', 'content.banners.index')->name('banners.ui');
Route::view('/slots', 'content.slots.index')->name('slots.ui');
Route::view('/categories', 'content.categories.index')->name('categories.ui');
Route::view('/lobbies', 'content.lobbies.index')->name('lobbies.ui');
Route::view('/menus', 'content.menus.index')->name('menus.ui');
Route::view('/footers', 'content.footers.index')->name('footers.ui');
Route::view('/showcases', 'content.showcases.index')->name('showcases.ui');
Route::view('/top-lists', 'content.toplists.index')->name('toplists.ui');
Route::view('/awards', 'content.awards.index')->name('awards.ui');
Route::view('/top-winners', 'content.topwinners.index')->name('topwinners.ui');
Route::view('/footers', 'content.footers.index')->name('footers.ui');
Route::view('/users', 'content.users.index')->name('users.ui');
Route::view('/game-extras', 'content.game_extras.index')->name('gameextras.ui');
