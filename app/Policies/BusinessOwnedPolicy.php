<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BusinessOwnedPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBusinessOwner() || $user->isSuperAdmin();
    }

    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isBusinessOwner() && $user->business_id !== null;
    }

    public function update(User $user, Model $model): bool
    {
        return $user->isBusinessOwner() && $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->update($user, $model);
    }

    protected function owns(User $user, Model $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isBusinessOwner()
            && $user->belongsToBusiness((int) $model->getAttribute('business_id'));
    }
}
