<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\Route;
use Modules\Api\Controllers\RentAPIController;
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
/* Config */
Route::get('configs','BookingController@getConfigs')->name('api.get_configs');

/* Rent Only Routes */
// Route::get('search','SearchController@search')->name('api.search2');
Route::get('search','RentAPIController@search')->name('api.search2');
Route::get('detail/{id}','RentAPIController@show')->name('api.detail');
Route::get('availability/{id}','SearchController@checkAvailability')->name('api.service.check_availability');
/* End Of Rent Only Routes */
 
/* Service */
Route::get('services','SearchController@searchServices')->name('api.service-search');
Route::get('{type}/search','SearchController@search')->name('api.search2');
Route::get('{type}/detail/{id}','SearchController@detail')->name('api.detail');
Route::get('{type}/availability/{id}','SearchController@checkAvailability')->name('api.service.check_availability');
Route::get('boat/availability-booking/{id}','SearchController@checkBoatAvailability')->name('api.service.checkBoatAvailability');

Route::get('{type}/filters','SearchController@getFilters')->name('api.service.filter');
Route::get('{type}/form-search','SearchController@getFormSearch')->name('api.service.form');

Route::group(['middleware' => 'api'],function(){
    Route::post('{type}/write-review/{id}','ReviewController@writeReview')->name('api.service.write_review');
});


/* Layout HomePage */
Route::get('home-page','BookingController@getHomeLayout')->name('api.get_home_layout');

/* Register - Login */
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function ($router) {
    Route::post('login', 'AuthController@login')->middleware(['throttle:login']);
    Route::post('register', 'AuthController@register');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::get('me', 'AuthController@me');
    Route::post('me', 'AuthController@updateUser');
    Route::post('change-password', 'AuthController@changePassword');

});

Route::group(['middleware'=>['auth:sanctum']],function(){
    // Products
    Route::get('my-products','RentAPIController@myProducts')->name('api.myProducts');
    Route::post('cartBookingTour','BookingController@addToCart')->name("api.booking.cart_booking_tour");
    Route::post('product/save','RentAPIController@store')->name('api.store_product');
    // Route::get('get-dates','RentAPIController@getCarDates')->name('api.get_tour_dates');
    // Route::post('set-dates/{target_id}','TourAPIController@setTourDates')->name('api.set_tour_dates');
    // Route::delete('tour-dates/delete/{id}/{price}','TourAPIController@deleteTourDates')->name('api.delete_tour_dates');
    Route::put('product/update/{id}','RentAPIController@update')->name('api.update_product');
    Route::delete('product/delete/{id}','RentAPIController@delete')->name('api.delete_product');
    // Route::delete('trip/private/delete/{id}','TourAPIController@deleteTrip')->name('api.delete_trip');
});

/* User */
Route::group(['prefix' => 'user', 'middleware' => ['api'],], function ($router) {
    Route::get('booking-history', 'UserController@getBookingHistory')->name("api.user.booking_history");
    Route::post('/wishlist','UserController@handleWishList')->name("api.user.wishList.handle");
    Route::get('/wishlist','UserController@indexWishlist')->name("api.user.wishList.index");
    Route::post('/permanently_delete','UserController@permanentlyDelete')->name("user.permanently.delete");

});

/* Location */
Route::get('locations','LocationController@search')->name('api.location.search');
Route::get('location/{id}','LocationController@detail')->name('api.location.detail');

// Booking
Route::group(['prefix'=>config('booking.booking_route_prefix')],function(){
    Route::post('/addToCart','BookingController@addToCart')->name("api.booking.add_to_cart");
    Route::post('/addEnquiry','BookingController@addEnquiry')->name("api.booking.add_enquiry");
    Route::post('/doCheckout','BookingController@doCheckout')->name('api.booking.doCheckout');
    Route::get('/confirm/{gateway}','BookingController@confirmPayment');
    Route::get('/cancel/{gateway}','BookingController@cancelPayment');
    Route::get('/{code}','BookingController@detail');
    Route::get('/{code}/thankyou','BookingController@thankyou')->name('booking.thankyou');
    Route::get('/{code}/checkout','BookingController@checkout');
    Route::get('/{code}/check-status','BookingController@checkStatusCheckout');
});

// Gateways
Route::get('/gateways','BookingController@getGatewaysForApi');

// News
Route::get('news','NewsController@search')->name('api.news.search');
Route::get('news/category','NewsController@category')->name('api.news.category');
Route::get('news/{id}','NewsController@detail')->name('api.news.detail');

/* Media */
Route::group(['prefix'=>'media','middleware' => 'auth:api'],function(){
    Route::post('/store','MediaController@store')->name("api.media.store");
});
