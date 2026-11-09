<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->json('approver_ids')->nullable()->after('memo_date');
        });

        Schema::table('memo_approvals', function (Blueprint $table) {
            $table->foreignId('assigned_approver_id')
                ->nullable()
                ->after('round')
                ->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('memo_approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_approver_id');
        });

        Schema::table('memos', function (Blueprint $table) {
            $table->dropColumn('approver_ids');
        });
    }
};
