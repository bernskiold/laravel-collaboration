<?php

namespace Bernskiold\LaravelCollaboration\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This model is not shareable.
 */
class Plain extends Model
{
    protected $table = 'posts';

    protected $guarded = [];
}
