<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures;

use Bernskiold\LaravelCollaboration\Support\PeopleDirectory;
use Bernskiold\LaravelCollaboration\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitingPeopleDirectory extends PeopleDirectory
{
    public function canInvite(): bool
    {
        return true;
    }

    public function invite(string $email): ?Model
    {
        if (! str_ends_with($email, '@example.com')) {
            return null;
        }

        return User::query()->firstOrCreate(
            ['email' => $email],
            ['name' => Str::of($email)->before('@')->replace('.', ' ')->title()->toString()],
        );
    }
}
