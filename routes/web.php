<?php
use Illuminate\Http\Request;
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
Route::get('checkouts','CheckoutController@checkouts');
Route::post('quote', 'QuoteController@index');
Route::post('create_draft_order', 'OrdersController@create_draft_order');
Route::post('order_fulfillment', 'OrdersController@order_fulfillment');
Route::post('order_update', 'OrdersController@order_update');
Route::post('create_order', 'OrdersController@create_order');
Route::post('delete_order', 'OrdersController@delete_order');
Route::post('update_order', 'OrdersController@update_order');