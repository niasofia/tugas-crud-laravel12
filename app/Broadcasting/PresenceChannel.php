<?php

namespace App\Broadcasting;

use App\Models\User;

class PresenceChannel
{
    public function join(User $user)
    {
        return ['id' => $user->id, 'name' => $user->name];
    }
}