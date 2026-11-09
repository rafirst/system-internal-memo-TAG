<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('city');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code', 10)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->string('name');
                $table->string('label')->nullable();
                $table->unsignedInteger('level')->default(0);
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('roles', 'label')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('label')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
