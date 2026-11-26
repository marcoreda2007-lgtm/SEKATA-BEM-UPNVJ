<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('depositor_name', 100);
            $table->string('item_name');
            $table->text('description');
            $table->date('deposited_on');
            $table->enum('status', ['Dititipkan', 'Diambil'])->default('Dititipkan');
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('item_deposit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_deposit_id')->constrained('item_deposits')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_deposit_photos');
        Schema::dropIfExists('item_deposits');
    }
};
