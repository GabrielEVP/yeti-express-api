<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('delivery_anonymous_clients', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name', 100);
            $table->enum("type", [
                "venezolano",
                "foreing",
                "legal",
                "commune",
                "government",
                "pasaport",
                "personal_signature",
            ]);
            $table->string('registration_number', 50);
            $table->string('phone', 20);
            $table->foreignId("delivery_id")->constrained("deliveries")->onDelete("cascade");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anonymous_clients');
    }
};
