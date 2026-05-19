<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\UserPresence;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users
        $user1 = User::create([
            'name' => 'Budi',
            'email' => 'budi@test.com',
            'password' => bcrypt('password123'),
        ]);

        $user2 = User::create([
            'name' => 'Ani',
            'email' => 'ani@test.com',
            'password' => bcrypt('password123'),
        ]);

        $user3 = User::create([
            'name' => 'Citra',
            'email' => 'citra@test.com',
            'password' => bcrypt('password123'),
        ]);

        $user4 = User::create([
            'name' => 'Doni',
            'email' => 'doni@test.com',
            'password' => bcrypt('password123'),
        ]);

        // Create user presences
        foreach ([$user1, $user2, $user3, $user4] as $user) {
            UserPresence::create([
                'user_id' => $user->id,
                'status' => 'offline',
                'last_seen_at' => now(),
            ]);
        }

        // Create private conversation between Budi & Ani
        $conv1 = Conversation::create([
            'type' => 'private',
            'created_by' => $user1->id,
        ]);
        $conv1->users()->attach([$user1->id, $user2->id]);

        // Add messages
        Message::create([
            'conversation_id' => $conv1->id,
            'user_id' => $user1->id,
            'body' => 'Halo Ani, apa kabar?',
        ]);

        Message::create([
            'conversation_id' => $conv1->id,
            'user_id' => $user2->id,
            'body' => 'Halo Budi! Aku baik baik aja, thanks!',
        ]);

        // Create group conversation
        $conv2 = Conversation::create([
            'name' => 'Tim Proyek',
            'type' => 'group',
            'created_by' => $user1->id,
        ]);
        $conv2->users()->attach([$user1->id, $user2->id, $user3->id, $user4->id]);

        // Add group messages
        Message::create([
            'conversation_id' => $conv2->id,
            'user_id' => $user1->id,
            'body' => 'Halo semua! Bagaimana progress project?',
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'user_id' => $user3->id,
            'body' => 'Sudah selesai bagian saya, tinggal testing',
        ]);

        Message::create([
            'conversation_id' => $conv2->id,
            'user_id' => $user4->id,
            'body' => 'Saya masih proses, akan selesai besok',
        ]);

        // Create another private conversation
        $conv3 = Conversation::create([
            'type' => 'private',
            'created_by' => $user1->id,
        ]);
        $conv3->users()->attach([$user1->id, $user3->id]);

        Message::create([
            'conversation_id' => $conv3->id,
            'user_id' => $user1->id,
            'body' => 'Hi Citra, ada yang ingin aku diskusikan',
        ]);
    }
}