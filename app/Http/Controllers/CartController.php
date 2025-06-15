<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Room;
use App\Models\Booking;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function addtoCart(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'checkin' => 'required|date',
            'checkout' => 'required|date|after:checkin',
        ]);

        $room = Room::findOrFail($request->room_id);

        // Check nếu phòng đó + ngày đó đã tồn tại trong cart (tránh thêm trùng)
        $exists = Cart::where('user_id', Auth::id())
            ->where('room_id', $room->id)
            ->whereDate('checkin', $request->checkin)
            ->whereDate('checkout', $request->checkout)
            ->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Phòng này đã có trong giỏ hàng.');
        }

        // Thêm mới vào cart
        Cart::create([
            'user_id' => Auth::id(),
            'room_id' => $room->id,
            'price_at_time' => $room->price,
            'checkin' => $request->checkin,
            'checkout' => $request->checkout,
            'is_selected' => false, // mặc định chưa chọn để thanh toán
        ]);

        return redirect()->back()->with('success', 'Đã thêm phòng vào giỏ hàng.');
    }


    public function remove($id)
    {
        $item = Cart::where('id', $id)->where('user_id', Auth::id())->firstOrFail();
        $item->delete();

        return redirect()->back()->with('success', 'Đã xoá khỏi giỏ hàng.');
    }


    public function viewCart()
    {
        $userId = Auth::id();
        $today = now()->startOfDay();

        $cartItems = Cart::with(['room.hotel'])
            ->where('user_id', $userId)
            ->get();

        foreach ($cartItems as $item) {
            $checkin = Carbon::parse($item->checkin);
            $checkout = Carbon::parse($item->checkout);

            $item->is_expired = $checkin->lt($today);

            if ($item->is_expired) {
                $item->is_available_now = false;
            } else {
                $unavailable = DB::table('room_availabilities')
                    ->where('room_id', $item->room_id)
                    ->whereBetween('date', [$checkin, $checkout->copy()->subDay()])
                    ->where(function ($q) {
                        $q->where('is_available', 0)
                            ->orWhere('available_rooms', '<', 1);
                    })
                    ->exists();

                $item->is_available_now = !$unavailable;
            }

            $item->price = $item->room->price;
        }
        $bookings = Booking::where('user_id', Auth::id())
            ->with(['booking_details.room.hotel']) // nhớ phải dùng y như này
            ->latest()
            ->get();


        return view('home.cart', compact('cartItems', 'bookings'));
    }

    public function verifyCart(Request $request)
    {
        $userId = Auth::id();
        $today = now()->startOfDay();
        $selectedIds = json_decode($request->input('selected_ids'), true);

        if (empty($selectedIds)) {
            return back()->with('error', 'Bạn chưa chọn phòng nào.');
        }

        $cartItems = Cart::with(['room.hotel'])
            ->where('user_id', $userId)
            ->whereIn('id', $selectedIds)
            ->get();

        $invalidItems = [];

        foreach ($cartItems as $item) {
            $checkin = Carbon::parse($item->checkin);
            $checkout = Carbon::parse($item->checkout);

            if ($checkin->lt($today)) {
                $invalidItems[] = $item;
                continue;
            }

            $unavailable = DB::table('room_availabilities')
                ->where('room_id', $item->room_id)
                ->whereBetween('date', [$checkin, $checkout->copy()->subDay()])
                ->where(function ($q) {
                    $q->where('is_available', 0)
                        ->orWhere('available_rooms', '<', 1);
                })
                ->exists();

            if ($unavailable) {
                $invalidItems[] = $item;
            }
        }

        if (count($invalidItems) > 0) {
            return back()->with('modal_error', 'Một số phòng không còn khả dụng.');
        }

        // Reset tất cả is_selected về false trước khi cập nhật lại
        Cart::where('user_id', $userId)->update(['is_selected' => false]);

        // Cập nhật những cái được chọn thành true
        Cart::where('user_id', $userId)
            ->whereIn('id', $selectedIds)
            ->update(['is_selected' => true]);

        return redirect()->route('cart_booking.form');
    }


    public function bookingwithCart()
    {
        $userId = Auth::id();
        $bookingData = Cart::with('room.hotel')
            ->where('user_id', $userId)
            ->where('is_selected', true)
            ->get();

        if ($bookingData->isEmpty()) {
            return redirect()->route('cart.view')->with('error', 'Không có phòng nào được chọn.');
        }

        $detailedRooms = [];
        $finalTotal = 0;

        foreach ($bookingData as $item) {

            $checkin = Carbon::parse($item->checkin);
            $checkout = Carbon::parse($item->checkout);
            $nights = $checkin->diffInDays($checkout);

            $pricePerNight = $item->price_at_time ?? $item->room->price; // Ưu tiên giá lúc thêm vào giỏ
            $totalPrice = $nights * $pricePerNight;
            $vat = $totalPrice * 0.10;
            $service = 100000;
            $roomTotal = $totalPrice + $vat + $service;

            $finalTotal += $roomTotal;
            Log::info('[CHECK CART ITEM]', [
                'room_id' => $item->room_id,
                'checkin' => $item->checkin,
                'checkout' => $item->checkout,
                'nights' => $nights,

                'price_per_night' => $pricePerNight,
                'total_price' => $totalPrice,
                'vat' => $vat,
                'service' => $service,
                'room_total' => $roomTotal,
            ]);

            $detailedRooms[] = [
                'room' => $item->room,
                'hotel' => $item->room->hotel,
                'checkin' => $item->checkin,
                'checkout' => $item->checkout,
                'nights' => $nights,
                'pricePerNight' => $pricePerNight,
                'totalPrice' => $totalPrice,
                'vat' => $vat,
                'service' => $service,
                'roomTotal' => $roomTotal,
            ];
        }
        Log::info('[RENDERING paymentpage VIEW]');
        return view('home.bookingwithCart', compact('detailedRooms', 'finalTotal'));
    }
}
