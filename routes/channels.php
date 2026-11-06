<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal público para que cualquier espectador vea los asientos en vivo
Broadcast::channel('event.{eventId}', function () {
    return true;
});

// Canal administrativo para métricas del dashboard
Broadcast::channel('admin.dashboard', function ($user) {
    return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('admin');
});
