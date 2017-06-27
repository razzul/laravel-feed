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

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('posts/', ['as' => 'post.archive', 'uses' => 'PostController@archive']);
Route::get('post/{id}/{slug?}', ['as' => 'post.single', 'uses' => 'PostController@single']);
Route::get('feed/{type?}', ['as' => 'feed.atom', 'uses' => 'Feed\FeedsController@getFeed']);