<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

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

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    Log::info('Channel accessed for user: '.$user->id.', requested user ID: '.$id);

    return (int) $user->id === (int) $id;
});

Broadcast::channel('order-created.{userId}', function (User $user, $userId) {
    Log::info('Channel accessed for user event: '.$user->id.', requested user ID: '.$userId);

    return $user->id === $userId;
});
