<?php

namespace App\Infrastructure\Access;

use App\Domain\Access\SessionTerminator;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Deletes the user's rows in the database session store and invalidates "remember me" (RN-19).
 */
final class DatabaseSessionTerminator implements SessionTerminator
{
    public function endAll(int $userId): void
    {
        DB::table(Config::string('session.table', 'sessions'))->where('user_id', $userId)->delete();

        $user = User::query()->find($userId);
        $user?->forceFill(['remember_token' => Str::random(60)])->save();
    }
}
