<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('presence.chat', function ($user) {
    return $user ? ['id' => $user->id, 'name' => $user->name] : false;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    return $conversation && $user->conversations->contains($conversation);
});