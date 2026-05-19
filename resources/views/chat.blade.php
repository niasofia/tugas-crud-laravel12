<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatApp - IG Style</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @vite(['resources/js/app.js'])

    <style>
        /* Custom scrollbar ala MacOS/IG */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
    </style>
</head>
<body class="bg-gray-50 h-screen overflow-hidden flex justify-center items-center font-sans text-gray-900">

    <div x-data="chatApp()" x-init="initApp()" class="flex h-[95vh] w-[95vw] max-w-6xl bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        
        <div class="w-[350px] border-r border-gray-200 flex flex-col bg-white shrink-0">
            <div class="h-20 px-6 flex justify-between items-center border-b border-gray-200">
                <div class="font-bold text-xl tracking-tight">{{ auth()->user()->name }}</div>
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 bg-green-500 rounded-full shadow-sm" title="Online"></span>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-red-500 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </div>

            <div class="flex px-4 pt-4 pb-2 text-sm font-semibold text-gray-900 border-b border-gray-100">
                <button @click="tab = 'chats'" :class="tab === 'chats' ? 'text-black' : 'text-gray-400'" class="mr-6 transition">Messages</button>
                <button @click="tab = 'users'; fetchUsers()" :class="tab === 'users' ? 'text-black' : 'text-gray-400'" class="transition">New Chat</button>
            </div>

            <div x-show="tab === 'chats'" class="flex-1 overflow-y-auto p-2">
                <template x-for="conv in conversations" :key="conv.id">
                    <div @click="selectConversation(conv)" 
                         :class="activeConversation && activeConversation.id === conv.id ? 'bg-gray-50' : 'hover:bg-gray-50'"
                         class="flex items-center p-3 cursor-pointer rounded-lg transition">
                        
                        <div class="relative">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 p-[2px]">
                                <div class="w-full h-full bg-white rounded-full border-2 border-white flex items-center justify-center text-lg font-bold text-gray-700">
                                    <span x-text="conv.name.charAt(0).toUpperCase()"></span>
                                </div>
                            </div>
                            <template x-if="conv.type === 'private' && isUserOnline(conv)">
                            <div class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white rounded-full z-10"></div>
                        </template>
                        </div>

                        <div class="ml-4 flex-1 overflow-hidden">
                            <div class="flex justify-between items-center">
                                <h3 class="font-semibold text-sm truncate" x-text="conv.name"></h3>
                                <span class="text-[11px] text-gray-400 shrink-0" x-text="conv.last_message ? conv.last_message.created_at : ''"></span>
                            </div>
                            <p class="text-xs text-gray-500 truncate mt-1" :class="!conv.last_message ? 'italic' : ''" x-text="conv.last_message ? (conv.last_message.user_name + ': ' + conv.last_message.body) : 'Send a message...'"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="tab === 'users'" class="flex-1 overflow-y-auto p-2">
                <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Suggested</div>
                <template x-for="u in users" :key="u.id">
                    <div @click="startPrivateChat(u.id)" class="flex items-center p-3 hover:bg-gray-50 rounded-lg cursor-pointer transition">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-gray-600 font-bold" x-text="u.name.charAt(0)"></div>
                            <template x-if="u.is_online">
                                <div class="absolute bottom-0 right-0 w-3.5 h-3.5 bg-green-500 border-2 border-white rounded-full"></div>
                            </template>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-semibold" x-text="u.name"></div>
                            <div class="text-xs text-gray-400" x-text="u.is_online ? 'Active now' : 'Offline'"></div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex-1 flex flex-col bg-white">
            
            <template x-if="activeConversation">
                <div class="flex flex-col h-full">
                    
                    <div class="h-20 px-6 border-b border-gray-200 flex items-center justify-between bg-white/95 backdrop-blur z-10">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700 font-bold text-lg" x-text="activeConversation.name.charAt(0)"></div>
                                <template x-if="activeConversation.type === 'private' && isUserOnline(activeConversation)">
                                    <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full z-10"></div>
                                </template>
                            </div>
                            <div>
                                <h2 class="font-bold text-gray-900 text-base leading-tight" x-text="activeConversation.name"></h2>
                                <p class="text-[11px] transition-colors mt-0.5" 
                                   :class="isUserOnline(activeConversation) ? 'text-green-600 font-semibold' : 'text-gray-500'" 
                                   x-text="getLastSeenText(activeConversation)"></p>
                            </div>
                        </div>
                        <button class="text-gray-900 hover:bg-gray-100 p-2 rounded-full transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </button>
                    </div>

                    <div id="message-container" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <template x-for="msg in messages" :key="msg.id">
                            <div :class="msg.user_id == {{ auth()->id() }} ? 'justify-end' : 'justify-start'" class="flex w-full">
                                
                                <template x-if="msg.user_id != {{ auth()->id() }}">
                                    <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 mr-2 mt-auto shrink-0" x-text="msg.user_name.charAt(0)"></div>
                                </template>

                                <div class="flex flex-col w-full" :class="msg.user_id == {{ auth()->id() }} ? 'items-end' : 'items-start'">
    <div x-show="activeConversation.type === 'group' && msg.user_id != {{ auth()->id() }}" class="text-[10px] text-gray-400 ml-2 mb-1" x-text="msg.user_name"></div>
    
    <div :class="msg.user_id == {{ auth()->id() }} ? 'bg-gradient-to-r from-violet-600 to-fuchsia-600 text-white rounded-2xl rounded-br-sm' : 'bg-[#efefef] text-gray-900 rounded-2xl rounded-bl-sm'" 
         class="max-w-[75%] w-fit px-4 py-2.5 text-[15px] shadow-sm relative group">
         
        <p class="whitespace-pre-wrap break-words leading-relaxed" x-text="msg.body"></p>
        
        <div :class="msg.user_id == {{ auth()->id() }} ? 'right-full mr-2' : 'left-full ml-2'" 
             class="absolute top-1/2 -translate-y-1/2 text-[10px] text-gray-400 opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap" 
             x-text="msg.created_at"></div>
    </div>
