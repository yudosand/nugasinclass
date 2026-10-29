<?php

namespace Tests\Feature;

use App\Models\AcademicProgress;
use App\Models\ClientCourse;
use App\Models\ClientProfile;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackofficeAccountTest extends TestCase
{
    use RefreshDatabase;
    private const OLD = 'OriginalPassword123!';
    private const NEW = 'NewPasswordSecure456!';
    private int $phoneSequence = 0;

    private function user(string $role = 'client'): User
    {
        return User::create(['name' => $role.' Test '.++$this->phoneSequence, 'nomor_hp' => '0812345600'.$this->phoneSequence, 'role' => $role, 'status' => 'approved', 'password' => self::OLD]);
    }

    private function preview(string $name, string $html): void
    {
        if ($directory = getenv('NUGASIN_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0755, true);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }

    public function test_client_can_change_password_only_with_correct_old_password(): void
    {
        $client = $this->user();
        $this->actingAs($client)->get('/account/password')->assertOk()->assertSee('Password lama');
        $this->put('/account/password', ['current_password' => 'wrong', 'password' => self::NEW, 'password_confirmation' => self::NEW])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check(self::OLD, $client->fresh()->password));
        $token = Password::createToken($client);
        $this->put('/account/password', ['current_password' => self::OLD, 'password' => self::NEW, 'password_confirmation' => self::NEW])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertTrue(Hash::check(self::NEW, $client->fresh()->password));
        $this->assertFalse(Password::tokenExists($client, $token));
    }

    public function test_forgot_password_explains_verified_whatsapp_recovery(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertOk()->assertSee('6285861015161')->assertSee('memverifikasi');
        $this->get('/login')->assertSee('Lupa password?');
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->preview('forgot-password', $response->getContent());
    }

    public function test_admin_creates_hashed_one_time_reset_link_and_client_can_reset(): void
    {
        config(['app.url' => 'https://nugasinclass.web.id']);
        $admin = $this->user('admin');
        $client = $this->user();
        $this->actingAs($admin)->post('/admin/clients/'.$client->id.'/reset-password', ['current_password' => self::OLD, 'identity_verified' => '1'])->assertSessionHas('reset_link');
        $url = session('reset_link');
        $this->assertStringStartsWith('https://nugasinclass.web.id/reset-password/', $url);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertTrue(Password::tokenExists($client, $token));
        $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $client->nomor_hp)->value('token'));
        $detail = $this->get('/admin/clients/'.$client->id);
        $detail->assertOk()->assertSee('Tautan Reset Password Client')->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post('/logout');
        $this->get('/reset-password/'.$token.'?nomor_hp='.$client->nomor_hp)->assertOk()->assertHeader('Referrer-Policy', 'no-referrer');
        $payload = ['token' => $token, 'nomor_hp' => $client->nomor_hp, 'password' => self::NEW, 'password_confirmation' => self::NEW];
        $this->post('/reset-password', $payload)->assertRedirect('/login')->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check(self::NEW, $client->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        $this->post('/login', ['nomor_hp' => $client->nomor_hp, 'password' => self::NEW])->assertRedirect('/onboarding');
        $this->assertAuthenticatedAs($client);
    }

    public function test_reset_rejects_expired_wrong_account_and_admin_tokens(): void
    {
        $client = $this->user();
        $other = $this->user();
        $token = Password::createToken($client);
        $payload = ['token' => $token, 'nomor_hp' => $other->nomor_hp, 'password' => self::NEW, 'password_confirmation' => self::NEW];
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/reset-password', [...$payload, 'nomor_hp' => $client->nomor_hp])->assertSessionHasErrors('token');
        $admin = $this->user('admin');
        $this->post('/reset-password', [...$payload, 'token' => Password::createToken($admin), 'nomor_hp' => $admin->nomor_hp])->assertSessionHasErrors('token');
        $this->assertTrue(Hash::check(self::OLD, $client->fresh()->password));
        $this->assertTrue(Hash::check(self::OLD, $admin->fresh()->password));
    }

    public function test_reset_generation_requires_admin_password_and_identity_confirmation(): void
    {
        $admin = $this->user('admin');
        $client = $this->user();
        $url = '/admin/clients/'.$client->id.'/reset-password';
        $this->actingAs($admin)->post($url, ['current_password' => 'wrong', 'identity_verified' => '1'])->assertSessionHasErrors('current_password');
        $this->post($url, ['current_password' => self::OLD])->assertSessionHasErrors('identity_verified');
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->post($url, ['current_password' => self::OLD, 'identity_verified' => '1']);
        $first = basename(parse_url(session('reset_link'), PHP_URL_PATH));
        $this->post($url, ['current_password' => self::OLD, 'identity_verified' => '1']);
        $this->assertFalse(Password::tokenExists($client, $first));
    }

    public function test_admin_list_and_create_with_duplicate_and_password_protection(): void
    {
        $admin = $this->user('admin');
        $client = $this->user();
        $this->actingAs($admin)->get('/admin/admins')->assertOk()->assertSee($admin->name)->assertDontSee($client->name);
        $create = $this->get('/admin/admins/create');
        $create->assertOk();
        $this->preview('add-admin', $create->getContent());
        $payload = ['name' => 'Admin Operasional', 'nomor_hp' => '089999999999', 'password' => self::NEW, 'password_confirmation' => self::NEW, 'current_password' => 'wrong'];
        $this->post('/admin/admins', $payload)->assertSessionHasErrors('current_password');
        $this->post('/admin/admins', [...$payload, 'current_password' => self::OLD, 'nomor_hp' => $client->nomor_hp])->assertSessionHasErrors('nomor_hp');
        $this->post('/admin/admins', [...$payload, 'current_password' => self::OLD, 'role' => 'client'])->assertRedirect('/admin/admins');
        $new = User::where('nomor_hp', '089999999999')->firstOrFail();
        $this->assertSame('admin', $new->role);
        $this->assertSame('approved', $new->status);
        $this->assertTrue(Hash::check(self::NEW, $new->password));
        $this->post('/logout');
        $this->post('/login', ['nomor_hp' => $new->nomor_hp, 'password' => self::NEW])->assertRedirect('/admin/dashboard');
    }

    public function test_client_cannot_manage_admins_reset_other_accounts_or_delete_clients(): void
    {
        $client = $this->user();
        $other = $this->user();
        $this->actingAs($client)->get('/admin/admins')->assertForbidden();
        $this->get('/admin/admins/create')->assertForbidden();
        $this->post('/admin/admins', [])->assertForbidden();
        $this->get('/admin/clients/'.$other->id.'/delete')->assertForbidden();
        $this->delete('/admin/clients/'.$other->id, [])->assertForbidden();
        $this->post('/admin/clients/'.$other->id.'/reset-password', [])->assertForbidden();
        $this->assertDatabaseCount('users', 2);
    }

    public function test_client_deletion_cascades_data_and_proofs_without_affecting_other_clients(): void
    {
        Storage::fake('public');
        $admin = $this->user('admin');
        $client = $this->user();
        $other = $this->user();
        ClientProfile::create(['user_id' => $client->id]);
        $package = Package::create(['name' => 'Paket Test', 'slug' => 'paket-test', 'price' => 100000]);
        Order::create(['user_id' => $client->id, 'package_id' => $package->id, 'order_number' => 'TEST-DELETE', 'amount' => 100000, 'payment_proof' => 'payment_proofs/test.png']);
        $course = ClientCourse::create(['user_id' => $client->id, 'course_name' => 'Test Course']);
        AcademicProgress::create(['client_course_id' => $course->id, 'session_number' => 1]);
        Storage::disk('public')->put('payment_proofs/test.png', 'test');
        Storage::disk('public')->put('payment_proofs/other.png', 'other');
        Password::createToken($client);
        $this->actingAs($admin)->get('/admin/clients/'.$client->id.'/delete')->assertOk()->assertSee('Penghapusan permanen');
        $this->delete('/admin/clients/'.$client->id, ['current_password' => self::OLD, 'confirm_phone' => 'wrong'])->assertSessionHasErrors('confirm_phone');
        $this->assertDatabaseHas('users', ['id' => $client->id]);
        $this->delete('/admin/clients/'.$client->id, ['current_password' => self::OLD, 'confirm_phone' => $client->nomor_hp])->assertRedirect('/admin/clients');
        foreach (['orders', 'client_profiles', 'client_courses', 'academic_progresses', 'password_reset_tokens'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertDatabaseMissing('users', ['id' => $client->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
        $this->assertDatabaseHas('packages', ['id' => $package->id]);
        Storage::disk('public')->assertMissing('payment_proofs/test.png');
        Storage::disk('public')->assertExists('payment_proofs/other.png');
        $this->delete('/admin/clients/'.$admin->id, ['current_password' => self::OLD, 'confirm_phone' => $admin->nomor_hp])->assertNotFound();
    }

    public function test_changed_password_invalidates_an_established_session(): void
    {
        $client = $this->user();
        $oldHash = $client->password;
        $client->update(['password' => self::NEW]);
        $this->actingAs($client->fresh())->withSession(['password_hash_web' => $oldHash])->get('/account/password')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_reset_submission_is_throttled(): void
    {
        $client = $this->user();
        $data = ['token' => 'invalid', 'nomor_hp' => $client->nomor_hp, 'password' => self::NEW, 'password_confirmation' => self::NEW];
        for ($i = 0; $i < 6; $i++) $this->post('/reset-password', $data)->assertStatus(302);
        $this->post('/reset-password', $data)->assertStatus(429);
    }

    public function test_dashboard_does_not_count_blank_precreated_sessions_and_tracks_all_courses(): void
    {
        $admin = $this->user('admin');
        $client = $this->user();
        $client->update(['name' => 'Client Progres']);
        $course = ClientCourse::create(['user_id' => $client->id, 'course_name' => 'Matematika']);
        for ($s = 1; $s <= 8; $s++) AcademicProgress::create(['client_course_id' => $course->id, 'session_number' => $s, 'discussion_status' => 'Belum Dikerjakan', 'assignment_status' => 'Belum Dikerjakan']);
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertViewHas('progressClients', fn ($clients) => $clients->first()->session_summary['latest'] === 0);
        $course->progresses()->where('session_number', 1)->update(['discussion_status' => 'Selesai', 'assignment_status' => 'Selesai']);
        $course->progresses()->where('session_number', 4)->update(['discussion_status' => 'On Progress']);
        // Empty second course means client-wide completion must remain zero.
        $second = ClientCourse::create(['user_id' => $client->id, 'course_name' => 'Algoritma']);
        $response = $this->get('/admin/dashboard');
        $response->assertOk()->assertSee('Sesi 4 / 8')->assertViewHas('progressClients', fn ($clients) => $clients->first()->session_summary['completed'] === 0);
        AcademicProgress::create(['client_course_id' => $second->id, 'session_number' => 1, 'discussion_status' => 'Selesai', 'assignment_status' => 'Selesai']);
        $response = $this->get('/admin/dashboard');
        $response->assertViewHas('progressClients', fn ($clients) => $clients->first()->session_summary['completed'] === 1);
        $this->preview('backoffice-dashboard', $response->getContent());
        $this->get('/admin/dashboard?client_search=NoMatch')->assertOk()->assertViewHas('progressClients', fn ($clients) => $clients->isEmpty());
    }

    public function test_admin_can_claim_client_handler_and_dashboard_shows_owner(): void
    {
        $admin = $this->user('admin');
        $otherAdmin = $this->user('admin');
        $client = $this->user();
        $client->update(['name' => 'Client Handle Saya']);

        $this->actingAs($admin)->get('/admin/academic')
            ->assertOk()
            ->assertSee('Ambil Client')
            ->assertSee('Belum Onboarding');

        $this->post('/admin/academic/client/'.$client->id.'/claim')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame($admin->id, $client->fresh()->handler_admin_id);

        $this->get('/admin/academic')
            ->assertOk()
            ->assertSee('Saya yang handle')
            ->assertDontSee('Ambil Client');

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Handler')
            ->assertSee('Saya');

        $this->post('/logout');

        $this->actingAs($otherAdmin)->post('/admin/academic/client/'.$client->id.'/claim')
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame($admin->id, $client->fresh()->handler_admin_id);

        $this->get('/admin/dashboard')
            ->assertOk()
            ->assertSee($admin->name)
            ->assertDontSee('Saya yang handle');
    }

    public function test_current_handler_can_release_client_for_other_admin_to_claim(): void
    {
        $admin = $this->user('admin');
        $otherAdmin = $this->user('admin');
        $client = $this->user();
        $client->forceFill(['handler_admin_id' => $admin->id])->save();

        $this->actingAs($otherAdmin)->delete('/admin/academic/client/'.$client->id.'/claim')
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame($admin->id, $client->fresh()->handler_admin_id);

        $this->post('/logout');

        $this->actingAs($admin)->get('/admin/academic')
            ->assertOk()
            ->assertSee('Saya yang handle')
            ->assertSee('Lepas');

        $this->delete('/admin/academic/client/'.$client->id.'/claim')
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertNull($client->fresh()->handler_admin_id);

        $this->post('/logout');

        $this->actingAs($otherAdmin)->post('/admin/academic/client/'.$client->id.'/claim')
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertSame($otherAdmin->id, $client->fresh()->handler_admin_id);
    }
}
