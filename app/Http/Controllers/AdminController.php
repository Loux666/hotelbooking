<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\RoomImage;

class AdminController extends Controller
{
    public function index()
    {
        if (Auth::id()) {
            $usertype = Auth::user()->usertype;
            if ($usertype == 'user') {
                return $this->home();
            } else if ($usertype == 'admin') {
                return view('admin.index');
            } else {
                return redirect()->back();
            }
        }
    }

    public function home()
    {
        $cities = ['Hà Nội', 'Vũng Tàu', 'Đà Lạt', 'Hạ Long', 'Hồ Chí Minh', 'Đà Năng'];
        $rooms = collect();

        foreach ($cities as $city) {
            $room = Room::whereHas('hotel', function ($q) use ($city) {
                $q->where('hotel_city', $city);
            })
                ->with(['hotel', 'firstImage']) // Load cả hotel và ảnh đầu tiên
                ->inRandomOrder()
                ->first();

            if ($room) {
                $rooms->push($room);
            }
        }
        return view('home.index', compact('rooms'));
    }

    public function about()
    {
        return view('home.about');
    }

    public function room()
    {
        return view('home.room_list');
    }
    public function cart()
    {
        return view('home.cart');
    }
    public function coupon()
    {
        return view('home.coupon');
    }
    public function liveSearch(Request $request)
    {
        $query = $request->get('query');

        $results = Hotel::where('hotel_name', 'like', '%' . $query . '%')
            ->orWhere('hotel_address', 'like', '%' . $query . '%')
            ->orWhere('hotel_city', 'like', '%' . $query . '%')
            ->limit(5)
            ->get(['hotel_name', 'hotel_address', 'hotel_city', 'hotel_image']);

        return response()->json($results);
    }
}