</div>
                            </div>
                        </template>
                    </div>

                    <div class="px-6 pb-1 bg-white" x-show="typingUser" style="display: none;">
                        <p class="text-[11px] text-gray-400 italic font-medium" x-text="typingUser + ' sedang mengetik...'"></p>
                    </div>

                    <div class="p-4 bg-white border-t border-gray-50">
                        <form @submit.prevent="sendMessage()" class="flex items-center gap-3 border border-gray-300 rounded-full px-2 py-1.5 focus-within:border-gray-400 transition">
                            <button type="button" class="p-2 text-gray-900 hover:bg-gray-100 rounded-full transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            
                            <input type="text" x-model="newMessage" @input="notifyTyping()" placeholder="Message..." class="flex-1 bg-transparent border-none px-2 py-2 text-sm focus:outline-none focus:ring-0 text-gray-900 placeholder-gray-400">
                            
                            <button type="submit" x-show="newMessage.trim().length > 0" class="text-blue-500 font-semibold px-4 text-sm hover:text-blue-700 transition">
                                Send
                            </button>
                            
                            <button type="button" x-show="newMessage.trim().length === 0" class="p-2 text-gray-900 hover:bg-gray-100 rounded-full transition">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <template x-if="!activeConversation">
                <div class="flex-1 flex flex-col items-center justify-center text-gray-900 h-full">
                    <div class="w-24 h-24 border-2 border-black rounded-full flex items-center justify-center mb-4">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"></path></svg>
                    </div>
                    <h3 class="text-xl font-medium mb-1">Your Messages</h3>
                    <p class="text-sm text-gray-500">Send private photos and messages to a friend or group.</p>
                </div>
            </template>
        </div>

    </div>

    <script>
        function chatApp() {
            return {
                tab: 'chats',
                conversations: [],
                users: [],
                activeConversation: null,
                activeChannelId: null,
                messages: [],
                newMessage: '',
                onlineUsers: [], 
                
                // Variabel baru untuk fitur Typing
                typingUser: false,
                typingTimeout: null,

                initApp() {
                    this.fetchConversations();
                    this.fetchUsers();
                    this.connectToPresenceChannel();
                    this.updateMyPresence('online');
                    setInterval(() => this.updateMyPresence('online'), 30000);
                },

                fetchConversations() {
                    fetch('/conversations')
                        .then(res => res.json())
                        .then(data => { this.conversations = data; });
                },

                fetchUsers() {
                    fetch('/users')
                        .then(res => res.json())
                        .then(data => { this.users = data; });
                },

                selectConversation(conv) {
                    this.activeConversation = conv;
                    this.fetchMessages(conv.id);
                    this.listenToConversationChannel(conv.id);
                },

                fetchMessages(id) {
                    fetch(`/conversations/${id}/messages`)
                        .then(res => res.json())
                        .then(data => {
                            this.messages = data;
                            this.scrollToBottom();
                        });
                },

                // Fungsi baru untuk mengirim sinyal "Berbisik"
                notifyTyping() {
                    if (!this.activeConversation) return;
                    
                    window.Echo.private(`chat.${this.activeConversation.id}`)
                        .whisper('typing', {
                            user_name: '{{ auth()->user()->name }}'
                        });
                },

                sendMessage() {
                    if (!this.newMessage.trim()) return;

                    let bodyData = { body: this.newMessage };
                    let currentConvId = this.activeConversation.id;

                    fetch(`/conversations/${currentConvId}/messages`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(bodyData)
                    })
                    .then(res => res.json())
                    .then(msg => {
                        this.messages.push(msg);
                        this.newMessage = '';
                        this.scrollToBottom();
                        this.fetchConversations();
                    });
                },

                startPrivateChat(userId) {
                    fetch('/conversations/private', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ user_id: userId })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.tab = 'chats';
                        this.fetchConversations();
                        setTimeout(() => {
                            let found = this.conversations.find(c => c.id === data.conversation_id);
                            if (found) this.selectConversation(found);
                        }, 500);
                    });
                },

                updateMyPresence(status) {
                    fetch('/presence', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ status: status })
                    });
                },

                connectToPresenceChannel() {
                    setTimeout(() => {
                        if (!window.Echo) return;
                        
                        window.Echo.join('presence.chat')
                            .here((users) => {
                                this.onlineUsers = users.map(u => u.id);
                            })
                            .joining((user) => {
                                if (!this.onlineUsers.includes(user.id)) this.onlineUsers.push(user.id);
                            })
                            .leaving((user) => {
                                this.onlineUsers = this.onlineUsers.filter(id => id !== user.id);
                            })
                            .listen('.user.presence.changed', (e) => {
                                this.fetchConversations();
                                if (this.tab === 'users') this.fetchUsers();
                            });
                    }, 1000);
                },

                getLastSeenText(conv) {
                    if (!conv) return '';
                    if (conv.type === 'group') return 'Grup Chat';
                    
                    if (!conv.users) return 'Offline';
                    let kawanId = conv.users.find(u => u.id != {{ auth()->id() }})?.id;
                    
                    if (this.onlineUsers.includes(kawanId)) {
                        return 'Sedang aktif';
                    }

                    let kawan = this.users.find(u => u.id === kawanId);
                    
                    if (kawan && kawan.last_seen_at) {
                        let date = new Date(kawan.last_seen_at);
                        let now = new Date();
                        let hours = date.getHours().toString().padStart(2, '0');
                        let minutes = date.getMinutes().toString().padStart(2, '0');
                        
                        if(date.toDateString() === now.toDateString()) {
                            return 'Terakhir aktif hari ini pukul ' + hours + ':' + minutes;
                        } else {
                            return 'Terakhir aktif ' + date.getDate() + '/' + (date.getMonth()+1) + ' pukul ' + hours + ':' + minutes;
                        }
                    }

                    return 'Offline';
                },

                isUserOnline(conv) {
                    if (!conv || !conv.users) return false;
                    let kawan = conv.users.find(u => u.id != {{ auth()->id() }});
                    return kawan ? this.onlineUsers.includes(kawan.id) : false;
                },

                scrollToBottom() {
                    setTimeout(() => {
                        let container = document.getElementById('message-container');
                        if (container) container.scrollTop = container.scrollHeight;
                    }, 50);
                },

                listenToConversationChannel(conversationId) {
                    if (this.activeChannelId) {
                        window.Echo.leave(`chat.${this.activeChannelId}`);
                    }
                    
                    this.activeChannelId = conversationId;

                    window.Echo.private(`chat.${conversationId}`)
                        .listen('.message.sent', (e) => {
                            if (this.activeConversation && this.activeConversation.id === e.conversation_id) {
                                if(e.user_id != {{ auth()->id() }}) {
                                    
                                    // Matikan indikator typing saat pesan masuk
                                    this.typingUser = false;

                                    let pesanSudahAda = this.messages.some(m => m.id === e.id);
                                    if (!pesanSudahAda) {
                                        this.messages.push({
                                            id: e.id,
                                            user_id: e.user_id,
                                            user_name: e.user_name,
                                            body: e.body,
                                            created_at: e.created_at
                                        });
                                        this.scrollToBottom();
                                    }
                                }
                            }
                            this.fetchConversations(); 
                        })
                        // SENSOR BISIKAN (Mendengarkan siapa yang lagi ngetik)
                        .listenForWhisper('typing', (e) => {
                            this.typingUser = e.user_name;
                            
                            // Hapus jeda yang lama
                            if (this.typingTimeout) {
                                clearTimeout(this.typingTimeout);
                            }
                            
                            // Tulisan akan hilang otomatis setelah 2 detik nggak ngetik
                            this.typingTimeout = setTimeout(() => {
                                this.typingUser = false;
                            }, 2000);
                        });
                }
            }
        }
    </script>
</body>
</html>