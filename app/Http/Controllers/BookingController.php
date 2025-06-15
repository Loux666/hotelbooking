<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\RoomAvailability;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use App\Models\Hotel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Services\VNPayPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class BookingController extends Controller

{

    public function showBookingPage(Request $request)
    {
        $room_id = $request->input('room_id');

        // Lấy thông tin phòng kèm hotel và images
        $room = Room::with(['hotel', 'images'])->findOrFail($room_id);

        return view('home.booking_room', [
            'room' => $room,
        ]);
    }



    public function verifyRoomBeforeBooking(Request $request)
    {
        $roomId = $request->input('room_id');
        $checkin = $request->input('checkin');
        $checkout = $request->input('checkout');

        if (!$roomId || !$checkin || !$checkout) {
            return redirect()->back()->with('error', 'Thiếu thông tin!');
        }

        $isAvailable = DB::table('room_availabilities')
            ->where('room_id', $roomId)
            ->where('date', '>=', $checkin)
            ->where('date', '<', $checkout) // ✅ fix: không tính ngày checkout
            ->where(function ($query) {
                $query->where('is_available', 0)
                    ->orWhere('available_rooms', '<', 1);
            })
            ->doesntExist();

        if (!$isAvailable) {
            return view('home.no_room');
        }

        // OK, chuyển đến form booking
        return redirect()->route('booking.form', [
            'room_id' => $roomId,
            'hotel_id' => $request->input('hotel_id'),
            'checkin' => $checkin,
            'checkout' => $checkout
        ]);
    }

    public function bookingform(Request $request)
    {
        $roomId = $request->query('room_id');
        $hotelId = $request->query('hotel_id');
        $checkin = $request->query('checkin');
        $checkout = $request->query('checkout');

        $room = Room::with('hotel')->findOrFail($roomId);
        $hotel = Hotel::findOrFail($hotelId);

        return view('home.booking_room', compact('room', 'hotel', 'checkin', 'checkout'));
    }

    public function applyCoupon(Request $request)
    {
        Log::info('=== [COUPON] Bắt đầu xử lý mã giảm giá ===');

        $request->validate([
            'coupon_code' => 'required|string',
            'total_price' => 'required|numeric|min:0',
        ]);

        $code = trim($request->input('coupon_code'));
        $totalPrice = $request->input('total_price');
        $userId = Auth::id();

        Log::info('[COUPON] Mã nhập vào: ' . $code . ', Tổng tiền: ' . $totalPrice . ', User: ' . $userId);

        // Tìm coupon
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->first();

        if (!$coupon) {
            Log::warning('[COUPON] Không tìm thấy mã hợp lệ.');
            return back()->with('error', 'Mã giảm giá không hợp lệ hoặc đã hết hạn.');
        }

        Log::info('[COUPON] Tìm thấy mã: ' . $coupon->code);

        // Kiểm tra lượt dùng tổng thể
        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            Log::warning('[COUPON] Mã đã hết lượt sử dụng.');
            return back()->with('error', 'Mã giảm giá đã hết lượt sử dụng.');
        }

        // Kiểm tra đơn hàng tối thiểu
        if ($coupon->min_order_price && $totalPrice < $coupon->min_order_price) {
            Log::info('[COUPON] Tổng đơn: ' . $totalPrice . ' | Min yêu cầu: ' . $coupon->min_order_price);

            return back()->with('error', 'Đơn hàng chưa đủ điều kiện để áp dụng mã.');
        }

        // Kiểm tra lượt dùng theo user
        $usage = \App\Models\CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->first();

        if ($coupon->user_limit && $usage && $usage->used_count >= $coupon->user_limit) {
            Log::warning('[COUPON] User đã vượt quá số lần sử dụng.');
            return back()->with('error', 'Bạn đã sử dụng mã này vượt quá giới hạn cho phép.');
        }

        // Tính giảm giá
        $discount = $coupon->type === 'percent'
            ? ($totalPrice * $coupon->value / 100)
            : $coupon->value;

        $discount = min($discount, $totalPrice); // Không được giảm quá total

        Log::info('[COUPON] Giảm giá tính được: ' . $discount);

        // Lưu vào session


        Log::info('[COUPON] Mã đã được lưu vào session');

        return back()
            ->with('success', "Áp dụng mã thành công! Giảm " . number_format($discount) . "đ.")
            ->with('applied_coupon', [
                'code' => $coupon->code,
                'coupon_id' => $coupon->id,
                'discount' => $discount,
            ]);
    }


    //1 room only lưu data vao cache vao chuyen hương den thanh toan0
    public function storeTemp(Request $request)
    {
        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'checkin_date' => 'required|date',
            'checkout_date' => 'required|date|after:checkin_date',
            'hotel_id' => 'required|exists:hotels,id',
            'room_id' => 'required|exists:rooms,id',
            'room_name' => 'required|string',
            'room_price' => 'required|numeric|min:0',
            'nights' => 'required|integer|min:1',
            'total_price' => 'required|numeric|min:0',


        ]);
        $cacheKey = 'booking_' . uniqid();
        $bookingData = $validated;
        $bookingData['user_id'] = Auth::check() ? Auth::id() : null;

        Cache::put($cacheKey, $bookingData, now()->addMinutes(30));
        session(['booking_cache_key' => $cacheKey]);

        return redirect()->route('payment.page');
    }
    public function showPayment(Request $request)
    {
        $cacheKey = session('booking_cache_key');
        $data = Cache::get($cacheKey);

        if (!$data) {
            return redirect()->back()->with('error', 'Thông tin booking không tồn tại.');
        }

        return view('home.paymentpage', compact('data', 'cacheKey'));
    }

    //danh cho viec thanh toan trong cart: lưu data vao cache vao chuyen hương den thanh toan

    public function storeTempCart(Request $request)
    {

        // Test nếu giỏ hàng rỗng

        $validated = $request->validate([
            'fullname' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',

        ]);

        $userId = Auth::id();
        if (!$userId) {
            return redirect()->back()->with('error', 'Bạn cần đăng nhập để tiếp tục.');
        }

        $carts = Cart::with('room')->where('user_id', $userId)->get();

        if ($carts->isEmpty()) {
            return redirect()->back()->with('error', 'Giỏ hàng đang trống.');
        }

        $rooms = [];
        $totalPrice = 0;

        foreach ($carts as $cart) {
            $checkin = \Carbon\Carbon::parse($cart->checkin);
            $checkout = \Carbon\Carbon::parse($cart->checkout);
            $nights = $nights = Carbon::parse($cart->checkin)->diffInDays(Carbon::parse($cart->checkout));

            $roomTotal = $cart->price_at_time * $nights;
            $totalPrice += $roomTotal;

            $rooms[] = [
                'cart_id' => $cart->id, // để xóa đúng cart sau khi thanh toán
                'room_id' => $cart->room->id,
                'hotel_id' => $cart->room->hotel_id,
                'room_name' => $cart->room->room_name,
                'price' => $cart->price_at_time,
                'nights' => $nights,
                'total_price' => $roomTotal,
                'checkin' => $cart->checkin,
                'checkout' => $cart->checkout,
            ];
        }

        $cacheKey = 'booking_cart_' . uniqid('bc_');

        $cacheData = [
            'user_id' => $userId,
            'fullname' => $validated['fullname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],

            'total_price' => $totalPrice,
            'rooms' => $rooms,
        ];

        Cache::put($cacheKey, $cacheData, now()->addMinutes(30));
        session(['booking_cart_cache_key' => $cacheKey]);

        Log::info('Cart booking cache created', ['key' => $cacheKey]);

        return redirect()->route('payment.page.cart', ['cacheKey' => $cacheKey]);
    }

    public function showPaymentCart(Request $request)
    {
        $cacheKey = $request->query('cacheKey') ?? session('booking_cart_cache_key');

        if (!$cacheKey) {
            return redirect()->route('cart.index')->with('error', 'Không tìm thấy mã booking.');
        }

        $data = Cache::get($cacheKey);

        if (!$data) {
            return redirect()->route('cart.index')->with('error', 'Thông tin booking từ giỏ hàng không tồn tại hoặc đã hết hạn.');
        }

        if (empty($data['rooms'])) {
            return redirect()->route('cart.index')->with('error', 'Danh sách phòng trong giỏ hàng đã bị thay đổi. Vui lòng kiểm tra lại.');
        }

        // Xóa khỏi session sau khi lấy xong (tránh nhầm key khi back)
        session()->forget('booking_cart_cache_key');

        return view('home.paymentpageCart', [
            'data' => $data,
            'cacheKey' => $cacheKey,
        ]);
    }
}
