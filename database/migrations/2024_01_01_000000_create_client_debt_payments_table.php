<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_debt_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->dateTime('paid_at');
            $table->enum('method', ['mobile_payment', 'transfer', 'cash']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_debt_payments');
    }
};
