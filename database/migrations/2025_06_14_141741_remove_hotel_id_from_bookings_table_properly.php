<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'hotel_id')) {
                $table->dropForeign(['hotel_id']); // Xoá khóa ngoại
                $table->dropColumn('hotel_id');    // Rồi xoá cột
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings_table_properly', function (Blueprint $table) {
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
        });
    }
};
