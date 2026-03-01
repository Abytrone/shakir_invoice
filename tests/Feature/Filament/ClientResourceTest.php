<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Filament\Concerns\ActsAsAdmin;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    use ActsAsAdmin;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpActsAsAdmin();
    }

    public function test_can_render_list_page(): void
    {
        $this->actingAsAdmin()
            ->get(ClientResource::getUrl('index'))
            ->assertOk();
    }

    public function test_can_list_clients(): void
    {
        $clients = Client::factory()->count(3)->create();

        $this->actingAsAdmin()
            ->get(ClientResource::getUrl('index'))
            ->assertOk()
            ->assertSeeText($clients->first()->name);
    }

    public function test_can_render_create_page(): void
    {
        $this->actingAsAdmin()
            ->get(ClientResource::getUrl('create'))
            ->assertOk();
    }

    public function test_can_render_edit_page(): void
    {
        $client = Client::factory()->create();

        $this->actingAsAdmin()
            ->get(ClientResource::getUrl('edit', ['record' => $client]))
            ->assertOk();
    }
}
