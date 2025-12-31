<?php

namespace App\Policies;

use App\Models\Domain\Banners\Banner;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BannerPolicy
{
    public function viewAny($user)   { return $user->can('banners.view'); }
    public function view($user)      { return $user->can('banners.view'); }
    public function create($user)    { return $user->can('banners.create'); }
    public function update($user)    { return $user->can('banners.update'); }
    public function delete($user)    { return $user->can('banners.delete'); }
    public function publish($user)   { return $user->can('banners.publish'); }
}
