<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountRecoveryTest extends TestCase
{
    use RefreshDatabase;
    private const OLD = 'OriginalPassword123!';
    private int $sequence = 0;

    private function member(string $role): User
    {
        $id = ++$this->sequence;
        return User::create(['name' => 'Account '.$id, 'nomor_hp' => $role === 'marketing' ? null : '0812345000'.$id,
            'email' => 'account'.$id.'@example.test', 'password' => self::OLD, 'role' => $role, 'status' => 'approved']);
    }

    public static function roles(): array
    {
        return [['admin', '/login', 'NewPass8'], ['client', '/login', 'NewPassword12!'],
            ['marketing', '/marketing/login', 'NewPass8'], ['student', '/study-tracker/login', 'NewPass8']];
    }

    #[DataProvider('roles')]
    public function test_verified_reset_works_once_for_each_role(string $role, string $login, string $password): void
    {
        config(['app.url' => 'https://nugasinclass.web.id']);
        $helper = $this->member('admin'); $target = $this->member($role);
        $route = '/admin/accounts/'.$target->id.'/reset-password';
        $this->actingAs($helper)->withSession(['password_hash_web' => $helper->password]);
        $this->get($route)->assertOk()->assertSee($target->name)->assertDontSee(self::OLD)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->post($route, ['identity_verified' => 1, 'current_password' => self::OLD])->assertSessionHas('account_reset_link');
        $url = session('account_reset_link');
        $this->assertStringStartsWith('https://nugasinclass.web.id/reset-password/', $url);
        $token = basename(parse_url($url, PHP_URL_PATH));
        $this->assertTrue(Password::tokenExists($target, $token));
        $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $target->getEmailForPasswordReset())->value('token'));
        $this->get($route)->assertSee($url)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post('/logout');
        $this->get($url)->assertOk()->assertSee('name="account_id"', false)->assertDontSee($target->email)
            ->assertDontSee($target->password)->assertHeader('Cache-Control', 'no-store, private');
        $payload = ['token' => $token, 'account_id' => $target->id, 'password' => $password, 'password_confirmation' => $password, 'role' => 'admin'];
        $this->post('/reset-password', $payload)->assertRedirect($login)->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check($password, $target->fresh()->password));
        $this->assertSame($role, $target->fresh()->role);
        $this->assertSame('approved', $target->fresh()->status);
        $this->assertGuest();
        $this->assertFalse(Password::tokenExists($target, $token));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        $loginData = match ($role) {
            'marketing' => ['email' => $target->email],
            'student' => ['identity' => $target->email],
            default => ['nomor_hp' => $target->nomor_hp],
        };
        $this->post($login, [...$loginData, 'password' => $password])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($target);
    }

    public function test_only_verified_admin_can_issue_tokens(): void
    {
        $target = $this->member('admin');
        $route = '/admin/accounts/'.$target->id.'/reset-password';
        $payload = ['identity_verified' => 1, 'current_password' => self::OLD];
        $this->get($route)->assertRedirect('/login');
        $this->post($route, $payload)->assertRedirect('/login');
        foreach (['client', 'marketing', 'student'] as $role) {
            $member = $this->member($role);
            $this->actingAs($member)->withSession(['password_hash_web' => $member->password]);
            $this->get($route)->assertForbidden();
            $this->post($route, $payload)->assertForbidden();
        }
        $this->actingAs($target)->withSession(['password_hash_web' => $target->password]);
        $this->post($route, ['current_password' => self::OLD])->assertSessionHasErrors('identity_verified');
        $this->post($route, [...$payload, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertDatabaseCount('password_reset_tokens', 0);
    }

    public function test_token_cannot_reset_another_account_or_be_used_after_replacement_or_expiry(): void
    {
        $helper = $this->member('admin'); $target = $this->member('admin'); $other = $this->member('marketing');
        $route = '/admin/accounts/'.$target->id.'/reset-password';
        $this->actingAs($helper)->withSession(['password_hash_web' => $helper->password]);
        $issue = ['identity_verified' => 1, 'current_password' => self::OLD];
        $this->post($route, $issue);
        $old = basename(parse_url(session('account_reset_link'), PHP_URL_PATH));
        $this->post($route, $issue);
        $token = basename(parse_url(session('account_reset_link'), PHP_URL_PATH));
        $this->post('/logout');
        $payload = ['account_id' => $target->id, 'token' => $token, 'password' => 'NewPass8', 'password_confirmation' => 'NewPass8'];
        $this->post('/reset-password', [...$payload, 'account_id' => $other->id])->assertSessionHasErrors('token');
        $this->post('/reset-password', [...$payload, 'token' => $old])->assertSessionHasErrors('token');
        DB::table('password_reset_tokens')->update(['created_at' => now()->subMinutes(61)]);
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
        foreach ([$target, $other] as $user) $this->assertTrue(Hash::check(self::OLD, $user->fresh()->password));
    }

    public function test_reset_does_not_reactivate_disabled_account_and_invalidates_old_session(): void
    {
        $target = $this->member('student'); $target->update(['status' => 'rejected']);
        $oldHash = $target->password;
        $token = Password::createToken($target);
        $this->post('/reset-password', ['account_id' => $target->id, 'token' => $token, 'password' => 'NewPass8', 'password_confirmation' => 'NewPass8'])->assertRedirect('/study-tracker/login');
        $this->assertSame('rejected', $target->fresh()->status);
        $this->post('/study-tracker/login', ['identity' => $target->email, 'password' => 'NewPass8'])->assertSessionHasErrors('identity');
        $this->actingAs($target->fresh())->withSession(['password_hash_web' => $oldHash])->get('/account/password')->assertRedirect('/login');
    }

    public function test_short_password_rejection_and_existing_client_minimum_are_preserved(): void
    {
        foreach (['admin', 'client'] as $role) {
            $target = $this->member($role); $token = Password::createToken($target);
            $password = $role === 'admin' ? 'Short77' : 'Only8888';
            $this->post('/reset-password', ['account_id' => $target->id, 'token' => $token, 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
            $this->assertTrue(Password::tokenExists($target, $token));
        }
    }

    public function test_all_login_pages_link_to_shared_recovery(): void
    {
        foreach (['/login', '/marketing/login', '/study-tracker/login'] as $url) {
            $this->get($url)->assertOk()->assertSee('/forgot-password', false)->assertSee('Lupa password?');
        }
    }
}
