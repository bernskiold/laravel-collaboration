<?php

namespace Bernskiold\LaravelCollaboration\Enums;

/**
 * The result of a change to the share of one person.
 */
enum ShareOutcome: string
{
    /**
     * The person did not have access before. Now the person has access.
     */
    case Added = 'added';

    /**
     * The person had access at a different permission. Now the person has
     * the new permission.
     */
    case Changed = 'changed';

    /**
     * The person had access before. Now the person does not have access.
     */
    case Removed = 'removed';

    /**
     * The access of the person stayed the same.
     */
    case Unchanged = 'unchanged';
}
