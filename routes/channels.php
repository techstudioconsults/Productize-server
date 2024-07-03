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

Broadcast::channel('users.{id}', function (User $user, $id) {
    // return $user->id === $id;
    Log::alert("I GOT HERE");
    return true;
});

Broadcast::channel('order-created.{userId}', function (User $user, $userId) {
    return $user->id === $userId;
});
