<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('category', 50); // 'ruangan' (kondisi sekret) dan 'kunci' (pengembalian kunci)
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamp('uploaded_at');
            $table->boolean('requires_replacement')->default(false);
            $table->timestamps();

            $table->unique(['booking_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_proofs');
    }
};
