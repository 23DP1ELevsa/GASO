<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->enum('type', ['balonu atskaite', 'klientu atskaite', 'darijumu atskaite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};