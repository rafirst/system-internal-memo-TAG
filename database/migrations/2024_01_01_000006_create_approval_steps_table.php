<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_flow_id')->constrained('approval_flows')->cascadeOnDelete();
            $table->unsignedInteger('step_order'); // 1, 2, 3, ... urutan WAJIB berurutan
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete(); // siapa yang approve di step ini
            $table->string('label')->nullable(); // label tampilan, misal "Persetujuan Kepala Divisi"
            $table->timestamps();

            // satu flow tidak boleh punya step_order ganda
            $table->unique(['approval_flow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
