<?php

namespace Tests\Feature;

use App\Models\ElearningCredential;
use App\Models\User;
use App\Support\ElearningSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ElearningSecurityTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private const SECRET = '  KampusSecret<>&123!  ';

    private function user(string $role = 'client', string $status = 'approved'): User
    {
        return User::create(['name' => 'Fixture '.++$this->sequence, 'nomor_hp' => '0812222200'.$this->sequence, 'password' => 'AdminPassword123!', 'role' => $role, 'status' => $status]);
    }

    private function data(array $extra = []): array
    {
        return array_replace(['universitas' => 'Universitas Uji', 'nim' => '12345678', 'program_studi' => 'Informatika', 'semester' => 2, 'agree_terms' => '1', 'elearning_login' => 'nim@example.test', 'elearning_password' => self::SECRET], $extra);
    }

    private function replacement(ElearningCredential $credential): array
    {
        return ['current_password'=>'AdminPassword123!', 'elearning_password'=>'  Corrected<>&Secret!  ',
            'elearning_password_confirmation'=>'  Corrected<>&Secret!  ', 'revision'=>$credential->revision(), 'confirmed'=>1];
    }

    public function test_admin_can_correct_encrypted_password_without_changing_login_or_leaking_secrets(): void
    {
        $client=$this->user(); $admin=$this->user('admin');
        $credential=ElearningCredential::create(['user_id'=>$client->id,'login'=>'private@example.test','password'=>self::SECRET,'consented_at'=>now()->subDay()]);
        $login=$credential->getRawOriginal('login'); $consent=$credential->consented_at; $hash=$client->password;
        $payload=$this->replacement($credential); $url='/admin/clients/'.$client->id.'/elearning';
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('Perbaiki Password E-Learning')->assertDontSee(self::SECRET,false);
        $response=$this->putJson($url,[...$payload,'user_id'=>$admin->id]);
        $response->assertOk()->assertHeader('Cache-Control','no-store, private')->assertDontSee($payload['elearning_password'],false)->assertDontSee('private@example.test');
        $credential->refresh();
        $this->assertSame($payload['elearning_password'],$credential->password);
        $this->assertNotSame($payload['elearning_password'],$credential->getRawOriginal('password'));
        $this->assertSame($login,$credential->getRawOriginal('login'));
        $this->assertTrue($consent->equalTo($credential->consented_at));
        $this->assertSame($hash,$client->fresh()->password);
        $this->assertDatabaseHas('elearning_change_logs',['admin_id'=>$admin->id,'client_id'=>$client->id]);
        $this->assertStringNotContainsString('Corrected',json_encode(DB::table('elearning_change_logs')->get()));
        $this->get($url)->assertOk()->assertSee('Perubahan Password Terakhir')->assertDontSee('Corrected',false)->assertDontSee('private@example.test');
        $this->postJson($url,['current_password'=>'AdminPassword123!'])->assertOk()->assertJsonPath('password',$payload['elearning_password']);
        $this->putJson($url,$payload)->assertStatus(409);
        $this->assertDatabaseCount('elearning_change_logs',1);
    }

    public function test_password_correction_requires_confirmation_admin_password_and_matching_fields(): void
    {
        $client=$this->user(); $credential=ElearningCredential::create(['user_id'=>$client->id,'login'=>'test','password'=>self::SECRET,'consented_at'=>now()]);
        $this->actingAs($this->user('admin')); $url='/admin/clients/'.$client->id.'/elearning'; $payload=$this->replacement($credential);
        $this->putJson($url,[...$payload,'current_password'=>'wrong'])->assertUnprocessable();
        $this->putJson($url,[...$payload,'elearning_password_confirmation'=>'different'])->assertUnprocessable();
        $this->putJson($url,[...$payload,'confirmed'=>0])->assertUnprocessable();
        $this->putJson($url,[...$payload,'elearning_password'=>str_repeat('a',1025)])->assertUnprocessable();
        $this->put($url,$payload)->assertStatus(406);
        $this->assertArrayNotHasKey('elearning_password',session()->getOldInput());
        $this->assertArrayNotHasKey('elearning_password_confirmation',session()->getOldInput());
        $this->assertSame(self::SECRET,$credential->fresh()->password);
        $this->putJson($url,$payload)->assertStatus(429);
    }

    public function test_password_correction_rejects_guests_other_roles_and_missing_csrf(): void
    {
        $client=$this->user(); $credential=ElearningCredential::create(['user_id'=>$client->id,'login'=>'test','password'=>self::SECRET,'consented_at'=>now()]);
        $url='/admin/clients/'.$client->id.'/elearning'; $payload=$this->replacement($credential);
        $this->putJson($url,$payload)->assertUnauthorized();
        foreach (['client','marketing','student'] as $role) {
            $user=$this->user($role);
            $this->actingAs($user)->withSession(['password_hash_web'=>$user->password])->putJson($url,$payload)->assertForbidden();
        }
        $admin=$this->user('admin'); $this->app['env']='local';
        $this->actingAs($admin)->withSession(['password_hash_web'=>$admin->password,'_token'=>'csrf-test'])->putJson($url,$payload)->assertStatus(419);
        $this->assertSame(self::SECRET,$credential->fresh()->password);
    }

    public function test_password_change_rolls_back_if_audit_fails_and_detects_client_updates(): void
    {
        $client=$this->user(); $credential=ElearningCredential::create(['user_id'=>$client->id,'login'=>'test','password'=>self::SECRET,'consented_at'=>now()]);
        $this->actingAs($this->user('admin')); $url='/admin/clients/'.$client->id.'/elearning'; $payload=$this->replacement($credential);
        ElearningSchema::ensureChangeLog();
        DB::unprepared("CREATE TRIGGER fail_change_log BEFORE INSERT ON elearning_change_logs BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        $this->putJson($url,$payload)->assertStatus(500);
        $this->assertSame(self::SECRET,$credential->fresh()->password);
        DB::unprepared('DROP TRIGGER fail_change_log');
        $credential->update(['password'=>'UpdatedByClient!']);
        $this->putJson($url,$payload)->assertStatus(409);
        $this->assertSame('UpdatedByClient!',$credential->fresh()->password);
        $this->assertDatabaseCount('elearning_change_logs',0);
    }

    public function test_onboarding_encrypts_both_fields_and_never_serializes_or_prefills_secrets(): void
    {
        $client = $this->user();
        $other = $this->user();
        $this->actingAs($client)->post('/onboarding', $this->data(['user_id' => $other->id]))->assertRedirect('/client/dashboard');
        $raw = DB::table('client_elearning_credentials')->where('user_id', $client->id)->first();
        $this->assertNotSame(self::SECRET, $raw->password);
        $this->assertSame(self::SECRET, Crypt::decryptString($raw->password));
        $this->assertNotSame('nim@example.test', $raw->login);
        $this->assertSame('nim@example.test', Crypt::decryptString($raw->login));
        $model = ElearningCredential::firstOrFail();
        $this->assertArrayNotHasKey('password', $model->toArray());
        $this->assertArrayNotHasKey('login', $model->toArray());
        $this->assertDatabaseMissing('client_elearning_credentials', ['user_id' => $other->id]);
        $this->get('/onboarding')->assertOk()->assertDontSee(self::SECRET, false)->assertDontSee('nim@example.test')->assertSee('Kosongkan kedua kolom');
        $this->get('/client/dashboard')->assertOk()->assertDontSee(self::SECRET, false)->assertDontSee('nim@example.test');
    }

    public function test_consent_and_both_credentials_required_and_secrets_not_flashed_after_invalid_form(): void
    {
        $this->actingAs($this->user());
        $this->post('/onboarding', $this->data(['agree_terms' => '0']))->assertSessionHasErrors('agree_terms');
        $this->assertArrayNotHasKey('elearning_password', session()->getOldInput());
        $this->assertArrayNotHasKey('elearning_login', session()->getOldInput());
        $this->post('/onboarding', $this->data(['elearning_password' => '']))->assertSessionHasErrors('elearning_password');
        $this->post('/onboarding', $this->data(['elearning_login' => '']))->assertSessionHasErrors('elearning_login');
        $this->post('/onboarding', $this->data(['elearning_password' => str_repeat('a', 1025)]))->assertSessionHasErrors('elearning_password');
        $this->post('/onboarding', $this->data(['universitas' => '']))->assertSessionHasErrors('universitas');
        $this->assertArrayNotHasKey('elearning_password', session()->getOldInput());
        $this->get('/onboarding')->assertDontSee(self::SECRET, false)->assertDontSee('nim@example.test');
        $this->assertDatabaseCount('client_profiles', 0);
        $this->assertDatabaseCount('client_elearning_credentials', 0);
    }

    public function test_existing_access_can_be_kept_or_replaced_and_edit_onboarding_is_available(): void
    {
        $this->actingAs($this->user())->post('/onboarding', $this->data())->assertRedirect();
        $this->post('/onboarding', $this->data(['elearning_login' => '', 'elearning_password' => '', 'semester' => 3]))->assertRedirect('/client/dashboard');
        $this->assertSame(self::SECRET, ElearningCredential::first()->password);
        $this->post('/onboarding', $this->data(['elearning_login' => 'updated@example.test', 'elearning_password' => 'New!Secret']))->assertRedirect('/client/dashboard');
        $this->assertSame('New!Secret', ElearningCredential::first()->password);
        $this->assertDatabaseCount('client_elearning_credentials', 1);
        $this->assertDatabaseHas('client_profiles', ['semester' => 2]);
    }

    public function test_only_approved_clients_can_submit_onboarding(): void
    {
        $this->get('/onboarding')->assertRedirect('/login');
        foreach ([['admin', 'approved'], ['client', 'pending_payment'], ['client', 'rejected'], ['client', 'pending_approval']] as [$role, $status]) {
            $user = $this->user($role, $status);
            $this->actingAs($user)->withSession(['password_hash_web' => $user->password])->get('/onboarding')->assertForbidden();
            $this->post('/onboarding', $this->data())->assertForbidden();
        }
        $this->assertDatabaseCount('client_elearning_credentials', 0);
    }

    public function test_reveal_requires_admin_password_is_audited_and_never_preloaded(): void
    {
        $client = $this->user();
        ElearningCredential::create(['user_id' => $client->id, 'login' => 'private@example.test', 'password' => self::SECRET, 'consented_at' => now()]);
        $admin = $this->user('admin');
        $url = '/admin/clients/'.$client->id.'/elearning';
        $this->actingAs($admin)->get('/admin/clients/'.$client->id)->assertOk()->assertSee('Buka Akses E-Learning')->assertDontSee(self::SECRET, false)->assertDontSee('private@example.test');
        $this->get($url)->assertOk()->assertDontSee(self::SECRET, false)->assertDontSee('private@example.test')->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Content-Security-Policy', "default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; connect-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
        $this->postJson($url, ['current_password' => 'Wrong!'])->assertUnprocessable()->assertDontSee(self::SECRET, false);
        $this->assertDatabaseCount('elearning_access_logs', 0);
        $response = $this->postJson($url, ['current_password' => 'AdminPassword123!']);
        $response->assertOk()->assertJsonPath('password', self::SECRET)->assertJsonPath('login', 'private@example.test')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseHas('elearning_access_logs', ['admin_id' => $admin->id, 'client_id' => $client->id]);
        $this->assertStringNotContainsString(self::SECRET, json_encode(DB::table('elearning_access_logs')->get()));
        $this->assertArrayNotHasKey('current_password', session()->getOldInput());
        $this->post($url, ['current_password' => 'AdminPassword123!'])->assertStatus(406);
    }

    public function test_client_and_guest_cannot_reveal_or_activate_even_with_correct_admin_password(): void
    {
        $client = $this->user();
        $url = '/admin/clients/'.$client->id.'/elearning';
        $this->get($url)->assertRedirect('/login');
        $this->postJson($url, ['current_password' => 'AdminPassword123!'])->assertUnauthorized();
        $this->actingAs($client)->get($url)->assertForbidden();
        $this->postJson($url, ['current_password' => 'AdminPassword123!'])->assertForbidden();
        $this->post('/admin/elearning/activate')->assertForbidden();
    }

    public function test_reveal_attempts_are_limited_and_corrupt_encryption_fails_closed(): void
    {
        $client = $this->user();
        $credential = ElearningCredential::create(['user_id' => $client->id, 'login' => 'private@example.test', 'password' => self::SECRET, 'consented_at' => now()]);
        DB::table('client_elearning_credentials')->where('id', $credential->id)->update(['password' => 'broken-ciphertext']);
        $url = '/admin/clients/'.$client->id.'/elearning';
        $this->actingAs($this->user('admin'))->postJson($url, ['current_password' => 'AdminPassword123!'])->assertStatus(503)->assertDontSee('broken-ciphertext');
        for ($i = 0; $i < 4; $i++) $this->postJson($url, ['current_password' => 'Wrong!'])->assertUnprocessable();
        $this->postJson($url, ['current_password' => 'AdminPassword123!'])->assertStatus(429);
        $this->assertDatabaseCount('elearning_access_logs', 0);
    }

    public function test_existing_database_activation_is_idempotent_and_delete_cascades_secrets(): void
    {
        Schema::drop('elearning_access_logs'); Schema::drop('client_elearning_credentials');
        $client = $this->user();
        $admin = $this->user('admin');
        $this->actingAs($client)->get('/onboarding')->assertOk()->assertSee('Form belum tersedia')->assertDontSee('name="elearning_password"', false);
        $this->actingAs($admin)->withSession(['password_hash_web' => $admin->password])->post('/admin/elearning/activate')->assertRedirect();
        $this->assertTrue(ElearningSchema::ready());
        ElearningCredential::create(['user_id' => $client->id, 'login' => 'test', 'password' => self::SECRET, 'consented_at' => now()]);
        $this->post('/admin/elearning/activate')->assertRedirect();
        $migration = require database_path('migrations/2026_09_14_000002_create_elearning_credentials_tables.php'); $migration->up();
        $this->assertDatabaseCount('client_elearning_credentials', 1);
        $this->delete('/admin/clients/'.$client->id, ['current_password' => 'AdminPassword123!', 'confirm_phone' => $client->nomor_hp])->assertRedirect('/admin/clients');
        $this->assertDatabaseCount('client_elearning_credentials', 0);
    }

    public function test_production_http_redirects_and_refuses_submissions_without_trusting_forwarded_proto(): void
    {
        $this->app['env'] = 'production'; config(['app.url' => 'https://nugasinclass.web.id']);
        $this->get('http://nugasinclass.web.id/onboarding')->assertRedirect('https://nugasinclass.web.id/onboarding');
        $this->withHeader('X-Forwarded-Proto', 'https')->post('http://nugasinclass.web.id/onboarding', $this->data())->assertStatus(426)->assertDontSee(self::SECRET, false);
        $this->assertDatabaseCount('client_elearning_credentials', 0);
        $this->get('https://nugasinclass.web.id/login')->assertOk()->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    public function test_csrf_is_required_for_save_and_admin_reveal_outside_test_bypass(): void
    {
        $this->app['env'] = 'local';
        $client = $this->user();
        $this->actingAs($client)->withSession(['_token' => 'fixture-token'])->post('/onboarding', $this->data())->assertStatus(419);
        $this->assertDatabaseCount('client_elearning_credentials', 0);
        $this->withSession(['_token' => 'fixture-token'])->post('/onboarding', $this->data(['_token' => 'fixture-token']))->assertRedirect('/client/dashboard');
        $admin = $this->user('admin');
        $this->actingAs($admin)->withSession(['password_hash_web' => $admin->password, '_token' => 'fixture-token'])->postJson('/admin/clients/'.$client->id.'/elearning', ['current_password' => 'AdminPassword123!'])->assertStatus(419);
        $this->assertDatabaseCount('elearning_access_logs', 0);
    }
}
