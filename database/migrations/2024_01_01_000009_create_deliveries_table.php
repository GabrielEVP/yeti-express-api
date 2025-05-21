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
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100);
            $table->dateTime('date');
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'cancelled']);
            $table->enum('currency', ['USD', 'BOV', 'OTH']);
            $table->enum('payment_type', ['partial', 'full']);
            $table->decimal('total', 10, 2);
            $table->decimal('comision', 10, 2);
            $table->text('notes')->nullable();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('client_address_id')->constrained('client_addresses')->onDelete('cascade');
            $table->foreignId('courier_id')->constrained('couriers')->onDelete('cascade');
            $table->foreignId('open_box_id')->nullable()->constrained('box')->onDelete('set null');
            $table->foreignId('close_box_id')->nullable()->constrained('box')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};