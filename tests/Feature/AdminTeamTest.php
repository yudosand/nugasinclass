<?php

namespace Tests\Feature;

use App\Models\AdminChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminTeamTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;

    private function account(string $role = 'admin'): User
    {
        return User::create(['name' => 'Admin '.++$this->sequence, 'nomor_hp' => '0812345000'.$this->sequence, 'password' => 'Password123!', 'role' => $role, 'status' => 'approved']);
    }

    public function test_eight_character_admin_password_works_but_seven_does_not(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->get('/admin/admins/create')->assertSee('minimal 8 karakter')->assertSee('minlength="8"', false);
        $payload = ['name' => 'Admin Baru', 'nomor_hp' => '08999999999', 'current_password' => 'Password123!', 'password' => 'Pass123', 'password_confirmation' => 'Pass123'];
        $this->post('/admin/admins', $payload)->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
        $this->post('/admin/admins', [...$payload, 'password' => 'Pass1234', 'password_confirmation' => 'Pass1234'])->assertRedirect('/admin/admins');
        $new = User::where('nomor_hp', $payload['nomor_hp'])->firstOrFail();
        $this->assertTrue(Hash::check('Pass1234', $new->password));
        $this->post('/logout');
        $this->post('/login', ['nomor_hp' => $new->nomor_hp, 'password' => 'Pass1234'])->assertRedirect('/admin/dashboard');
    }

    public function test_admin_can_only_update_own_name_and_cannot_inject_other_fields(): void
    {
        $admin = $this->account();
        $other = $this->account();
        $this->actingAs($admin)->get('/admin/profile')->assertOk()->assertSee('Nama Anda');
        $this->put('/admin/profile', ['name' => '   '])->assertSessionHasErrors('name');
        $this->put('/admin/profile', ['name' => str_repeat('a', 256)])->assertSessionHasErrors('name');
        $this->put('/admin/profile', ['name' => 'Admin Operasional', 'id' => $other->id, 'role' => 'client', 'password' => 'ChangedPassword!', 'nomor_hp' => $other->nomor_hp])->assertRedirect('/admin/profile');
        $this->assertSame('Admin Operasional', $admin->fresh()->name);
        $this->assertSame('admin', $admin->fresh()->role);
        $this->assertSame($admin->nomor_hp, $admin->fresh()->nomor_hp);
        $this->assertTrue(Hash::check('Password123!', $admin->fresh()->password));
        $this->assertSame($other->name, $other->fresh()->name);
        $this->get('/admin/profile')->assertSee('Admin Operasional');
    }

    public function test_guest_and_client_cannot_read_post_or_activate_chat_or_edit_admin_profile(): void
    {
        $this->get('/admin/chat')->assertRedirect('/login');
        $this->getJson('/admin/chat/messages')->assertUnauthorized();
        $this->postJson('/admin/chat/messages', [])->assertUnauthorized();
        $this->post('/admin/chat/activate')->assertRedirect('/login');
        $client = $this->account('client');
        $this->actingAs($client)->get('/admin/chat')->assertForbidden();
        $this->getJson('/admin/chat/messages')->assertForbidden();
        $this->postJson('/admin/chat/messages', [])->assertForbidden();
        $this->post('/admin/chat/activate')->assertForbidden();
        $this->get('/admin/profile')->assertForbidden();
        $this->put('/admin/profile', ['name' => 'Injected'])->assertForbidden();
        $this->assertDatabaseCount('admin_chat_messages', 0);
        $this->assertSame($client->name, $client->fresh()->name);
    }

    public function test_existing_site_can_activate_chat_without_artisan_and_repeat_without_data_loss(): void
    {
        Schema::drop('admin_chat_messages');
        $admin = $this->account();
        $this->actingAs($admin)->get('/admin/chat')->assertOk()->assertSee('Aktifkan Chat Admin')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertFalse(Schema::hasTable('admin_chat_messages'));
        $this->getJson('/admin/chat/messages')->assertStatus(409);
        $this->postJson('/admin/chat/messages', [])->assertStatus(409);
        $this->post('/admin/chat/activate')->assertRedirect('/admin/chat')->assertSessionHas('success');
        $this->assertTrue(Schema::hasTable('admin_chat_messages'));
        $this->postJson('/admin/chat/messages', ['body' => 'Pesan pertama', 'client_message_id' => (string) Str::uuid()])->assertCreated();
        $this->post('/admin/chat/activate')->assertRedirect('/admin/chat');
        $migration = require database_path('migrations/2026_09_14_000001_create_admin_chat_messages_table.php');
        $migration->up();
        $this->assertDatabaseCount('admin_chat_messages', 1);
        $this->get('/admin/chat')->assertOk()->assertDontSee('Aktifkan Chat Admin')->assertSee('Kirim Pesan');
    }

    public function test_chat_uses_authenticated_sender_persists_plain_text_and_retries_once(): void
    {
        $admin = $this->account();
        $other = $this->account();
        $payload = ['body' => '<script>alert(1)</script>' . "\nHalo tim 🙏", 'client_message_id' => (string) Str::uuid(), 'user_id' => $other->id, 'sender_name' => 'Forged'];
        $this->actingAs($admin)->postJson('/admin/chat/messages', $payload)->assertCreated()->assertJsonPath('message.mine', true)->assertJsonPath('message.name', $admin->name)->assertJsonPath('message.body', $payload['body']);
        $this->postJson('/admin/chat/messages', $payload)->assertOk();
        $this->assertDatabaseCount('admin_chat_messages', 1);
        $this->assertDatabaseHas('admin_chat_messages', ['user_id' => $admin->id, 'body' => $payload['body']]);
        $this->actingAs($other)->withSession(['password_hash_web' => $other->password])->getJson('/admin/chat/messages')->assertOk()->assertJsonPath('messages.0.mine', false)->assertJsonPath('messages.0.body', $payload['body'])->assertJsonMissingPath('messages.0.nomor_hp');
        $this->postJson('/admin/chat/messages', $payload)->assertStatus(409);
        $this->assertDatabaseCount('admin_chat_messages', 1);
        $admin->update(['name' => 'Nama Admin Baru']);
        $this->getJson('/admin/chat/messages')->assertJsonPath('messages.0.name', 'Nama Admin Baru');
    }

    public function test_chat_cursor_does_not_skip_messages_and_can_load_older_history(): void
    {
        $admin = $this->account();
        for ($i = 1; $i <= 105; $i++) {
            AdminChatMessage::create(['user_id' => $admin->id, 'sender_name' => $admin->name, 'body' => 'Pesan '.$i, 'client_message_id' => (string) Str::uuid()]);
        }
        $this->actingAs($admin)->getJson('/admin/chat/messages')->assertJsonCount(50, 'messages')->assertJsonPath('messages.0.id', 56)->assertJsonPath('messages.49.id', 105)->assertJsonPath('has_more', true);
        $this->getJson('/admin/chat/messages?before=56')->assertJsonCount(50, 'messages')->assertJsonPath('messages.0.id', 6)->assertJsonPath('messages.49.id', 55)->assertJsonPath('has_more', true);
        $this->getJson('/admin/chat/messages?before=6')->assertJsonCount(5, 'messages')->assertJsonPath('has_more', false);
        $this->getJson('/admin/chat/messages?after=0')->assertJsonCount(50, 'messages')->assertJsonPath('messages.0.id', 1)->assertJsonPath('messages.49.id', 50)->assertJsonPath('has_more', true);
        $this->getJson('/admin/chat/messages?after=100')->assertJsonCount(5, 'messages')->assertJsonPath('messages.0.id', 101)->assertJsonPath('has_more', false);
        $this->getJson('/admin/chat/messages?after=105')->assertJsonCount(0, 'messages')->assertJsonPath('has_more', false);
        $this->getJson('/admin/chat/messages?after=1&before=5')->assertUnprocessable();
        $this->getJson('/admin/chat/messages?before=-1')->assertUnprocessable();
    }

    public function test_chat_validates_message_and_limits_post_rate(): void
    {
        $admin = $this->account();
        $this->actingAs($admin)->postJson('/admin/chat/messages', ['body' => '  ', 'client_message_id' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('body');
        $this->postJson('/admin/chat/messages', ['body' => str_repeat('x', 2001), 'client_message_id' => (string) Str::uuid()])->assertUnprocessable();
        $this->postJson('/admin/chat/messages', ['body' => 'Halo', 'client_message_id' => 'invalid'])->assertUnprocessable();
        for ($i = 0; $i < 27; $i++) $this->postJson('/admin/chat/messages', ['body' => 'Halo '.$i, 'client_message_id' => (string) Str::uuid()])->assertCreated();
        $this->postJson('/admin/chat/messages', ['body' => 'Limit', 'client_message_id' => (string) Str::uuid()])->assertStatus(429);
        $this->assertDatabaseCount('admin_chat_messages', 27);
    }

    public function test_polling_does_not_use_up_send_or_account_management_limits(): void
    {
        $admin = $this->account();
        $this->actingAs($admin);
        for ($i = 0; $i < 31; $i++) $this->getJson('/admin/chat/messages')->assertOk();
        $this->postJson('/admin/chat/messages', ['body' => 'Tetap bisa kirim', 'client_message_id' => (string) Str::uuid()])->assertCreated();
        $this->put('/admin/profile', ['name' => 'Admin Setelah Chat'])->assertRedirect('/admin/profile');
        $this->post('/admin/admins', ['name' => 'Admin Kedua', 'nomor_hp' => '08999999999', 'password' => 'Pass1234', 'password_confirmation' => 'Pass1234', 'current_password' => 'Password123!'])->assertRedirect('/admin/admins');
    }
}
