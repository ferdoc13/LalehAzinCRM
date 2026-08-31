<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BlockUser
{
    public function block(User $user, User $actor): void
    {
        abort_unless($actor->isNot($user), 403);

        $user->update([
            'blocked_at' => now(),
            'remember_token' => Str::random(60),
        ]);

        $this->invalidateSessions($user);
    }

    public function unblock(User $user): void
    {
        $user->update([
            'blocked_at' => null,
        ]);
    }

    private function invalidateSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $table = config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->where('user_id', $user->id)->delete();
    }
}
