<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\RoomAvailability;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class RoomAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $start = Carbon::today();
        $end = Carbon::today()->addMonths(6);
        $dates = CarbonPeriod::create($start, $end);

        // Chỉ lấy các phòng có id từ 40 đến 66
        $rooms = Room::whereBetween('id', [40, 66])->get();

        foreach ($rooms as $room) {
            foreach ($dates as $date) {
                RoomAvailability::updateOrCreate(
                    [
                        'room_id' => $room->id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'available_rooms' => $room->total_rooms ?? 1, // nếu null thì lấy 1
                        'is_available' => true,
                        'price_override' => null,
                    ]
                );
            }
        }

        echo "✅ Đã thêm dữ liệu phòng từ ID 40 đến 66 cho 6 tháng tới.\n";
    }
}
