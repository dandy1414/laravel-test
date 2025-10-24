<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_store_new_user_via_api(): void
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'role' => 'manager',
        ];
        $response = $this->postJson('/api/user', $payload);
        $response
            ->assertCreated()
            ->assertJson([
                'message' => 'User created',
                'data' => [
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'role' => 'manager',
                ],
            ])
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'name', 'email', 'role', 'created_at'],
            ]);
        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'role' => 'manager',
            'active' => true,
        ]);
    }

    #[Test]
    public function can_store_new_user_with_default_role(): void
    {
        $payload = [
            'name' => 'Jane Doe',
            'email' => 'jane.doe@example.com',
            'password' => 'password123',
        ];
        $response = $this->postJson('/api/user', $payload);
        $response->assertCreated()->assertJsonPath('data.role', 'user');
        $this->assertDatabaseHas('users', [
            'email' => 'jane.doe@example.com',
            'role' => 'user',
            'active' => true,
        ]);
    }

    #[Test]
    public function cannot_store_user_with_invalid_payload(): void
    {
        $response = $this->get('/');
        User::factory()->create(['email' => 'dup@example.com']);
        $response->assertStatus(200);
        $invalidPayloads = [
            [],
            ['name' => 'x', 'email' => 'not-an-email', 'password' => 'short', 'role' => 'invalid'],
            ['name' => 'John', 'email' => 'dup@example.com', 'password' => 'password123'],
        ];
        foreach ($invalidPayloads as $payload) {
            $this->postJson('/api/user', $payload)->assertStatus(422);
        }
    }

    #[Test]
    public function can_list_users_with_pagination_and_counts_and_can_edit_flags(): void
    {
        $u1 = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'role' => 'user',
            'active' => true,
        ]);
        $u2 = User::factory()->create([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'role' => 'manager',
            'active' => true,
        ]);
        $u3 = User::factory()->create([
            'name' => 'Carol',
            'email' => 'carol@example.com',
            'role' => 'administrator',
            'active' => true,
        ]);
        $inactive = User::factory()->create([
            'name' => 'Dave',
            'email' => 'dave@example.com',
            'active' => false,
        ]);

        // Give Alice 2 orders
        Order::create(['user_id' => $u1->id]);
        Order::create(['user_id' => $u1->id]);
        $response = $this->getJson('/api/user?limit=10');
        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'id',
                    'name',
                    'email',
                    'role',
                    'active',
                    'created_at',
                    'orders_count',
                    'can_edit',
                ]],
            ]);
        $json = $response->json();

        // Only active users are returned
        $emails = collect($json['data'])->pluck('email');
        $this->assertTrue($emails->contains('alice@example.com'));
        $this->assertTrue($emails->contains('bob@example.com'));
        $this->assertTrue($emails->contains('carol@example.com'));
        $this->assertFalse($emails->contains('dave@example.com'));
        
        // Orders count for Alice should be 2
        $alice = collect($json['data'])->firstWhere('email', 'alice@example.com');
        $this->assertSame(2, $alice['orders_count']);
        // Default currentRole=user without id -> can_edit false for all
        $this->assertTrue(collect($json['data'])->every(fn ($u) => $u['can_edit'] === false));
    }

    #[Test]
    public function can_filter_and_sort_users(): void
    {
        User::factory()->create(['name' => 'Zed Smith', 'email' => 'zed@example.com', 'active' => true]);
        User::factory()->create(['name' => 'Adam West', 'email' => 'adam@example.com', 'active' => true]);
        
        $response = $this->getJson('/api/user?search=Adam&sortBy=name&limit=10');
        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');

        $this->assertTrue($names->contains('Adam West'));
        $this->assertFalse($names->contains('Zed Smith'));
    }

    #[Test]
    public function can_edit_flags_change_depending_on_current_role_and_user(): void
    {
        $u = User::factory()->create(['name' => 'Regular', 'email' => 'user@example.com', 'role' => 'user', 'active' => true]);
        $m = User::factory()->create(['name' => 'Manager', 'email' => 'manager@example.com', 'role' => 'manager', 'active' => true]);
        $a = User::factory()->create(['name' => 'Admin', 'email' => 'admin@example.com', 'role' => 'administrator', 'active' => true]);

        // Administrator can edit everyone
        $respAdmin = $this->getJson('/api/user?currentRole=administrator&limit=50');
        $this->assertTrue(collect($respAdmin->json('data'))->every(fn ($row) => $row['can_edit'] === true));
       
        // Manager can edit only users with role 'user'
        $respManager = $this->getJson('/api/user?currentRole=manager&limit=50');
        $canEditByEmail = collect($respManager->json('data'))
            ->mapWithKeys(fn ($row) => [$row['email'] => $row['can_edit']])
            ->all();
        $this->assertTrue($canEditByEmail['user@example.com']);
        $this->assertFalse($canEditByEmail['manager@example.com']);
        $this->assertFalse($canEditByEmail['admin@example.com']);
       
        // User can edit only themselves
        $respUser = $this->getJson('/api/user?currentRole=user&currentUserId=' . $u->id . '&limit=50');
        $canEditByEmail = collect($respUser->json('data'))
            ->mapWithKeys(fn ($row) => [$row['email'] => $row['can_edit']])
            ->all();
        
        $this->assertTrue($canEditByEmail['user@example.com']);
        $this->assertFalse($canEditByEmail['manager@example.com']);
        $this->assertFalse($canEditByEmail['admin@example.com']);
    }
}
