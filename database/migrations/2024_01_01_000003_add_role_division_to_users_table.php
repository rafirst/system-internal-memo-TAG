<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'division_id')) {
                $table->foreignId('division_id')->nullable()->after('role_id')->constrained('divisions')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['role_id', 'division_id'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropConstrainedForeignId($column);
                }
            }
            foreach (['jabatan', 'is_active'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
