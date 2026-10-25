<?php

namespace Bernskiold\LaravelCollaboration;

use Bernskiold\LaravelCollaboration\Contracts\Shareable;
use Bernskiold\LaravelCollaboration\Models\Share;
use Bernskiold\LaravelCollaboration\Support\PeopleDirectory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class Collaboration
{
    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        return config('collaboration.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }

    /**
     * This method returns a query for the user model. The query includes
     * soft-deleted users. This keeps the names of removed users available.
     *
     * @return Builder<Model>
     */
    public static function userQueryWithTrashed(): Builder
    {
        $model = static::userModel();

        $query = $model::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        return $query;
    }

    /**
     * This method returns the table of the user model. The migration uses
     * this table for the foreign keys.
     */
    public static function userTable(): string
    {
        $model = static::userModel();

        return class_exists($model) ? (new $model)->getTable() : 'users';
    }

    /**
     * This method returns the signed-in user. The package records this user
     * as the person that changed the shares.
     */
    public static function actingUser(): ?Model
    {
        $user = auth()->guard()->user();

        return $user instanceof Model ? $user : null;
    }

    /**
     * @return class-string<Share>
     */
    public static function shareModel(): string
    {
        return config('collaboration.share_model', Share::class);
    }

    public static function people(): PeopleDirectory
    {
        return app(config('collaboration.people', PeopleDirectory::class));
    }

    /**
     * This method finds the shareable record for a morph type and a route
     * key. The type can be a morph alias or a class name. The method returns
     * null if the type is not a shareable model. It also returns null if
     * the record does not exist.
     *
     * The method finds the record by its route key. To use a different
     * column, for example the primary key, give the field.
     *
     * Use this method in a component or a tool that gets the record from the
     * browser or from an API call. Then authorize the user for the record.
     */
    public static function findShareable(string $type, int|string $key, ?string $field = null): (Model&Shareable)|null
    {
        $class = Relation::getMorphedModel($type) ?? $type;

        if (! class_exists($class) || ! is_a($class, Model::class, true) || ! is_a($class, Shareable::class, true)) {
            return null;
        }

        $record = (new $class)->resolveRouteBinding($key, $field);

        return $record instanceof Shareable ? $record : null;
    }
}
