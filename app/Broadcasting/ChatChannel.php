<?php

namespace App\Broadcasting;

use App\Models\User;
use App\Models\Conversation;

class ChatChannel
{
    public function join(User $user, Conversation $conversation)
    {
        return $user->conversations->contains($conversation);
    }
}