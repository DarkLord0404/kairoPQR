<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingsAboutAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_can_view_kairomeet_about_page(): void
    {
        $user = User::factory()->create(['role' => 'master']);
        $this->actingAs($user)->get('/reuniones/acerca-de')->assertOk()->assertSee('Acerca de KairoMeet');
    }

    public function test_non_master_cannot_view_kairomeet_about_page(): void
    {
        $user = User::factory()->create(['role' => 'administrativo', 'acceso_reuniones' => true]);
        $this->actingAs($user)->get('/reuniones/acerca-de')->assertForbidden();
    }
}
