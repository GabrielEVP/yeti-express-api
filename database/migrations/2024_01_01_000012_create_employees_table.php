<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('password', 255);
            $table->enum('role', ['admin', 'basic']);
            $table->boolean('active')->default(true);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('employee_events', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('reference_table')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};