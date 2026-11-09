<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('memo_approvals') && Schema::hasTable('memos')) {
            Schema::table('memo_approvals', function (Blueprint $table) {
                if (! Schema::hasColumn('memo_approvals', 'memo_id')) {
                    $table->foreignId('memo_id')->nullable()->after('id')->constrained('memos')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('memo_approvals', 'round')) {
                    $table->unsignedTinyInteger('round')->default(1)->after('memo_id');
                }
                if (! Schema::hasColumn('memo_approvals', 'approver_role_id')) {
                    $table->foreignId('approver_role_id')->nullable()->constrained('roles')->nullOnDelete();
                }
                if (! Schema::hasColumn('memo_approvals', 'acted_by')) {
                    $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('memo_approvals', 'comment')) {
                    $table->text('comment')->nullable();
                }
                foreach (['memo_request_id', 'approval_step_id', 'role_id'] as $column) {
                    if (Schema::hasColumn('memo_approvals', $column)) {
                        $table->foreignId($column)->nullable()->change();
                    }
                }
            });

            if (Schema::hasColumn('memo_approvals', 'role_id')) {
                DB::table('memo_approvals')
                    ->whereNull('approver_role_id')
                    ->update(['approver_role_id' => DB::raw('role_id')]);
            }
            if (Schema::hasColumn('memo_approvals', 'approver_id')) {
                DB::table('memo_approvals')
                    ->whereNull('acted_by')
                    ->update(['acted_by' => DB::raw('approver_id')]);
            }

            Schema::table('memo_approvals', function (Blueprint $table) {
                $table->string('status')->default('waiting')->change();
                if (! Schema::hasIndex('memo_approvals', ['memo_id', 'round', 'step_order'])) {
                    $table->unique(['memo_id', 'round', 'step_order']);
                }
            });
        }

        if (Schema::hasTable('memo_attachments') && Schema::hasTable('memos')) {
            Schema::table('memo_attachments', function (Blueprint $table) {
                if (! Schema::hasColumn('memo_attachments', 'memo_id')) {
                    $table->foreignId('memo_id')->nullable()->constrained('memos')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('memo_attachments', 'type')) {
                    $table->string('type')->default('supporting');
                }
                if (! Schema::hasColumn('memo_attachments', 'path')) {
                    $table->string('path')->nullable();
                }
                if (Schema::hasColumn('memo_attachments', 'memo_request_id')) {
                    $table->foreignId('memo_request_id')->nullable()->change();
                }
                if (Schema::hasColumn('memo_attachments', 'file_path')) {
                    $table->string('file_path')->nullable()->change();
                }
            });

            if (Schema::hasColumn('memo_attachments', 'file_path')) {
                DB::table('memo_attachments')
                    ->whereNull('path')
                    ->update(['path' => DB::raw('file_path')]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            (Schema::hasTable('memos') && DB::table('memos')->exists())
            || (Schema::hasTable('memo_approvals') && DB::table('memo_approvals')->whereNotNull('memo_id')->exists())
            || (Schema::hasTable('memo_attachments') && DB::table('memo_attachments')->whereNotNull('memo_id')->exists())
        ) {
            throw new RuntimeException('Cannot roll back memo schema while current-schema memo data exists.');
        }

        if (Schema::hasTable('memo_approvals')) {
            Schema::table('memo_approvals', function (Blueprint $table) {
                foreach (['memo_id', 'approver_role_id', 'acted_by'] as $column) {
                    if (Schema::hasColumn('memo_approvals', $column)) {
                        $table->dropForeign([$column]);
                    }
                }
                if (Schema::hasIndex('memo_approvals', ['memo_id', 'round', 'step_order'])) {
                    $table->dropUnique(['memo_id', 'round', 'step_order']);
                }
                foreach (['memo_id', 'approver_role_id', 'acted_by'] as $column) {
                    if (Schema::hasColumn('memo_approvals', $column)) {
                        $table->dropColumn($column);
                    }
                }
                foreach (['round', 'comment'] as $column) {
                    if (Schema::hasColumn('memo_approvals', $column)) {
                        $table->dropColumn($column);
                    }
                }
                foreach (['memo_request_id', 'approval_step_id', 'role_id'] as $column) {
                    if (Schema::hasColumn('memo_approvals', $column)) {
                        $table->foreignId($column)->nullable(false)->change();
                    }
                }
                $table->enum('status', ['pending', 'approved', 'rejected', 'revision'])
                    ->default('pending')->change();
            });
        }

        if (Schema::hasTable('memo_attachments')) {
            Schema::table('memo_attachments', function (Blueprint $table) {
                if (Schema::hasColumn('memo_attachments', 'memo_id')) {
                    $table->dropConstrainedForeignId('memo_id');
                }
                foreach (['type', 'path'] as $column) {
                    if (Schema::hasColumn('memo_attachments', $column)) {
                        $table->dropColumn($column);
                    }
                }
                if (Schema::hasColumn('memo_attachments', 'memo_request_id')) {
                    $table->foreignId('memo_request_id')->nullable(false)->change();
                }
                if (Schema::hasColumn('memo_attachments', 'file_path')) {
                    $table->string('file_path')->nullable(false)->change();
                }
            });
        }
    }
};
