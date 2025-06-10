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
        Schema::create("deliveries", function (Blueprint $table) {
            $table->id();
            $table->string("number", 100);
            $table->date("date");
            $table->enum("status", allowed: ["pending", "in_transit", "delivered", "cancelled",]);
            $table->enum("payment_type", ["partial", "full"]);
            $table->enum("payment_status", ["pending", "partially_paid", "paid",]);
            $table->decimal("amount", 10, 2);
            $table->text("notes")->nullable();
            $table->timestamps();
            $table->foreignId("service_id")->constrained("services")->onDelete("cascade");
            $table->foreignId("client_id")->constrained("clients")->onDelete("cascade");
            $table->foreignId("client_address_id")->constrained("client_addresses")->onDelete("cascade");
            $table->foreignId("courier_id")->constrained("couriers")->onDelete("cascade");
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
        });

        Schema::create("delivery_events", function (Blueprint $table) {
            $table->id();
            $table->string("event");
            $table->string(column: "section");
            $table->string("reference_table")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable();
            $table->timestamps();
            $table->foreignId("delivery_id")->constrained()->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("delivery_events");
        Schema::dropIfExists("deliveries");
    }
};
