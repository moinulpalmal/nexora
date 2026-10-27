<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast channels
|--------------------------------------------------------------------------
|
| Every channel is private, authorised here by the same rule the screen it
| feeds uses — a push must never tell someone what the page would not.
|
| The user channel carries database notifications to the bell
| (`Notifiable::receivesBroadcastNotificationsOn()` names it after the class).
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});
