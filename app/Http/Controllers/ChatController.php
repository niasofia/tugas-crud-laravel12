<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use App\Events\UserPresenceChanged;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    // Get all conversations for logged-in user
    public function getConversations()
    {
        $conversations = auth()->user()->conversations()
            ->with(['users', 'latestMessage.user'])
            ->latest('updated_at')
            ->get()
            ->map(fn($conv) => [
                'id' => $conv->id,
                'name' => $conv->getDisplayName(),
                'type' => $conv->type,
                'users' => $conv->users->map(fn($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'is_online' => $u->isOnline(),
                ]),
                'last_message' => $conv->latestMessage ? [
                    'body' => $conv->latestMessage->body,
                    'user_name' => $conv->latestMessage->user->name,
                    'created_at' => $conv->latestMessage->created_at->format('H:i'),
                ] : null,
            ]);

        return response()->json($conversations);
    }

    // Get messages from a conversation
    public function getMessages(Conversation $conversation)
    {
        // Check if user is member of conversation
        if (!$conversation->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = $conversation->messages()
            ->with('user')
            ->latest()
            ->paginate(50);

        return response()->json(
            $messages->reverse()->values()->map(fn($msg) => [
                'id' => $msg->id,
                'user_id' => $msg->user_id,
                'user_name' => $msg->user->name,
                'body' => $msg->body,
                'created_at' => $msg->created_at->format('H:i'),
            ])
        );
    }

    // Send message
    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Check if user is member of conversation
        if (!$conversation->users()->where('user_id', auth()->id())->exists()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        $message->load('user');
        $conversation->touch(); // Update conversation updated_at

        // Broadcast message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'user_id' => $message->user_id,
            'user_name' => $message->user->name,
            'body' => $message->body,
            'created_at' => $message->created_at->format('H:i'),
        ]);
    }

    // Create or get private conversation
    public function getOrCreatePrivateConversation(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|different:' . auth()->id(),
        ]);

        // Check if conversation exists
        $conversation = Conversation::where('type', 'private')
            ->whereHas('users', fn($q) => $q->where('user_id', auth()->id()))
            ->whereHas('users', fn($q) => $q->where('user_id', $validated['user_id']))
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'type' => 'private',
                'created_by' => auth()->id(),
            ]);

            $conversation->users()->attach([auth()->id(), $validated['user_id']]);
        }

        return response()->json(['conversation_id' => $conversation->id]);
    }

    // Create group conversation
    public function createGroupConversation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_ids' => 'required|array|min:2',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = collect($validated['user_ids'])->prepend(auth()->id())->unique()->toArray();

        $conversation = Conversation::create([
            'name' => $validated['name'],
            'type' => 'group',
            'created_by' => auth()->id(),
        ]);

        $conversation->users()->attach($userIds);

        return response()->json(['conversation_id' => $conversation->id]);
    }

    // Update user presence
    public function updatePresence(Request $request)
    {
        $validated = $request->validate([
            'status' => 'required|in:online,offline',
        ]);

        $presence = auth()->user()->presence()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['status' => $validated['status'], 'last_seen_at' => now()]
        );

        broadcast(new UserPresenceChanged(auth()->user(), $validated['status']))->toOthers();

        return response()->json(['status' => $presence->status]);
    }

    // Get all users
    public function getAllUsers()
    {
        $users = User::where('id', '!=', auth()->id())
            ->with('presence')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_online' => $u->isOnline(),
                'last_seen_at' => $u->presence?->last_seen_at,
            ]);

        return response()->json($users);
    }
}