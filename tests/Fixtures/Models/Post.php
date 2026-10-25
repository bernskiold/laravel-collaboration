<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures\Models;

use Bernskiold\LaravelCollaboration\Concerns\CanBeShared;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Illuminate\Database\Eloquent\Model;

/**
 * A shareable record. It offers the two permissions.
 */
class Post extends Model implements Shareable
{
    use CanBeShared;

    protected $guarded = [];

    public function isOwnedBy(Model $user): bool
    {
        return $this->owner_id !== null && $this->owner_id === $user->getKey();
    }

    public function shareableUrl(): ?string
    {
        return 'https://example.com/posts/'.$this->getKey();
    }
}
