<?php

namespace Bernskiold\LaravelCollaboration\Database\Factories;

use Bernskiold\LaravelCollaboration\Collaboration;
use Bernskiold\LaravelCollaboration\Enums\SharePermission;
use Bernskiold\LaravelCollaboration\Models\Share;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * This factory makes shares for tests.
 *
 *     Share::factory()->on($portfolio)->sharedWith($user)->edit()->create();
 *
 * The factory writes the share directly. It does not record activity, dispatch
 * events or send notifications. To test those, use shareWith() or SyncShares.
 *
 * @extends Factory<Share>
 */
class ShareFactory extends Factory
{
    protected $model = Share::class;

    public function modelName(): string
    {
        return Collaboration::shareModel();
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shared_with_id' => $this->userFactory(),
            'permission' => SharePermission::View,
        ];
    }

    /**
     * The shared record.
     */
    public function on(Model $record): static
    {
        return $this->state([
            'shareable_type' => $record->getMorphClass(),
            'shareable_id' => $record->getKey(),
        ]);
    }

    /**
     * The person that gets access.
     */
    public function sharedWith(Model $user): static
    {
        return $this->state(['shared_with_id' => $user->getKey()]);
    }

    /**
     * The person that shared the record.
     */
    public function sharedBy(Model $user): static
    {
        return $this->state(['shared_by_id' => $user->getKey()]);
    }

    public function permission(SharePermission $permission): static
    {
        return $this->state(['permission' => $permission]);
    }

    public function view(): static
    {
        return $this->permission(SharePermission::View);
    }

    public function edit(): static
    {
        return $this->permission(SharePermission::Edit);
    }

    /**
     * A new user from the factory of the user model. If the user model has no
     * factory, give the person with sharedWith().
     */
    protected function userFactory(): mixed
    {
        $model = Collaboration::userModel();

        return method_exists($model, 'factory') ? $model::factory() : null;
    }
}
