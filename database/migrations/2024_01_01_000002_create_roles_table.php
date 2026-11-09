<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // Direksi, Kepala Divisi, Supervisor, Admin Finance, Keuangan, Karyawan
            $table->string('slug')->unique();  // direksi, kepala-divisi, supervisor, admin-finance, keuangan, karyawan
            $table->unsignedInteger('level')->default(0); // opsional, untuk urutan hirarki umum
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
