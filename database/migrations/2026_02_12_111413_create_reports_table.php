<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reports_statement', function (Blueprint $table) {
            $table->id();
            $table->text('statement');             // user statement only
            $table->string('locale', 16)->default('en_PH'); // optional, for PH/Taglish
            $table->timestamps();

            // Useful for searching text faster later (optional)
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
