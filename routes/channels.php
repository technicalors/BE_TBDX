<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.CustomUser.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('my-channel', function() {
    return true;
});

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return true;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('presence-chat.{chatId}', function ($user, $chatId) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        // thêm avatar nếu cần
    ];
});