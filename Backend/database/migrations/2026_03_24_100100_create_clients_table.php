<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('surname', 50);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->string('phone', 20)->nullable();
            $table->string('street', 100);
            $table->unsignedInteger('home_number');
            $table->unsignedInteger('flat_number')->nullable();
            $table->string('city', 50);
            $table->string('zip_code', 20);
            $table->string('username', 50)->unique();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};