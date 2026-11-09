<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $t->foreignId('role_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'division_id')) {
                $t->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'branch_id')) {
                $t->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'email')) {
                $t->string('email')->nullable()->unique();
            }
            if (! Schema::hasColumn('users', 'jabatan')) {
                $t->string('jabatan')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $t->boolean('is_active')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach (['branch_id', 'email'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if ($column === 'branch_id') {
                        $t->dropConstrainedForeignId($column);
                    } else {
                        $t->dropUnique(['email']);
                        $t->dropColumn($column);
                    }
                }
            }
        });
    }
};
