<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('approval_flows')) {
            Schema::create('approval_flows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('memo_type_id')->nullable()->constrained('memo_types')->cascadeOnDelete();
                $table->string('name');
                $table->foreignId('submitter_role_id')->nullable()->constrained('roles')->nullOnDelete();
                $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('approval_flows', function (Blueprint $table) {
                if (Schema::hasColumn('approval_flows', 'memo_type_id')) {
                    $table->foreignId('memo_type_id')->nullable()->change();
                }
                if (! Schema::hasColumn('approval_flows', 'submitter_role_id')) {
                    $table->foreignId('submitter_role_id')->nullable()->constrained('roles')->nullOnDelete();
                }
                if (! Schema::hasColumn('approval_flows', 'division_id')) {
                    $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
                }
            });
        }

        if (! Schema::hasTable('approval_flow_steps')) {
            Schema::create('approval_flow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_flow_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('step_order');
                $table->foreignId('approver_role_id')->constrained('roles');
                $table->timestamps();
                $table->unique(['approval_flow_id', 'step_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_flow_steps');
    }
};
