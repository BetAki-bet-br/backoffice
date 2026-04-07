<?php

namespace App\Policies;

use App\Models\Domain\Navigation\Footer;
use App\Models\User;

class FooterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('footers.viewAny') || $user->can('footers.view') || $user->can('admin');
    }

    public function view(User $user, Footer $footer): bool
    {
        return $user->can('footers.view') || $user->can('footers.viewAny') || $user->can('admin');
    }

    public function create(User $user): bool
    {
        return $user->can('footers.create') || $user->can('admin');
    }

    public function update(User $user, Footer $footer): bool
    {
        return $user->can('footers.update') || $user->can('admin');
    }

    public function delete(User $user, Footer $footer): bool
    {
        return $user->can('footers.delete') || $user->can('admin');
    }

    public function publish(User $user, Footer $footer): bool
    {
        return $user->can('footers.publish') || $user->can('admin');
    }
}
