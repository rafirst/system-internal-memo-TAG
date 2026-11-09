<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_type_id')->constrained('memo_types')->cascadeOnDelete();
            $table->string('name'); // misal "Flow ATK Standar"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flows');
    }
};
