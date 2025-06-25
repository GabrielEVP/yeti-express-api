<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("services", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->text("description")->nullable();
            $table->decimal("amount", 10, 2);
            $table->decimal("comision", 10, 2);
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
            $table->timestamps();
        });

        Schema::create("service_events", function (Blueprint $table) {
            $table->id();
            $table->string("event");
            $table->string(column: "section");
            $table->string("reference_table")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable();
            $table->timestamps();
            $table->foreignId("service_id")->constrained()->onDelete("cascade");
        });

        Schema::create("bills", function (Blueprint $table) {
            $table->id();
            $table->foreignId("service_id")->constrained("services")->onDelete("cascade");
            $table->string("name");
            $table->decimal("amount", 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("service_events");
        Schema::dropIfExists("bills");
        Schema::dropIfExists("services");
    }
};
