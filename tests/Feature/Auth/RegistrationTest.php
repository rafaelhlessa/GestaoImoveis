<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        // Criar perfis necessários
        \App\Models\Profile::factory()->proprietario()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'cpf_cnpj' => '12345678901',
            'phone' => '11999999999',
            'address' => 'Test Address',
            'city' => 'Test City',
            'city_id' => 1,
            'profiles' => ['proprietario'], // Campo obrigatório
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'activity_id' => null, // Opcional
        ]);

        // O usuário não deve estar autenticado pois a conta precisa ser ativada
        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // Verificar se o usuário foi criado na base de dados
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_active' => false, // Usuário deve estar inativo inicialmente
        ]);
    }
}
