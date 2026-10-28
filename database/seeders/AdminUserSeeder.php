<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

/**
 * Creates the first administrator from config/admin.php (RN-17). It never promotes an
 * existing regular account: anyone who changed their email to ADMIN_EMAIL would become admin.
 */
class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = Str::lower(trim(Config::string('admin.email')));
        $password = Config::string('admin.password');

        if ($email === '') {
            throw new RuntimeException('Set ADMIN_EMAIL in .env to create the administrator.');
        }

        Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['email'], 'password' => ['nullable', Password::default()]],
        )->validate();

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null && ! $existing->is_admin) {
            throw new RuntimeException("{$email} already belongs to a regular account; promote it manually after checking ownership.");
        }

        if ($existing !== null) {
            $this->command->info("Administrator already exists: {$email}");

            return;
        }

        $admin = new User;
        $admin->forceFill([
            'name' => Config::string('admin.name'),
            'email' => $email,
            // Without ADMIN_PASSWORD nobody knows the password: the administrator sets one via "forgot password".
            'password' => $password !== '' ? $password : Str::password(64),
            'is_admin' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->command->info("Administrator created: {$email}");
    }
}
