<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('activity_description');
            $table->string('pic_name');
            $table->string('pic_division');
            $table->enum('status', [
                'Menunggu',
                'Disetujui',
                'Ditolak',
                'Periksa Bukti',
                'Perlu Perbaikan',
                'Selesai',
            ])->default('Menunggu');
            $table->text('decision_reason')->nullable();
            $table->text('inspection_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};