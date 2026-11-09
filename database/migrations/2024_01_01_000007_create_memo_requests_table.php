<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('memo_number')->unique(); // nomor memo auto-generate, misal MEMO/2026/09/0001
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // pengaju
            $table->foreignId('memo_type_id')->constrained('memo_types');
            $table->foreignId('approval_flow_id')->constrained('approval_flows'); // snapshot flow yang dipakai
            $table->string('title');
            $table->text('description');
            $table->decimal('estimated_amount', 15, 2)->nullable(); // estimasi biaya, opsional
            $table->unsignedInteger('current_step')->default(1); // step yang sedang berjalan
            $table->enum('status', [
                'draft',        // belum diajukan
                'submitted',    // sudah diajukan, menunggu approval step 1
                'in_progress',  // sedang berjalan di tengah-tengah step
                'revision',     // dikembalikan ke pengaju untuk revisi
                'approved',     // semua step sudah approve
                'rejected',     // ditolak di salah satu step, proses berhenti
            ])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_requests');
    }
};
