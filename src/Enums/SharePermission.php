<?php

namespace Bernskiold\LaravelCollaboration\Enums;

/**
 * The permission of a person on a shared record.
 *
 * The shares table keeps the values. Do not change them.
 *
 * The policy of each record controls what "edit" lets a person do. This
 * enum only gives the order: edit includes view.
 */
enum SharePermission: string
{
    case View = 'view';
    case Edit = 'edit';

    /**
     * This method returns true if this permission is the same as the
     * necessary permission, or larger.
     */
    public function allows(self $necessary): bool
    {
        return $this->rank() >= $necessary->rank();
    }

    /**
     * This method returns the permissions that allow the necessary
     * permission.
     *
     * @return array<int, self>
     */
    public static function atLeast(self $necessary): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $permission): bool => $permission->allows($necessary),
        ));
    }

    public function label(): string
    {
        return __("collaboration::collaboration.permissions.{$this->value}.label");
    }

    public function description(): string
    {
        return __("collaboration::collaboration.permissions.{$this->value}.description");
    }

    protected function rank(): int
    {
        return match ($this) {
            self::View => 1,
            self::Edit => 2,
        };
    }
}
