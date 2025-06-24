<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create("company_bills", function (Blueprint $table) {
            $table->id();
            $table->date("date");
            $table->string("name");
            $table->text("description");
            $table->enum("method", ["cash", "mobile_payment", "bank_transfered",]);
            $table->decimal("amount", 10, 2);
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("company_bills");
    }
};
