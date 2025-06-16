<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;

use App\Http\Controllers\BookingController;

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CartController;
use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\Log;



route::get('/', [AdminController::class, 'home']);
route::get('/home', [AdminController::class, 'index'])->name('home');
Route::get('/about', [AdminController::class, 'about'])->name('about');
Route::get('/room', [AdminController::class, 'room'])->name('room');
Route::get('/cart', [AdminController::class, 'cart'])->name('cart');
Route::get('/hotel/{id}', [AdminController::class, 'show']);
Route::get('/locations/search', [AdminController::class, 'liveSearch'])->name('locations.search');
Route::get('/coupon', [AdminController::class, 'coupon'])->name('coupon');




route::get('/create_room', [RoomController::class, 'create_room']);
route::get('/view_room', [RoomController::class, 'view_room']);
Route::get('/delete_room/{id}', [RoomController::class, 'delete_room']);
route::post('/add_room', [RoomController::class, 'add_room']);
route::get('/update_room/{id}', [RoomController::class, 'update_room']);
Route::get('/recommend_room', [RoomController::class, 'recommend_room']);
Route::post('/edit_room/{id}', [RoomController::class, 'edit_room']);
Route::get('/room-filter', [RoomController::class, 'showAvailableRoomByCity'])->name('room.filter');
Route::get('/rooms_by_hotel{id}', [RoomController::class, 'roomsByHotel'])->name('rooms.by.hotel');
Route::get('/room-unavailable', [RoomController::class, 'unavailable'])->name('room.unavailable');
Route::get('/no-room', function () {
    return view('home.no_room');
});






route::get('/create_hotel', [HotelController::class, 'create_hotel']);
route::post('/add_hotel', [HotelController::class, 'add_hotel']);
Route::get('/search-hotels', [HotelController::class, 'searchHotels']);
Route::get('/view_hotel', [HotelController::class, 'view_hotel']);
Route::get('/delete_hotel/{id}', [HotelController::class, 'delete_hotel']);
Route::get('/update_hotel/{id}', [HotelController::class, 'update_hotel']);
Route::post('/edit_hotel/{id}', [HotelController::class, 'edit_hotel']);
Route::get('/hotel/{id}/rooms', [HotelController::class, 'viewRooms'])->name('hotel.rooms');
Route::get('/search-hotel', [HotelController::class, 'searchForm'])->name('hotel.search');
Route::get('/all_hotels', [HotelController::class, 'all_hotel'])->name('hotels.list');
Route::get('/hotels/city/{city}', [HotelController::class, 'listByCity'])->name('hotels.by_city');
Route::get('/hotels/{id}', [HotelController::class, 'show'])->name('hotel.show');

//Cart
Route::middleware(['auth'])->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'viewCart'])->name('cart.view');
    Route::post('/add', [CartController::class, 'addtoCart'])->name('cart.add');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/verify', [CartController::class, 'verifyCart'])->name('cart.verify'); //verify trc khi bam dat phong trong cart
    Route::get('/booking/form', [CartController::class, 'bookingwithCart'])->name('cart_booking.form'); //chuyen huong sang form
    Route::post('/booking/temp', [BookingController::class, 'storeTempCart'])->name('cart.store.temp'); //luu data tam thoi
    Route::get('/payment', [BookingController::class, 'showPaymentCart'])->name('payment.page.cart'); //chuyen huong sang trang thanh toan

    Route::get('/booking-history', [BookingController::class, 'history'])->name('booking.history'); //lich su dat phong

});

Route::post('/apply-coupon', [BookingController::class, 'applyCoupon'])->name('booking.applyCoupon');

Route::post('/booking/verify', [BookingController::class, 'verifyRoomBeforeBooking'])->name('booking.verify'); //verify trc khi dat phong
Route::get('/booking_form', [BookingController::class, 'bookingForm'])->name('booking.form'); //form dat phong
Route::post('/bookings/temp', [BookingController::class, 'storeTemp'])->name('bookings.temp'); //luu form vao cache
Route::get('/payment', [BookingController::class, 'showPayment'])->name('payment.page'); //chuyen huong den trang thanh toan
//VNPAY
Route::post('/payment/vnpay', [PaymentController::class, 'payWithVnpay'])->name('payment.vnpay');
Route::get('/payment/vnpay/callback', [PaymentController::class, 'vnpayCallback'])->name('payment.vnpay.callback');
Route::get('/payment/vnpay/callback', function (\Illuminate\Http\Request $request) {
    Log::info('🔥 ĐÃ VÀO ĐƯỢC CALLBACK', $request->all());
    return 'Callback test OK';
});
//VNPAY - CART
Route::post('/cart/payment/vnpay', [PaymentController::class, 'payWithVnpayFromCart'])->name('payment.vnpay.cart');
Route::get('/payment/vnpay/callback/cart', [PaymentController::class, 'vnpayCallbackCart'])->name('payment.vnpay.callback.cart');










Route::get('/payment/success', [PaymentController::class, 'showSuccess'])->name('payment.success');
Route::get('/payment/success/existing', [PaymentController::class, 'showExisting'])->name('payment.success.existing');
