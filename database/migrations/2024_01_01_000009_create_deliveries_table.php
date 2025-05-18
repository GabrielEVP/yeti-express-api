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
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('client_address_id')->constrained('client_addresses')->onDelete('cascade');
            $table->foreignId('payment_id')->constrained('payment_types')->onDelete('cascade');
            $table->foreignId('prices_id')->constrained('price_types')->onDelete('cascade');
            $table->foreignId('courier_id')->constrained('couriers')->onDelete('cascade');
            $table->dateTime('delivery_date');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('comision', 10, 2);
            $table->foreignId('open_box_id')->nullable()->constrained('box')->onDelete('set null');
            $table->foreignId('close_box_id')->nullable()->constrained('box')->onDelete('set null');
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'cancelled']);
            $table->text('notes')->nullable();
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