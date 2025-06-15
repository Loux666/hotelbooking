<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingSuccessMail;
use App\Mail\BookingCartSuccessMail;
use App\Models\RoomAvailability;
use App\Services\VNPayPayment;

class PaymentController extends Controller
{
    public function payWithVnpay(Request $request)
    {
        $cacheKey = $request->input('cache_key');
        $booking = Cache::get($cacheKey);

        if (!$booking) {
            return redirect()->back()->with('error', 'Không tìm thấy thông tin booking');
        }

        $orderId = $cacheKey; // Dùng cache key làm TxnRef
        $inputData["vnp_TxnRef"] = $orderId;
        $amount = $booking['total_price'];

        $vnp_TmnCode = config('services.vnpay.tmn_code');
        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $vnp_Url = config('services.vnpay.url');
        $vnp_Returnurl = "https://893c-2405-4802-1c60-4f60-579-da2b-ae50-c070.ngrok-free.app/payment/vnpay/callback";
        Log::info('Redirecting to VNPAY: ' . $vnp_Url);

        $inputData = [
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $amount * 100,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => now()->format('YmdHis'),
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $request->ip(),
            "vnp_Locale" => "vn",
            "vnp_OrderInfo" => "Thanh toán đơn hàng",
            "vnp_OrderType" => "billpayment",
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $orderId,
        ];

        ksort($inputData);
        $hashdata = '';
        foreach ($inputData as $key => $value) {
            $hashdata .= ($hashdata ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        }
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
        $vnp_Url .= "?" . http_build_query($inputData) . '&vnp_SecureHash=' . $vnpSecureHash;

        return redirect($vnp_Url);
    }


    public function vnpayCallback(Request $request)
    {
        Log::info('=== BẮT ĐẦU VNPAY CALLBACK ===');

        $inputData = $request->all();
        Log::info('1. Data nhận được từ VNPAY:', $inputData);

        $vnp_HashSecret = config('services.vnpay.hash_secret');
        $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

        Log::info('2. Hash Secret có tồn tại:', ['exists' => !empty($vnp_HashSecret)]);

        // Kiểm tra key bắt buộc
        if (!isset($inputData['vnp_TxnRef'], $inputData['vnp_ResponseCode'])) {
            Log::error('3. THIẾU DỮ LIỆU BẮT BUỘC');
            return view('home.payment_failed', ['message' => 'Thiếu dữ liệu cần thiết từ VNPAY.']);
        }
        Log::info('3. Dữ liệu bắt buộc OK');

        // Lấy cache key
        $cacheKey = $inputData['vnp_TxnRef'];
        $booking = Cache::get($cacheKey);

        Log::info('4. Cache check:', [
            'cache_key' => $cacheKey,
            'booking_exists' => !empty($booking),
            'booking_data' => $booking
        ]);

        // Xác minh chữ ký - SỬA LẠI ĐỒNG BỘ VỚI payWithVnpay
        $tempData = $inputData;
        unset($tempData['vnp_SecureHash'], $tempData['vnp_SecureHashType']);
        ksort($tempData);

        // DÙNG CÙNG CÁCH VỚI payWithVnpay
        $hashdata = '';
        foreach ($tempData as $key => $value) {
            $hashdata .= ($hashdata ? '&' : '') . urlencode($key) . '=' . urlencode($value);
        }
        $secureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);

        Log::info('5. Xác minh chữ ký:', [
            'hash_data' => $hashdata,
            'computed_hash' => $secureHash,
            'received_hash' => $vnp_SecureHash,
            'is_valid' => $secureHash === $vnp_SecureHash
        ]);

        if ($secureHash !== $vnp_SecureHash) {
            Log::error('6. CHỮ KÝ KHÔNG HỢP LỆ');
            return view('home.payment_failed', ['message' => 'Chữ ký không hợp lệ']);
        }
        Log::info('6. Chữ ký hợp lệ');

        // Kiểm tra response code
        Log::info('7. Response code check:', [
            'code' => $inputData['vnp_ResponseCode'],
            'is_success' => $inputData['vnp_ResponseCode'] === '00'
        ]);

        if ($inputData['vnp_ResponseCode'] !== '00') {
            Log::error('8. GIAO DỊCH KHÔNG THÀNH CÔNG', ['code' => $inputData['vnp_ResponseCode']]);
            return view('home.payment_failed', ['message' => 'Giao dịch không thành công']);
        }
        Log::info('8. Giao dịch thành công');

        // Kiểm tra booking cache
        if (!$booking) {
            Log::error('9. KHÔNG TÌM THẤY BOOKING TRONG CACHE');
            return view('home.payment_failed', ['message' => 'Không tìm thấy dữ liệu booking từ cache.']);
        }
        Log::info('9. Booking cache OK');

        // Kiểm tra duplicate
        if (\App\Models\Payment::where('txn_ref', $cacheKey)->exists()) {
            Log::info('10. GIAO DỊCH ĐÃ TỒN TẠI - REDIRECT');
            return redirect()->route('payment.success.existing', ['txn_ref' => $cacheKey]);
        }
        Log::info('10. Giao dịch mới');

        Log::info('11. BẮT ĐẦU LƯU DATABASE');
        DB::beginTransaction();
        try {
            // Tạo booking
            Log::info('11a. Tạo booking...');
            $bookingModel = \App\Models\Booking::create([
                'user_id' => Auth::id(),
                'hotel_id' => $booking['hotel_id'],
                'guest_name' => $booking['fullname'],
                'guest_email' => $booking['email'],
                'guest_phone' => $booking['phone'],

                'number_of_guests' => $booking['number_of_guests'] ?? 1,
                'total_price' => $booking['total_price'],
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);
            Log::info('11a. Booking tạo thành công', ['id' => $bookingModel->id]);

            // Tạo booking detail
            Log::info('11b. Tạo booking detail...');
            \App\Models\BookingDetail::create([
                'booking_id' => $bookingModel->id,
                'room_id' => $booking['room_id'],
                'hotel_id' => $booking['hotel_id'],
                'room_name' => $booking['room_name'],
                'price_per_night' => $booking['room_price'],
                'nights' => $booking['nights'],
                'quantity' => 1,
                'subtotal' => $booking['total_price'],
                'checkin' => $booking['checkin_date'],
                'checkout' => $booking['checkout_date'],
            ]);
            Log::info('11b. Booking detail tạo thành công');

            // 11c. Cập nhật room_availability
            Log::info('11c. Cập nhật room_availability...');
            $start = \Carbon\Carbon::parse($booking['checkin_date']);
            $end = \Carbon\Carbon::parse($booking['checkout_date']);

            for ($date = $start->copy(); $date->lt($end); $date->addDay()) {
                \App\Models\RoomAvailability::where('room_id', $booking['room_id'])
                    ->where('date', $date->toDateString())
                    ->decrement('available_rooms', 1);
            }
            Log::info('11c. Cập nhật room_availability hoàn tất');

            // Parse ngày giờ thanh toán
            try {
                $paidAt = \Carbon\Carbon::createFromFormat('YmdHis', $inputData['vnp_PayDate']);
                Log::info('11c. Parse date thành công', ['date' => $paidAt]);
            } catch (\Exception $e) {
                Log::warning('11c. Parse date lỗi, dùng fallback', ['error' => $e->getMessage()]);
                $paidAt = now();
            }

            // Tạo payment record
            Log::info('11d. Tạo payment record...');
            \App\Models\Payment::create([
                'booking_id' => $bookingModel->id,
                'txn_ref' => $inputData['vnp_TxnRef'] ?? '',
                'transaction_no' => $inputData['vnp_TransactionNo'] ?? '',
                'bank_code' => $inputData['vnp_BankCode'] ?? '',
                'card_type' => $inputData['vnp_CardType'] ?? '',
                'amount' => ($inputData['vnp_Amount'] ?? 0) / 100,
                'payment_gateway' => 'vnpay',
                'status' => 'success',
                'paid_at' => $paidAt,
            ]);
            Log::info('11d. Payment record tạo thành công');

            Log::info('11e. Xử lí coupon');
            $couponData = session('applied_coupon');

            if ($couponData) {
                Log::info('11e. Ghi nhận mã giảm giá đã dùng', $couponData);

                \App\Models\CouponUsage::updateOrCreate(
                    ['coupon_id' => $couponData['coupon_id'], 'user_id' => Auth::id()],
                    ['used_count' => DB::raw('used_count + 1')]
                );

                \App\Models\Coupon::where('id', $couponData['coupon_id'])->increment('used_count');

                // Xóa coupon khỏi session
                session()->forget('applied_coupon');
            }
            Log::info('11e. Coupon xử lí thành công');

            DB::commit();
            Log::info('12. DATABASE COMMIT THÀNH CÔNG');

            // Gửi email
            try {
                Log::info('13. Gửi email...');
                $bookingWithDetails = $bookingModel->load('booking_details.hotel');
                Mail::to($booking['email'])->send(new BookingSuccessMail($bookingWithDetails));
                Log::info('13. Email gửi thành công');
            } catch (\Exception $e) {
                Log::error('13. Lỗi gửi email: ' . $e->getMessage());
            }

            Cache::forget($cacheKey);
            Log::info('14. Xóa cache thành công');

            Log::info('15. REDIRECT ĐẾN SUCCESS PAGE', ['booking_id' => $bookingModel->id]);
            return redirect()->route('payment.success', ['booking_id' => $bookingModel->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('LỖI XỬ LÝ GIAO DỊCH:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return view('home.payment_failed', ['message' => 'Lỗi xử lý giao dịch: ' . $e->getMessage()]);
        }

        Log::info('=== KẾT THÚC VNPAY CALLBACK ===');
    }
    //Cart payment
    // public function payWithVnpayFromCart(Request $request)
    // {
    //     $cacheKey = $request->input('cache_key');
    //     $booking = Cache::get($cacheKey);

    //     if (!$booking) {
    //         return redirect()->back()->with('error', 'Không tìm thấy thông tin booking.');
    //     }

    //     $vnp_TmnCode = config('services.vnpay.tmn_code');
    //     $vnp_HashSecret = config('services.vnpay.hash_secret');
    //     $vnp_Url = config('services.vnpay.url_cart');
    //     $vnp_Returnurl = route('payment.vnpay.callback.cart');

    //     if (!$vnp_TmnCode || !$vnp_HashSecret || !$vnp_Url) {
    //         return redirect()->back()->with('error', 'Cấu hình cổng thanh toán chưa đầy đủ.');
    //     }

    //     $orderId = $cacheKey;
    //     $amount = (int) $booking['total_price'];

    //     $inputData = [
    //         "vnp_Version" => "2.1.0",
    //         "vnp_TmnCode" => $vnp_TmnCode,
    //         "vnp_Amount" => $amount * 100,
    //         "vnp_Command" => "pay",
    //         "vnp_CreateDate" => now()->format('YmdHis'),
    //         "vnp_CurrCode" => "VND",
    //         "vnp_IpAddr" => $request->ip(),
    //         "vnp_Locale" => "vn",
    //         "vnp_OrderInfo" => "Thanh toán giỏ hàng",
    //         "vnp_OrderType" => "billpayment",
    //         "vnp_ReturnUrl" => $vnp_Returnurl,
    //         "vnp_TxnRef" => $orderId,
    //     ];

    //     ksort($inputData);
    //     $hashdata = '';
    //     foreach ($inputData as $key => $value) {
    //         $hashdata .= ($hashdata ? '&' : '') . urlencode($key) . '=' . urlencode($value);
    //     }

    //     $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnp_HashSecret);
    //     $vnp_Url .= '?' . http_build_query($inputData) . '&vnp_SecureHash=' . $vnpSecureHash;

    //     Log::info('Redirecting to VNPAY from cart', [
    //         'user_id' => Auth::id(),
    //         'cache_key' => $cacheKey,
    //         'amount' => $amount,
    //         'redirect_url' => $vnp_Url,
    //     ]);

    //     return redirect($vnp_Url);
    // }

    // public function vnpayCallbackCart(Request $request)
    // {
    //     Log::info('=== [CART] VNPAY CALLBACK START ===');

    //     $inputData = $request->all();
    //     Log::info('[CART] Dữ liệu nhận được từ VNPAY:', $inputData);

    //     $vnp_HashSecret = config('services.vnpay.hash_secret');
    //     $vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';

    //     if (!isset($inputData['vnp_TxnRef'], $inputData['vnp_ResponseCode'])) {
    //         return view('home.payment_failed', ['message' => 'Thiếu dữ liệu cần thiết từ VNPAY.']);
    //     }

    //     $cacheKey = $inputData['vnp_TxnRef'];
    //     $booking = Cache::get($cacheKey);

    //     if (!$booking) {
    //         return view('home.payment_failed', ['message' => 'Thông tin booking từ giỏ hàng đã hết hạn.']);
    //     }

    //     Log::info('[CART] Booking data từ cache:', $booking);

    //     // Xác thực chữ ký
    //     $tempData = $inputData;
    //     unset($tempData['vnp_SecureHash'], $tempData['vnp_SecureHashType']);
    //     ksort($tempData);
    //     $hashData = http_build_query($tempData, '', '&');
    //     $secureHash = hash_hmac('sha512', $hashData, $vnp_HashSecret);

    //     if ($secureHash !== $vnp_SecureHash) {
    //         return view('home.payment_failed', ['message' => 'Chữ ký không hợp lệ']);
    //     }

    //     if ($inputData['vnp_ResponseCode'] !== '00') {
    //         return view('home.payment_failed', ['message' => 'Giao dịch không thành công']);
    //     }

    //     // Kiểm tra duplicate
    //     if (\App\Models\Payment::where('txn_ref', $cacheKey)->exists()) {
    //         return redirect()->route('payment.success.existing', ['txn_ref' => $cacheKey]);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         // Lấy min-max ngày trong danh sách phòng
    //         $checkin_date = collect($booking['rooms'])->min('checkin');
    //         $checkout_date = collect($booking['rooms'])->max('checkout');

    //         Log::info('[CART] Bắt đầu lưu dữ liệu booking...');

    //         $bookingModel = \App\Models\Booking::create([
    //             'user_id' => $booking['user_id'],
    //             'guest_name' => $booking['fullname'],
    //             'guest_email' => $booking['email'],
    //             'guest_phone' => $booking['phone'],

    //             'number_of_guests' => 1,
    //             'total_price' => $booking['total_price'],
    //             'status' => 'confirmed',
    //             'payment_status' => 'paid',
    //         ]);

    //         Log::info('[CART] Booking đã được tạo:', ['id' => $bookingModel->id]);

    //         foreach ($booking['rooms'] as $room) {
    //             $roomModel = \App\Models\Room::with('hotel')->find($room['room_id']);

    //             \App\Models\BookingDetail::create([
    //                 'booking_id' => $bookingModel->id,
    //                 'room_id' => $room['room_id'],
    //                 'room_name' => $room['room_name'],
    //                 'hotel_id' => $room['hotel_id'],
    //                 'price_per_night' => $room['price'],
    //                 'nights' => $room['nights'],
    //                 'quantity' => 1,
    //                 'subtotal' => $room['total_price'],
    //                 'checkin' => $room['checkin'],
    //                 'checkout' => $room['checkout'],
    //             ]);

    //             Log::info('[CART] BookingDetail đã tạo:', $room);

    //             // Trừ phòng theo ngày riêng
    //             $start = \Carbon\Carbon::parse($room['checkin']);
    //             $end = \Carbon\Carbon::parse($room['checkout']);
    //             for ($date = $start->copy(); $date->lt($end); $date->addDay()) {
    //                 \App\Models\RoomAvailability::where('room_id', $room['room_id'])
    //                     ->where('date', $date->toDateString())
    //                     ->decrement('available_rooms', 1);
    //             }
    //         }

    //         // Parse ngày thanh toán
    //         try {
    //             $paidAt = \Carbon\Carbon::createFromFormat('YmdHis', $inputData['vnp_PayDate']);
    //         } catch (\Exception $e) {
    //             $paidAt = now();
    //         }

    //         \App\Models\Payment::create([
    //             'booking_id' => $bookingModel->id,
    //             'txn_ref' => $inputData['vnp_TxnRef'],
    //             'transaction_no' => $inputData['vnp_TransactionNo'] ?? '',
    //             'bank_code' => $inputData['vnp_BankCode'] ?? '',
    //             'card_type' => $inputData['vnp_CardType'] ?? '',
    //             'amount' => ($inputData['vnp_Amount'] ?? 0) / 100,
    //             'payment_gateway' => 'vnpay',
    //             'status' => 'success',
    //             'paid_at' => $paidAt,
    //         ]);

    //         // Xoá giỏ hàng + cache
    //         \App\Models\Cart::where('user_id', $booking['user_id'])->delete();
    //         Cache::forget($cacheKey);

    //         DB::commit();

    //         try {
    //             $bookingWithDetails = $bookingModel->load('booking_details.room.hotel');
    //             Mail::to($booking['email'])->send(new BookingCartSuccessMail($bookingWithDetails));

    //             Log::info('[CART] Đã gửi mail xác nhận booking');
    //         } catch (\Exception $e) {
    //             Log::error('[CART] Lỗi gửi mail: ' . $e->getMessage());
    //         }

    //         return redirect()->route('payment.success', ['booking_id' => $bookingModel->id]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('[CART] Lỗi xử lý callback: ' . $e->getMessage());
    //         return view('home.payment_failed', ['message' => 'Lỗi xử lý giao dịch: ' . $e->getMessage()]);
    //     }
    // }
























    public function showSuccess(Request $request)
    {

        $booking = \App\Models\Booking::with('payments')->find($request->booking_id);

        if (!$booking) {
            return view('home.payment_failed', ['message' => 'Không tìm thấy thông tin booking']);
        }

        return view('home.payment_success', [
            'booking' => $booking,
            'payment' => $booking->payments,
        ]);
    }

    public function showExisting(Request $request)
    {
        $txnRef = $request->txn_ref;


        $payment = \App\Models\Payment::where('txn_ref', $txnRef)->with('booking')->first();

        if (!$payment || !$payment->booking) {
            return view('home.payment_failed', ['message' => 'Không tìm thấy thông tin giao dịch.']);
        }

        return view('home.payment_success', [
            'booking' => $payment->booking,
            'payment' => $payment,
        ]);
    }
}
