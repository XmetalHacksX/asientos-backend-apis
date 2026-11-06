<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        RoleAndPermissionSeeder::class,
        UserSeeder::class,
    ]);
});

test('user can log in with valid credentials and receive token with roles', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'status',
            'message',
            'access_token',
            'token_type',
            'user' => [
                'id',
                'name',
                'email',
                'roles',
                'permissions',
            ],
        ]);

    expect($response->json('user.roles'))->toContain('admin');
});

test('login fails with wrong password', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'admin@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can access profile and log out', function () {
    $user = User::where('email', 'admin@example.com')->first();
    $token = $user->createToken('test')->plainTextToken;

    $profileResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/me');

    $profileResponse->assertStatus(200)
        ->assertJsonFragment(['email' => 'admin@example.com']);

    $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/logout');

    $logoutResponse->assertStatus(200)
        ->assertJson(['status' => 'success']);
});
