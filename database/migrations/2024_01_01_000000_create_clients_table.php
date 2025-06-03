<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("clients", function (Blueprint $table) {
            $table->id();
            $table->string("legal_name", 100);
            $table->enum("type", [
                "venezolano",
                "foreing",
                "legal",
                "commune",
                "government",
                "pasaport",
                "personal_signature",
            ]);
            $table->string("registration_number", 50);
            $table->text("notes")->nullable();
            $table->boolean("allow_credit")->default(false);
            $table->timestamps();
            $table->foreignId("user_id")->constrained("users")->onDelete("cascade");
        });

        Schema::create("client_events", function (Blueprint $table) {
            $table->id();
            $table->string("event");
            $table->string("section");
            $table->string("reference_table")->nullable();
            $table->unsignedBigInteger("reference_id")->nullable();
            $table->timestamps();
            $table->foreignId("client_id")->constrained()->onDelete("cascade");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("client_events");
        Schema::dropIfExists("clients");
    }
};

