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
        $usedUsernames = [];
        $usedEmails = [];
        $updates = [];

        $users = DB::table('users')->orderBy('id')->get(['id', 'email']);

        foreach ($users as $user) {
            $localPart = is_string($user->email) && str_contains($user->email, '@')
                ? strstr($user->email, '@', true)
                : '';
            $username = (string) preg_replace('/[^a-z0-9._]/', '', strtolower($localPart));
            $username = substr($username !== '' ? $username : "user{$user->id}", 0, 50);

            if (strlen($username) < 3) {
                $username = substr($username.'.'.$user->id, 0, 50);
            }

            $baseUsername = $username;
            $suffix = 1;

            while (isset($usedUsernames[$username])) {
                $suffix++;
                $ending = '.'.$suffix;
                $username = substr($baseUsername, 0, 50 - strlen($ending)).$ending;
            }

            $usedUsernames[$username] = true;
            $hasEmail = is_string($user->email) && $user->email !== '';
            $email = $hasEmail ? $user->email : "user{$user->id}@example.invalid";

            if (! $hasEmail) {
                $emailSuffix = 1;
                while (
                    isset($usedEmails[strtolower($email)])
                    || DB::table('users')
                        ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                        ->where('id', '!=', $user->id)
                        ->exists()
                ) {
                    $email = "user{$user->id}.{$emailSuffix}@example.invalid";
                    $emailSuffix++;
                }
            }

            $usedEmails[strtolower($email)] = true;
            $updates[$user->id] = [
                'username' => $username,
                'email' => $email,
            ];
        }

        foreach (array_keys($updates) as $userId) {
            $temporaryUsername = "__username_migration_{$userId}__";
            while (DB::table('users')->where('username', $temporaryUsername)->where('id', '!=', $userId)->exists()) {
                $temporaryUsername .= '_';
            }

            DB::table('users')->where('id', $userId)->update(['username' => $temporaryUsername]);
        }

        foreach ($updates as $userId => $values) {
            DB::table('users')->where('id', $userId)->update($values);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable(false)->change();
            $table->string('email')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->change();
            $table->string('email')->nullable()->change();
        });
    }
};
