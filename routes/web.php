<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;

// Otomatis arahkan ke chat
Route::get('/', function () {
    return redirect()->route('chat');
});

// --- HALAMAN LOGIN SEMENTARA ---
Route::get('/login', function () {
    return '
        <div style="text-align:center; margin-top:100px; font-family:sans-serif;">
            <h2>Login Tester App Chat</h2>
            <form action="/login" method="POST">
                <input type="hidden" name="_token" value="'.csrf_token().'">
                <div style="margin-bottom:10px;">
                    <input type="email" name="email" value="budi@test.com" style="padding:8px;" placeholder="Email" required>
                </div>
                <div style="margin-bottom:10px;">
                    <input type="password" name="password" value="password123" style="padding:8px;" placeholder="Password" required>
                </div>
                <button type="submit" style="padding:8px 20px; background-color:#10b981; color:white; border:none; border-radius:5px; cursor:pointer;">Login</button>
            </form>
            <p style="font-size:12px; color:gray;">Info: Coba login pakai <b>budi@test.com</b> atau <b>ani@test.com</b><br>Password: <b>password123</b></p>
        </div>
    ';
})->name('login');

Route::post('/login', function (Request $request) {
    if (Auth::attempt($request->only('email', 'password'))) {
        $request->session()->regenerate();
        return redirect()->route('chat');
    }
    return "Login Gagal! Email atau password salah.";
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
// --------------------------------

// Route Inti yang butuh Login (Tugas Chat-nya)
Route::middleware(['auth'])->group(function () {
    
    // Tampilan UI
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    // API Internal untuk Alpine.js
    Route::get('/conversations', [ChatController::class, 'getConversations']);
    Route::get('/conversations/{conversation}/messages', [ChatController::class, 'getMessages']);
    Route::post('/conversations/{conversation}/messages', [ChatController::class, 'sendMessage']);
    Route::post('/conversations/private', [ChatController::class, 'getOrCreatePrivateConversation']);
    Route::post('/conversations/group', [ChatController::class, 'createGroupConversation']);
    Route::post('/presence', [ChatController::class, 'updatePresence']);
    Route::get('/users', [ChatController::class, 'getAllUsers']);
});