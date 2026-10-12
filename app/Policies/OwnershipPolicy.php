<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final class OwnershipPolicy
{
    public function view(User $user, Account|Category|Transaction $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function update(User $user, Account|Category|Transaction $resource): Response
    {
        return $this->owns($user, $resource);
    }

    public function delete(User $user, Account|Category|Transaction $resource): Response
    {
        return $this->owns($user, $resource);
    }

    private function owns(User $user, Account|Category|Transaction $resource): Response
    {
        // 404 instead of 403: another user's records must not be distinguishable from missing ones.
        return $resource->user()->is($user) ? Response::allow() : Response::denyAsNotFound();
    }
}
