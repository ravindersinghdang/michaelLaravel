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

Route::get('orders/{id}','OrdersController@index');
Route::get('draft_orders/{id}','OrdersController@draft_orders');
Route::get('products/{id}/metafields','ProductsController@index');
Route::get('products/{pid}/variants/{vid}/metafields','ProductsController@variant_metafields');
Route::get('checkouts/{id}','CheckoutController@index');