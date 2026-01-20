<?php

use App\Models\User;

test('user can login', function () {
    pest()->browser()->withHost('foo.opticanet.test');

    User::query()
        ->where('email', 'test@example.com')
        ->update(
            ['password_changed_at' => now()]
        );

    visit('/login')->assertSee('Iniciar sesión en tu cuenta')
        ->assertSee('Ingresa tu correo electrónico y contraseña para iniciar sesión')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->debug()
        ->click('Iniciar sesión')
        ->assertPathIs('/dashboard')
        ->assertSee('Tablero');
});
