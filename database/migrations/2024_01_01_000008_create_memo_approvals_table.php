<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_request_id')->constrained('memo_requests')->cascadeOnDelete();
            $table->foreignId('approval_step_id')->constrained('approval_steps');
            $table->unsignedInteger('step_order'); // disalin dari approval_steps biar gampang query
            $table->foreignId('role_id')->constrained('roles'); // role yang berwenang di step ini
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete(); // user yg action
            $table->enum('status', ['pending', 'approved', 'rejected', 'revision'])->default('pending');
            $table->text('notes')->nullable(); // catatan approve/reject/revisi
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->unique(['memo_request_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_approvals');
    }
};
