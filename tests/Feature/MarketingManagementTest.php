<?php

namespace Tests\Feature;

use App\Models\{User, Order, Package, CuaninWithdrawal};
use App\Support\{Cuanin, CuaninSchema};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Password, Schema};
use Illuminate\Support\Str;
use Tests\TestCase;

class MarketingManagementTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private const PASSWORD = 'MarketingTest123!';

    private function user(string $role = 'marketing'): User
    {
        $n = ++$this->sequence;
        $user = User::create(['name' => 'Member '.$n, 'email' => 'member'.$n.'@example.test', 'nomor_hp' => $role === 'marketing' ? null : '0812345678'.$n, 'role' => $role, 'status' => 'approved', 'password' => self::PASSWORD]);
        if ($role === 'marketing') {
            Cuanin::wallet($user);
            DB::table('referral_codes')->insert(['user_id' => $user->id, 'code' => 'PARTNER'.$n, 'created_at' => now(), 'updated_at' => now()]);
        }
        return $user;
    }

    private function as(User $user): static
    {
        return $this->actingAs($user)->withSession(['password_hash_web' => $user->password]);
    }

    private function payload(User $user): array
    {
        return ['confirm_email' => $user->email, 'current_password' => self::PASSWORD];
    }

    private function credit(User $user, int $amount): void
    {
        DB::transaction(function () use ($user, $amount) {
            Cuanin::lock(); Cuanin::entry(Cuanin::wallet($user), $amount, 'test', (string) Str::uuid(), 'Test fixture');
        });
    }

    public function test_admin_sees_count_identity_referral_and_search_without_password_or_other_roles(): void
    {
        $admin = $this->user('admin'); $m = $this->user(); $other = $this->user(); $client = $this->user('client');
        DB::table('referral_conversions')->insert(['owner_id' => $m->id, 'client_id' => $client->id, 'created_at' => now()]);
        $response = $this->as($admin)->get('/admin/marketing');
        $response->assertOk()->assertSee('Jumlah Marketing')->assertSee($m->email)->assertSee($other->email)->assertSee('PARTNER2')->assertDontSee($client->email)->assertDontSee(self::PASSWORD)->assertDontSee($m->password)->assertViewHas('totalMarketing', 2);
        $response->assertViewHas('marketing', fn ($rows) => (int) $rows->firstWhere('id', $m->id)->successful_clients === 1);
        foreach ([$m->name, $m->email, 'PARTNER2'] as $search) $this->get('/admin/marketing?search='.urlencode($search))->assertOk()->assertSee($m->email)->assertDontSee($other->email)->assertViewHas('totalMarketing', 2);
        $this->get('/admin/marketing?search=NO_MATCH')->assertOk()->assertSee('Marketing tidak ditemukan');
        $this->get('/admin/marketing?search[]=invalid')->assertSessionHasErrors('search');
        $this->get('/admin/dashboard')->assertSee('/admin/marketing', false);
    }

    public function test_guests_clients_and_marketing_cannot_list_or_delete_and_other_roles_are_not_targets(): void
    {
        $m = $this->user(); $client = $this->user('client'); $admin = $this->user('admin');
        $this->get('/admin/marketing')->assertRedirect('/login');
        foreach ([$m, $client] as $actor) {
            $this->as($actor)->get('/admin/marketing')->assertForbidden();
            $this->get('/admin/marketing/'.$m->id.'/delete')->assertForbidden();
            $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertForbidden();
        }
        foreach ([$client, $admin] as $target) {
            $this->as($admin)->get('/admin/marketing/'.$target->id.'/delete')->assertNotFound();
            $this->delete('/admin/marketing/'.$target->id, $this->payload($target))->assertNotFound();
        }
        $this->assertSame('marketing', $m->fresh()->role);
    }

    public function test_delete_requires_password_and_email_then_revokes_login_and_existing_sessions(): void
    {
        $admin = $this->user('admin'); $m = $this->user(); $oldHash = $m->password;
        Password::createToken($m);
        DB::table('sessions')->insert(['id' => 'marketing-fixture', 'user_id' => $m->id, 'payload' => '', 'last_activity' => time()]);
        $this->as($admin)->get('/admin/marketing/'.$m->id.'/delete')->assertOk()->assertSee('diarsipkan')->assertSee('Password admin Anda');
        $this->delete('/admin/marketing/'.$m->id, [...$this->payload($m), 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->delete('/admin/marketing/'.$m->id, [...$this->payload($m), 'confirm_email' => 'wrong@example.test'])->assertSessionHasErrors('confirm_email');
        $this->assertSame('marketing', $m->fresh()->role);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertRedirect('/admin/marketing');
        $this->assertSame('marketing_deleted', $m->fresh()->role);
        $this->assertFalse(Hash::check(self::PASSWORD, $m->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'marketing-fixture']);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->get('/admin/marketing')->assertViewHas('totalMarketing', 0)->assertDontSee($m->email);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertNotFound();
        $this->actingAs($m->fresh())->withSession(['password_hash_web' => $oldHash])->get('/cuanin')->assertRedirect('/login');
        $this->post('/marketing/login', ['email' => $m->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_balance_after_confirmation_blocks_actual_delete(): void
    {
        $admin = $this->user('admin'); $m = $this->user();
        $this->as($admin)->get('/admin/marketing/'.$m->id.'/delete')->assertSee('Ketik email marketing');
        $this->credit($m, 50000);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertSessionHasErrors('marketing');
        $this->get('/admin/marketing/'.$m->id.'/delete')->assertSee('Selesaikan saldo')->assertDontSee('name="confirm_email"', false);
        $this->assertSame('marketing', $m->fresh()->role); $this->assertSame(50000, Cuanin::wallet($m)->balance);
    }

    public function test_pending_withdrawals_bonus_and_orders_each_block_deletion(): void
    {
        $admin = $this->user('admin'); $m = $this->user(); $wallet = Cuanin::wallet($m);
        $withdrawal = CuaninWithdrawal::create(['wallet_id' => $wallet->id, 'request_key' => (string) Str::uuid(), 'amount' => 10, 'bank_snapshot' => ['bank_name' => 'Test', 'account_number' => '12345', 'account_name' => 'Test']]);
        $this->as($admin)->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertSessionHasErrors('marketing');
        $this->get('/admin/marketing/'.$m->id.'/delete')->assertSee('pengajuan pencairan');
        $withdrawal->update(['status' => 'rejected']);
        DB::table('cuanin_bonus_awards')->insert(['owner_id' => $m->id, 'threshold' => 10, 'amount' => 50000, 'paid' => 0, 'created_at' => now()]);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertSessionHasErrors('marketing');
        $this->get('/admin/marketing/'.$m->id.'/delete')->assertSee('bonus marketing yang belum didanai');
        DB::table('cuanin_bonus_awards')->where('owner_id', $m->id)->update(['paid' => 50000]);
        $client = $this->user('client');
        $p = Package::create(['slug' => 'pending', 'name' => 'Paket', 'price' => 500000]);
        $order = Order::create(['user_id' => $client->id, 'package_id' => $p->id, 'order_number' => 'PENDING', 'amount' => 500000, 'payment_status' => 'pending']);
        DB::table('order_referrals')->insert(['order_id' => $order->id, 'owner_id' => $m->id, 'client_id' => $client->id, 'code' => 'PARTNER2', 'created_at' => now(), 'updated_at' => now()]);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertSessionHasErrors('marketing');
        $this->get('/admin/marketing/'.$m->id.'/delete')->assertSee('order client referral');
        $this->assertSame('marketing', $m->fresh()->role);
    }

    public function test_delete_preserves_paid_financial_history_clients_and_disables_code_for_new_registration(): void
    {
        $admin = $this->user('admin'); $m = $this->user(); $client = $this->user('client');
        $p = Package::create(['slug' => 'history', 'name' => 'Paket', 'price' => 500000]);
        $order = Order::create(['user_id' => $client->id, 'package_id' => $p->id, 'order_number' => 'HISTORY', 'amount' => 500000, 'payment_status' => 'paid', 'payment_proof' => 'private/payment_proofs/test.png']);
        DB::table('order_referrals')->insert(['order_id' => $order->id, 'owner_id' => $m->id, 'client_id' => $client->id, 'code' => 'PARTNER2', 'created_at' => now(), 'updated_at' => now()]);
        $this->as($admin)->post('/admin/orders/'.$order->id.'/approve')->assertSessionHasNoErrors();
        $this->credit($m, -50000);
        $this->delete('/admin/marketing/'.$m->id, $this->payload($m))->assertRedirect('/admin/marketing');
        $this->assertDatabaseHas('users', ['id' => $client->id, 'role' => 'client']);
        foreach (['orders', 'order_referrals', 'referral_conversions', 'cuanin_settlements'] as $table) $this->assertDatabaseCount($table, 1);
        $this->assertDatabaseCount('cuanin_entries', 3);
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee($m->name)->assertSee('50.000');
        $this->post('/admin/orders/'.$order->id.'/approve')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cuanin_entries', 3);
        $this->post('/logout');
        $this->post('/register', ['name' => 'New Client', 'nomor_hp' => '0899999999', 'package_id' => $p->id, 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'referral_code' => 'PARTNER2', 'terms_accepted' => '1', 'terms_version' => \App\Support\ServiceTerms::VERSION])->assertSessionHasErrors('referral_code');
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_list_works_before_cuanin_activation(): void
    {
        $admin = $this->user('admin');
        Schema::drop('cuanin_state');
        $this->as($admin)->get('/admin/marketing')->assertOk()->assertSee('Cuanin belum aktif')->assertViewHas('totalMarketing', 0);
    }
}
