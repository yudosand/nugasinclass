<?php
namespace Tests\Feature;

use App\Models\{User, Order, Package, CuaninWallet, CuaninWithdrawal};
use App\Support\{Cuanin, CuaninSchema};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Hash, Storage};
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CuaninTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private const PASSWORD = 'TestPassword123!';
    private function member(string $role = 'admin'): User
    {
        $n = ++$this->sequence;
        return User::create(['name' => 'Member '.$n, 'nomor_hp' => $role === 'marketing' ? null : '081234567'.str_pad($n, 3, '0', STR_PAD_LEFT), 'email' => 'member'.$n.'@example.test', 'role' => $role, 'status' => 'approved', 'password' => self::PASSWORD]);
    }
    private function as(User $user): static
    {
        return $this->actingAs($user)->withSession(['password_hash_web' => $user->password]);
    }
    private function order(int $amount = 500000, ?User $owner = null, ?User $client = null): Order
    {
        $client ??= $this->member('client');
        $p = Package::firstOrCreate(['slug' => 'cuanin-test'], ['name' => 'Paket Uji', 'price' => $amount, 'is_active' => true]);
        $order = Order::create(['user_id' => $client->id, 'package_id' => $p->id, 'amount' => $amount, 'payment_status' => 'paid', 'payment_proof' => 'private/payment_proofs/fixture.png', 'order_number' => 'TEST-'.Str::random(10)]);
        if ($owner) DB::table('order_referrals')->insert(['order_id' => $order->id, 'owner_id' => $owner->id, 'client_id' => $client->id, 'code' => 'PARTNER'.$owner->id, 'created_at' => now(), 'updated_at' => now()]);
        return $order;
    }
    private function approve(User $admin, Order $order): void
    {
        $this->as($admin)->post('/admin/orders/'.$order->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
    }
    private function funded(User $user, int $amount = 100000): CuaninWallet
    {
        return DB::transaction(function () use ($user, $amount) {
            Cuanin::lock(); $wallet = Cuanin::wallet($user);
            Cuanin::entry($wallet, $amount, 'fixture', 'fixture:'.$user->id, 'Fixture test only');
            $wallet->update(['bank_name' => 'Bank Uji', 'account_name' => $user->name, 'account_number' => '1234567890']);
            return $wallet->fresh();
        });
    }
    public function test_marketing_registration_uses_four_fields_hashes_password_and_forces_role(): void
    {
        $this->get('/marketing')->assertOk()->assertSee('10%')->assertSee('Rp1.000.000')->assertSee('nugasin-partner.png');
        $this->post('/marketing/register', ['name' => 'Rani Partner', 'email' => 'RANI@example.test', 'referral_code' => 'rani2026', 'password' => 'Eight888', 'role' => 'admin', 'balance' => 999999])->assertRedirect('/cuanin');
        $user = User::where('email', 'rani@example.test')->firstOrFail();
        $this->assertSame('marketing', $user->role); $this->assertNull($user->nomor_hp);
        $this->assertTrue(Hash::check('Eight888', $user->password));
        $this->assertDatabaseHas('referral_codes', ['user_id' => $user->id, 'code' => 'RANI2026']);
        $this->assertSame(0, Cuanin::wallet($user)->balance);
        $this->get('/cuanin')->assertOk()->assertDontSee('Eight888')->assertDontSee('/admin/admins', false)->assertSee('/marketing/clients', false);
        $this->post('/logout');
        $this->post('/marketing/login', ['email' => 'rani@example.test', 'password' => 'Eight888'])->assertRedirect('/cuanin');
    }
    public function test_invalid_or_duplicate_marketing_fields_never_create_accounts(): void
    {
        $owner = $this->member('marketing');
        DB::table('referral_codes')->insert(['user_id' => $owner->id, 'code' => 'TAKEN', 'created_at' => now(), 'updated_at' => now()]);
        $payload = ['name' => 'New', 'email' => 'new@example.test', 'referral_code' => 'TAKEN', 'password' => 'Eight888'];
        $this->post('/marketing/register', $payload)->assertSessionHasErrors('referral_code');
        $this->post('/marketing/register', [...$payload, 'referral_code' => ['bad'], 'email' => ['bad']])->assertSessionHasErrors(['email', 'referral_code']);
        $this->post('/marketing/register', [...$payload, 'referral_code' => 'VALID', 'password' => 'short'])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 1);
    }
    public function test_marketing_sees_only_referred_clients_and_cannot_access_admin_or_private_routes(): void
    {
        $m = $this->member('marketing'); $other = $this->member('marketing'); $admin = $this->member();
        $own = $this->order(owner: $m); $foreign = $this->order(owner: $other);
        $this->as($m)->get('/marketing/clients')->assertOk()->assertSee($own->user->name)->assertDontSee($foreign->user->name)->assertDontSee($own->user->nomor_hp);
        foreach (['/admin/dashboard', '/admin/clients', '/admin/admins', '/admin/chat', '/admin/cuanin/requests', '/onboarding', '/client/dashboard', '/payment/'.$own->id, '/payment/'.$own->id.'/proof', '/admin/clients/'.$own->user_id.'/elearning'] as $url) $this->get($url)->assertForbidden();
        $this->post('/admin/orders/'.$own->id.'/approve')->assertForbidden();
        $this->post('/admin/cuanin/requests/1', [])->assertForbidden();
        $this->as($own->user)->get('/cuanin')->assertForbidden();
        $this->as($admin)->get('/marketing/clients')->assertForbidden();
    }
    public function test_client_referral_registration_is_atomic_and_uses_server_price(): void
    {
        $m = $this->member('marketing');
        DB::table('referral_codes')->insert(['user_id' => $m->id, 'code' => 'RANI', 'created_at' => now(), 'updated_at' => now()]);
        $package = Package::create(['slug' => 'register-test', 'name' => 'Paket', 'price' => 500000, 'is_active' => true]);
        $payload = ['name' => 'Client Baru', 'nomor_hp' => '08987654321', 'password' => self::PASSWORD, 'password_confirmation' => self::PASSWORD, 'package_id' => $package->id, 'referral_code' => 'MISSING'];
        $payload += ['terms_accepted' => '1', 'terms_version' => \App\Support\ServiceTerms::VERSION];
        $this->post('/register', $payload)->assertSessionHasErrors('referral_code');
        $this->assertDatabaseCount('users', 1); $this->assertDatabaseCount('orders', 0);
        $this->post('/register', [...$payload, 'referral_code' => 'rani', 'amount' => 1, 'role' => 'admin'])->assertRedirect();
        $this->assertDatabaseHas('orders', ['amount' => 500000, 'payment_status' => 'pending']);
        $this->assertDatabaseHas('order_referrals', ['owner_id' => $m->id, 'code' => 'RANI']);
        $this->assertDatabaseCount('cuanin_entries', 0);
    }
    public function test_non_referral_split_conserves_every_rupiah_and_freezes_admin_snapshot(): void
    {
        $a = $this->member(); $b = $this->member(); $c = $this->member();
        $order = $this->order(500000); $this->approve($a, $order);
        $this->assertSame([166667,166667,166666], CuaninWallet::orderBy('user_id')->pluck('balance')->all());
        $d = $this->member(); $this->approve($a, $order);
        $this->assertDatabaseCount('cuanin_entries', 3); $this->assertDatabaseCount('cuanin_settlements', 1);
        $this->assertDatabaseHas('cuanin_settlements', ['admin_count' => 3, 'gross' => 500000, 'admin_pool' => 500000]);
        $this->approve($a, $this->order(400));
        $this->assertSame(100, Cuanin::wallet($d)->balance);
        $this->assertSame(500400, (int) DB::table('cuanin_entries')->sum('amount'));
        $this->as($a)->get('/admin/orders/'.$order->id)->assertOk()->assertSee('166.667');
        $this->get('/admin/orders')->assertOk()->assertSee('Pool admin dibagi 3');
    }

    public function test_admin_split_remainder_rotates_across_orders(): void
    {
        $a = $this->member(); $b = $this->member(); $c = $this->member();

        for ($i = 0; $i < 4; $i++) {
            $this->approve($a, $this->order(500000));
        }

        $this->assertSame([666667, 666667, 666666], CuaninWallet::orderBy('user_id')->pluck('balance')->all());
        $this->assertSame(2000000, (int) DB::table('cuanin_entries')->where('kind', 'admin_share')->sum('amount'));
    }
    public function test_referral_commission_precedes_admin_split_and_admin_can_own_code(): void
    {
        $a = $this->member(); $b = $this->member(); $c = $this->member(); $m = $this->member('marketing');
        $order = $this->order(owner: $m); $this->approve($a, $order);
        foreach ([$a,$b,$c] as $admin) $this->assertSame(150000, Cuanin::wallet($admin)->balance);
        $this->assertSame(50000, Cuanin::wallet($m)->balance);
        $this->assertDatabaseHas('cuanin_settlements', ['order_id' => $order->id, 'commission' => 50000, 'admin_pool' => 450000]);
        $this->approve($a, $this->order(100, $a));
        $this->assertSame(150040, Cuanin::wallet($a)->balance);
    }
    public function test_bonus_counts_unique_clients_and_funds_shortfall_from_later_receipts(): void
    {
        $a = $this->member(); $m = $this->member('marketing');
        for ($i=1;$i<=9;$i++) DB::table('referral_conversions')->insert(['owner_id' => $m->id, 'client_id' => 10000+$i, 'created_at' => now()]);
        $order = $this->order(15000, $m); $this->approve($a, $order);
        $this->assertSame(15000, Cuanin::wallet($m)->balance);
        $this->assertSame(0, Cuanin::wallet($a)->balance);
        $this->assertDatabaseHas('cuanin_bonus_awards', ['threshold' => 10, 'amount' => 50000, 'paid' => 13500]);
        $this->approve($a, $this->order(50000));
        $this->assertSame(51500, Cuanin::wallet($m)->balance);
        $this->assertSame(13500, Cuanin::wallet($a)->balance);
        $this->approve($a, $this->order(100, $m, $order->user));
        $this->assertDatabaseCount('referral_conversions', 10);
        $this->assertDatabaseCount('cuanin_bonus_awards', 1);
        $this->assertSame(65100, (int) DB::table('cuanin_entries')->sum('amount'));
    }
    public function test_twenty_client_bonus_is_incremental_to_cumulative_target(): void
    {
        $a = $this->member(); $m = $this->member('marketing');
        for ($i=1;$i<=19;$i++) DB::table('referral_conversions')->insert(['owner_id' => $m->id, 'client_id' => 10000+$i, 'created_at' => now()]);
        DB::table('cuanin_bonus_awards')->insert(['owner_id' => $m->id, 'threshold' => 10, 'amount' => 50000, 'paid' => 50000, 'created_at' => now()]);
        $this->approve($a, $this->order(500000, $m));
        $this->assertDatabaseHas('cuanin_bonus_awards', ['threshold' => 20, 'amount' => 50000, 'paid' => 50000]);
        $this->assertSame(100000, Cuanin::wallet($m)->balance);
        $this->assertSame(400000, Cuanin::wallet($a)->balance);
    }
    public function test_unpaid_orders_and_fractional_prices_cannot_credit_wallets(): void
    {
        $a = $this->member(); $order = $this->order(); $order->update(['payment_status' => 'pending']);
        $this->as($a)->post('/admin/orders/'.$order->id.'/approve')->assertSessionHasErrors('order');
        $order->update(['payment_status' => 'paid', 'amount' => '100.50']);
        $this->post('/admin/orders/'.$order->id.'/approve')->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('cuanin_entries', 0); $this->assertDatabaseCount('cuanin_settlements', 0);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
    public function test_approved_orders_cannot_be_downgraded_or_recredited_and_history_is_not_backfilled(): void
    {
        Storage::fake('local'); $a = $this->member(); $order = $this->order(); $this->approve($a, $order);
        $this->post('/admin/orders/'.$order->id.'/reject', ['rejection_reason' => 'Try again'])->assertSessionHasErrors('order');
        $this->as($order->user)->post('/payment/'.$order->id.'/upload', ['payment_proof' => UploadedFile::fake()->image('retry.png')])->assertStatus(409);
        $this->assertSame('approved', $order->fresh()->payment_status); $this->assertDatabaseCount('cuanin_settlements', 1);
        $historic = $this->order(); $historic->update(['payment_status' => 'approved']); $this->approve($a, $historic);
        $this->assertDatabaseCount('cuanin_settlements', 1);
    }
    public function test_bank_requires_password_encrypts_storage_and_never_repopulates_sensitive_input(): void
    {
        $m = $this->member('marketing');
        $bank = ['bank_name' => 'Bank Uji', 'account_name' => 'Rani', 'account_number' => '9988776655', 'current_password' => 'wrong'];
        $this->as($m)->put('/cuanin/bank', $bank)->assertSessionHasErrors('current_password');
        $this->assertNull(session('_old_input.account_number')); $this->assertNull(session('_old_input.current_password'));
        $this->put('/cuanin/bank', [...$bank, 'current_password' => self::PASSWORD])->assertRedirect()->assertSessionHasNoErrors();
        $wallet = Cuanin::wallet($m);
        $this->assertSame('9988776655', $wallet->account_number);
        $this->assertStringNotContainsString('9988776655', DB::table('cuanin_wallets')->where('id', $wallet->id)->value('account_number'));
        $this->assertArrayNotHasKey('account_number', $wallet->toArray());
        $this->get('/cuanin')->assertOk()->assertDontSee('9988776655')->assertDontSee(self::PASSWORD)->assertHeader('Cache-Control', 'no-store, private');
    }
    public function test_withdrawal_holds_balance_opens_prefilled_whatsapp_and_is_idempotent(): void
    {
        $m = $this->member('marketing'); $wallet = $this->funded($m); $key = (string) Str::uuid();
        $payload = ['request_key' => $key, 'current_password' => self::PASSWORD, 'amount' => 60000, 'wallet_id' => 888];
        $response = $this->as($m)->post('/cuanin/withdrawals', $payload); $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://wa.me/6285861015161?text=', $location);
        $this->assertStringContainsString('Rp60.000', rawurldecode($location));
        $this->assertStringContainsString('1234567890', rawurldecode($location));
        $this->assertSame(40000, $wallet->fresh()->available()); $this->assertSame(100000, $wallet->fresh()->balance); $this->assertSame(60000, $wallet->fresh()->reserved);
        $this->post('/cuanin/withdrawals', [...$payload, 'amount' => 99999])->assertRedirect($location);
        $this->post('/cuanin/withdrawals', [...$payload, 'request_key' => (string) Str::uuid()])->assertSessionHasErrors('withdrawal');
        $this->assertDatabaseCount('cuanin_withdrawals', 1); $this->assertDatabaseCount('cuanin_entries', 1);
        $w = CuaninWithdrawal::firstOrFail();
        $this->assertSame(60000, $w->amount);
        $this->assertStringNotContainsString('1234567890', $w->getRawOriginal('bank_snapshot'));
        $wallet->update(['account_number' => '1111111111']);
        $this->get('/cuanin/withdrawals/'.$w->id)->assertOk()->assertSee('1234567890')->assertDontSee('1111111111');
    }
    public function test_withdrawal_amount_cannot_exceed_available_balance(): void
    {
        $m = $this->member('marketing'); $wallet = $this->funded($m, 100000);
        $this->as($m)->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => 100001])->assertSessionHasErrors('amount');
        $this->assertSame(100000, $wallet->fresh()->available());
        $this->assertDatabaseCount('cuanin_withdrawals', 0);
    }
    public function test_formatted_rupiah_withdrawal_preserves_exact_amount_for_admin_and_marketing(): void
    {
        foreach (['admin', 'marketing'] as $role) {
            $user = $this->member($role);
            $wallet = $this->funded($user, 1350001);
            $this->as($user)->get('/cuanin')->assertOk()->assertSee('1.350.001')->assertSee('cuanin-amount.js');
            $this->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => '1.250.001'])
                ->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame(1250001, $wallet->fresh()->reserved);
            $this->assertSame(100000, $wallet->fresh()->available());
            $this->assertDatabaseHas('cuanin_withdrawals', ['wallet_id' => $wallet->id, 'amount' => 1250001]);
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidWithdrawalAmounts')]
    public function test_invalid_formatted_amount_never_reserves_balance(mixed $amount): void
    {
        $user = $this->member();
        $wallet = $this->funded($user, 100000);
        $this->as($user)->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => $amount])
            ->assertSessionHasErrors('amount');
        $this->assertSame(0, $wallet->fresh()->reserved);
        $this->assertDatabaseCount('cuanin_withdrawals', 0);
    }

    public static function invalidWithdrawalAmounts(): array
    {
        return array_map(fn ($value) => [$value], ['', '0', '-50.000', '1.50', '1,50', '1e3', '10..000', '100.001', '999.999.999.999.999.999.999', ['50000']]);
    }
    public function test_withdrawal_access_control_and_actual_transfer_confirmation_create_one_out(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $other = $this->member('marketing'); $wallet = $this->funded($m);
        $this->as($m)->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => 100000])->assertRedirect();
        $w = CuaninWithdrawal::firstOrFail();
        $this->as($other)->get('/cuanin/withdrawals/'.$w->id)->assertForbidden();
        $this->post('/cuanin/withdrawals', ['request_key' => $w->request_key, 'current_password' => self::PASSWORD, 'amount' => 100000])->assertStatus(409);
        $payload = ['action' => 'paid', 'current_password' => self::PASSWORD, 'confirmed' => 1, 'payment_reference' => 'TEST-TRANSFER'];
        $this->as($a)->post('/admin/cuanin/requests/'.$w->id, [...$payload, 'current_password' => 'wrong'])->assertSessionHasErrors('current_password');
        $this->assertSame(100000, $wallet->fresh()->reserved);
        $this->post('/admin/cuanin/requests/'.$w->id, $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(0, $wallet->fresh()->balance); $this->assertSame(0, $wallet->fresh()->reserved);
        $this->post('/admin/cuanin/requests/'.$w->id, $payload)->assertSessionHasErrors('withdrawal');
        $this->assertDatabaseHas('cuanin_entries', ['amount' => -100000, 'entry_key' => 'withdrawal:'.$w->id]);
        $this->assertDatabaseCount('cuanin_entries', 2);
        $this->get('/admin/cuanin/requests')->assertOk()->assertSee('Selesai');
    }
    public function test_rejected_withdrawal_releases_hold_without_out_and_zero_cannot_withdraw(): void
    {
        $a = $this->member(); $wallet = $this->funded($a, 123);
        $this->as($a)->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => 123])->assertRedirect();
        $w = CuaninWithdrawal::firstOrFail();
        $this->post('/admin/cuanin/requests/'.$w->id, ['action' => 'rejected', 'current_password' => self::PASSWORD, 'confirmed' => 1, 'payment_reference' => 'Rekening salah'])->assertSessionHasNoErrors();
        $this->assertSame(123, $wallet->fresh()->available()); $this->assertDatabaseCount('cuanin_entries', 1);
        $m = $this->member('marketing'); $this->funded($m, 0);
        $this->as($m)->post('/cuanin/withdrawals', ['request_key' => (string) Str::uuid(), 'current_password' => self::PASSWORD, 'amount' => 1])->assertSessionHasErrors('balance');
    }
    public function test_client_deletion_keeps_financial_history_and_package_with_orders_is_protected(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $order = $this->order(owner: $m); $this->approve($a, $order);
        $this->delete('/admin/packages/'.$order->package_id)->assertSessionHasErrors('package');
        $this->delete('/admin/clients/'.$order->user_id, ['current_password' => self::PASSWORD, 'confirm_phone' => $order->user->nomor_hp])->assertRedirect('/admin/clients');
        $this->assertDatabaseCount('orders', 0); $this->assertDatabaseCount('cuanin_settlements', 1); $this->assertDatabaseCount('referral_conversions', 1);
        $this->assertSame(500000, (int) DB::table('cuanin_entries')->sum('amount'));
        $this->assertSame(50000, Cuanin::wallet($m)->balance);
    }
    public function test_activation_is_admin_only_password_protected_and_idempotent(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $this->funded($a, 123);
        $this->as($m)->post('/cuanin/activate', ['current_password' => self::PASSWORD, 'rules_accepted' => 1])->assertForbidden();
        $this->as($a)->post('/cuanin/activate', ['current_password' => 'wrong', 'rules_accepted' => 1])->assertSessionHasErrors('current_password');
        $this->post('/cuanin/activate', ['current_password' => self::PASSWORD, 'rules_accepted' => 1])->assertRedirect('/cuanin');
        CuaninSchema::ensureCreated(); $this->assertSame(123, Cuanin::wallet($a)->balance);
        $this->assertDatabaseCount('cuanin_state', 1);
    }
    public function test_failed_distribution_rolls_back_earlier_credits_and_order_approval(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $order = $this->order(owner: $m);
        DB::table('cuanin_bonus_awards')->insert(['owner_id' => 99999, 'threshold' => 10, 'amount' => 50000, 'paid' => 0, 'created_at' => now()]);
        $this->as($a)->post('/admin/orders/'.$order->id.'/approve')->assertNotFound();
        $this->assertDatabaseCount('cuanin_entries', 0); $this->assertDatabaseCount('cuanin_settlements', 0); $this->assertDatabaseCount('referral_conversions', 0);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }
}
