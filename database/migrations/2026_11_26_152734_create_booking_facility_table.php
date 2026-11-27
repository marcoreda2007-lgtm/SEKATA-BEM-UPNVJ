<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_facility', function (Blueprint $table) {
            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();
            $table->foreignId('facility_id')
                ->constrained('facilities')
                ->cascadeOnDelete();

            $table->primary(['booking_id', 'facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_facility');
    }
};