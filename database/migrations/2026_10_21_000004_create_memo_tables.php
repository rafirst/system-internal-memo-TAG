<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('memo_sequences')) {
            Schema::create('memo_sequences', function (Blueprint $t) {
                $t->id();
                $t->foreignId('division_id')->constrained();
                $t->unsignedSmallInteger('year');
                $t->unsignedInteger('last_number')->default(0);
                $t->timestamps();

                $t->unique(['division_id', 'year']);
            });
        }

        if (! Schema::hasTable('memos')) {
            Schema::create('memos', function (Blueprint $t) {
                $t->id();
                $t->string('memo_number')->nullable()->unique(); // dibuat saat pertama kali submit
                $t->foreignId('submitter_id')->constrained('users');       // staff yang menginput
                $t->foreignId('on_behalf_of_id')->nullable()->constrained('users'); // Spv yang tercetak di "Hormat saya"
                $t->foreignId('division_id')->constrained();
                $t->foreignId('branch_id')->constrained();

                $t->string('subject');                  // "Hal"
                $t->text('opening_paragraph');
                $t->decimal('total_amount', 15, 2)->default(0);

                $t->string('bank_name')->nullable();
                $t->string('account_number')->nullable();
                $t->string('account_holder')->nullable();

                $t->date('memo_date');
                $t->string('status')->default('draft')->index();
                $t->unsignedTinyInteger('current_step')->nullable();
                $t->unsignedTinyInteger('round')->default(1);

                $t->timestamp('submitted_at')->nullable();
                $t->timestamp('approved_at')->nullable();
                $t->timestamp('disbursed_at')->nullable();
                $t->foreignId('disbursed_by')->nullable()->constrained('users');
                $t->timestamp('completed_at')->nullable();
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('memo_items')) {
            Schema::create('memo_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('memo_id')->constrained()->cascadeOnDelete();
                $t->string('name');
                $t->unsignedInteger('qty')->nullable();     // boleh kosong (tercetak "-")
                $t->decimal('unit_price', 15, 2);
                $t->decimal('subtotal', 15, 2);
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('memo_attachments')) {
            Schema::create('memo_attachments', function (Blueprint $t) {
                $t->id();
                $t->foreignId('memo_id')->constrained()->cascadeOnDelete();
                $t->string('type')->default('supporting');  // supporting | realization
                $t->string('original_name');
                $t->string('path');
                $t->foreignId('uploaded_by')->constrained('users');
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('memo_approvals')) {
            Schema::create('memo_approvals', function (Blueprint $t) {
                $t->id();
                $t->foreignId('memo_id')->constrained()->cascadeOnDelete();
                $t->unsignedTinyInteger('round');
                $t->unsignedTinyInteger('step_order');
                $t->foreignId('approver_role_id')->constrained('roles');
                $t->string('status')->default('waiting');   // waiting|pending|approved|denied
                $t->foreignId('acted_by')->nullable()->constrained('users');
                $t->text('comment')->nullable();            // wajib saat denied (divalidasi di aplikasi)
                $t->timestamp('acted_at')->nullable();
                $t->timestamps();

                $t->unique(['memo_id', 'round', 'step_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_items');
        Schema::dropIfExists('memos');
        Schema::dropIfExists('memo_sequences');
    }
};
