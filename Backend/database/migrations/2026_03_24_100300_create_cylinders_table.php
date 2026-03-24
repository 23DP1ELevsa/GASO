<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cylinders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('status_id')->constrained()->restrictOnDelete();
            $table->string('serial_number', 50)->unique();
            $table->decimal('capacity', 10, 2);
            $table->date('manufacture_date');
            $table->date('inspection_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cylinders');
    }
};