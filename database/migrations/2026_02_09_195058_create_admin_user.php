<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class () extends Migration {
    public function up(): void
    {
        $email = config('app.admin_email');
        $password = config('app.admin_password');

        if (! $email || ! $password) {
            return;
        }

        $exists = DB::table('users')->where('email', $email)->exists();

        if (! $exists) {
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $email = config('app.admin_email');

        if ($email) {
            DB::table('users')->where('email', $email)->delete();
        }
    }
};
