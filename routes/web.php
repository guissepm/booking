<?php
/*
|--------------------------------------------------------------------------
| routes/web.php *** Copyright netprogs.pl | avaiable only at Udemy.com | further distribution is prohibited  ***
|--------------------------------------------------------------------------
*/

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


Route::get('/','FrontendController@index')->name('home'); /* Lecture 6 */
Route::get(trans('routes.object').'/{id}','FrontendController@object')->name('object'); /* Lecture 5 Lecture 15 {id}  */
Route::post(trans('routes.roomsearch'),'FrontendController@roomsearch')->name('roomSearch'); /* Lecture 5 Lecture 18 get->post */
Route::get(trans('routes.room').'/{id}','FrontendController@room')->name('room'); /* Lecture 6 Lecture 19 {id} */
Route::get(trans('routes.article').'/{id}','FrontendController@article')->name('article'); /* Lecture 6 Lecture 22 {id} */
Route::get(trans('routes.person').'/{id}','FrontendController@person')->name('person'); /* Lecture 6 Lecture 23 {id} */
 
Route::get('/searchCities', 'FrontendController@searchCities'); /* Lecture 17 */
Route::get('/ajaxGetRoomReservations/{id}', 'FrontendController@ajaxGetRoomReservations'); /* Lecture 20 */

Route::get('/like/{likeable_id}/{type}', 'FrontendController@like')->name('like'); /* Lecture 24 */
Route::get('/unlike/{likeable_id}/{type}', 'FrontendController@unlike')->name('unlike'); /* Lecture 24 */

Route::post('/addComment/{commentable_id}/{type}', 'FrontendController@addComment')->name('addComment'); /* Lecture 25 */
Route::post('/makeReservation/{room_id}/{city_id}', 'FrontendController@makeReservation')->name('makeReservation'); /* Lecture 26 */
Route::post(trans('routes.review').'/{reservation_id}', 'FrontendController@addReview')->name('addReview');

Route::post(trans('routes.object').'/{object_id}/'.trans('routes.messages'), 'FrontendController@startConversation')->name('startConversation');
Route::get(trans('routes.inbox'), 'FrontendController@inbox')->name('inbox');
Route::get(trans('routes.messages').'/{conversation_id}', 'FrontendController@showConversation')->name('showConversation');
Route::post(trans('routes.messages').'/{conversation_id}', 'FrontendController@postMessage')->name('postMessage');

Route::get('/checkout/{reservation_id}', 'PaymentController@checkout')->name('checkout');
Route::get('/checkout/{reservation_id}/success', 'PaymentController@success')->name('checkoutSuccess');
Route::get('/checkout/{reservation_id}/cancel', 'PaymentController@cancel')->name('checkoutCancel');
Route::post('/stripe/webhook', 'PaymentController@webhook')->name('stripeWebhook');

 
//for json mobile
Route::get('/cities', 'FrontendController@cities'); /* Lecture 60 */


Route::group(['prefix'=>'admin'],function(){  /* Lecture 6 Lecture 7 'middleware'=>'auth' Lecture 60 remove middleware */ 
    
  //for json mobile
  Route::get('/getNotifications', 'BackendController@getNotifications'); /* Lecture 53 */
  Route::post('/setReadNotifications', 'BackendController@setReadNotifications'); /* Lecture 53 */
    
  Route::get('/','BackendController@index')->name('adminHome'); /* Lecture 6 */  
  Route::get(trans('routes.myobjects'),'BackendController@myobjects')->name('myObjects'); /* Lecture 6 */  
  Route::match(['GET','POST'],trans('routes.saveobject').'/{id?}','BackendController@saveObject')->name('saveObject'); /* Lecture 6 Lecture 41 match(['GET','POST'];/{id?} */  
  Route::match(['GET','POST'],trans('routes.profile'),'BackendController@profile')->name('profile'); /* Lecture 6  Lecture 39 match(['GET','POST'] */ 
  Route::get('/deletePhoto/{id}', 'BackendController@deletePhoto')->name('deletePhoto'); /* Lecture 39 */
  Route::match(['GET','POST'],trans('routes.saveroom').'/{id?}', 'BackendController@saveRoom')->name('saveRoom'); /* Lecture 47 */  
  Route::get(trans('routes.deleteroom').'/{id}', 'BackendController@deleteRoom')->name('deleteRoom'); /* Lecture 47 */
  
  Route::get('/deleteArticle/{id}', 'BackendController@deleteArticle')->name('deleteArticle'); /* Lecture 44 */
  Route::post('/saveArticle/{id?}', 'BackendController@saveArticle')->name('saveArticle'); /* Lecture 44 */
  
  Route::get('/ajaxGetReservationData', 'BackendController@ajaxGetReservationData'); /* Lecture 30 */
  Route::get('/ajaxSetReadNotification', 'BackendController@ajaxSetReadNotification'); /* Lecture 50 */
  Route::get('/ajaxGetNotShownNotifications', 'BackendController@ajaxGetNotShownNotifications'); /* Lecture 51 */
  Route::get('/ajaxSetShownNotifications', 'BackendController@ajaxSetShownNotifications'); /* Lecture 52 */
  
  Route::get('/confirmReservation/{id}', 'BackendController@confirmReservation')->name('confirmReservation'); /* Lecture 33 */
  Route::get('/deleteReservation/{id}', 'BackendController@deleteReservation')->name('deleteReservation'); /* Lecture 33 */
  
  Route::resource('cities', 'CityController'); /* Lecture 37 */
  
  Route::get(trans('routes.deleteobject').'/{id}', 'BackendController@deleteObject')->name('deleteObject'); /* Lecture 46 */
  
  
    
    
});


Auth::routes();

//Route::get('/home', 'HomeController@index')->name('home');  /* Lecture 7 */

