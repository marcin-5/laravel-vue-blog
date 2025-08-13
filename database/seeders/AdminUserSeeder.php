<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = (string) config('admin.name');
        $email = (string) config('admin.email');
        $passwordFromConfig = config('admin.password');
        $verify = (bool) config('admin.verify_email');

        if (empty($email)) {
            // Nothing to seed without an email
            Log::warning('AdminUserSeeder: ADMIN_EMAIL not set; admin user not created.');
            return;
        }

        $user = User::query()->where('email', $email)->first();

        // Determine the password to set when creating a new user.
        // If not provided via env, use a reasonable default for dev and log a warning.
        $passwordToSet = $passwordFromConfig;
        if (!$passwordToSet) {
            // Prefer a deterministic but unsafe default only for local/dev environments.
            // In production, set ADMIN_PASSWORD; otherwise a random password is generated and logged.
            if (app()->environment(['local', 'development', 'testing'])) {
                $passwordToSet = 'password';
                Log::warning('AdminUserSeeder: ADMIN_PASSWORD not set; using default "password" for local/dev. Change it ASAP.');
            } else {
                $passwordToSet = Str::random(24);
                Log::warning('AdminUserSeeder: ADMIN_PASSWORD not set in production; generated a random admin password. Consider rotating it.');
            }
        }

        if ($user) {
            // Update the name; update password only if explicitly provided to avoid overwriting unknown passwords.
            $user->name = $name ?: $user->name;
            if ($passwordFromConfig) {
                $user->password = $passwordFromConfig; // Will be hashed by model cast
            }
            if ($verify) {
                $user->email_verified_at = now();
            }
            $user->save();
        } else {
            $attributes = [
                'name' => $name ?: 'Administrator',
                'email' => $email,
                'password' => $passwordToSet, // Will be hashed by model cast
            ];
            if ($verify) {
                $attributes['email_verified_at'] = now();
            }
            User::query()->create($attributes);
        }
    }
}
