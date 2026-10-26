<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures\Models;

use Bernskiold\LaravelCollaboration\Concerns\CanBeShared;
use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Illuminate\Database\Eloquent\Model;

/**
 * A shareable record. It offers the two permissions. Its owner is in the
 * owner_id column.
 */
class Post extends Model implements Shareable
{
    use CanBeShared;

    protected $guarded = [];

    public function shareOwnerColumn(): ?string
    {
        return 'owner_id';
    }

    public function shareableUrl(): ?string
    {
        return 'https://example.com/posts/'.$this->getKey();
    }
}
