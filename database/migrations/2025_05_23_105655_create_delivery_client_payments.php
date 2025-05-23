<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_client_payments', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('method');
            $table->decimal('amount', 10, 2);
            $table->foreignId('delivery_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_client_payments');
    }
};

