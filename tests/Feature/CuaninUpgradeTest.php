<?php
namespace Tests\Feature;

use App\Models\{User, Order, Package};
use App\Support\CuaninSchema;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Tests\TestCase;

class CuaninUpgradeTest extends TestCase
{
    public function test_web_activation_upgrades_legacy_schema_without_losing_existing_users_or_orders(): void
    {
        $this->artisan('migrate:fresh', ['--force' => true])->assertExitCode(0);
        try {
            foreach (['cuanin_state','cuanin_withdrawals','cuanin_entries','cuanin_settlements','cuanin_bonus_awards','referral_conversions','order_referrals','referral_codes','cuanin_wallets'] as $table) Schema::drop($table);
            Schema::table('users', function ($table) { $table->enum('role', ['admin','client'])->default('client')->change(); $table->string('nomor_hp')->nullable(false)->change(); });
            $admin = User::create(['name' => 'Legacy Admin', 'nomor_hp' => '08123456789', 'role' => 'admin', 'status' => 'approved', 'password' => 'TestPassword123!']);
            $client = User::create(['name' => 'Legacy Client', 'nomor_hp' => '08123456788', 'role' => 'client', 'status' => 'pending_approval', 'password' => 'TestPassword123!']);
            $package = Package::create(['name' => 'Legacy', 'slug' => 'legacy', 'price' => 500000]);
            $order = Order::create(['user_id' => $client->id, 'package_id' => $package->id, 'order_number' => 'LEGACY', 'amount' => 500000, 'payment_status' => 'paid', 'payment_proof' => 'payment_proofs/old.png']);
            $this->assertFalse(CuaninSchema::ready());
            $this->actingAs($admin)->get('/cuanin')->assertOk()->assertSee('Aktifkan Cuanin');
            $this->post('/admin/orders/'.$order->id.'/approve')->assertStatus(409);
            $this->post('/cuanin/activate', ['current_password' => 'TestPassword123!', 'rules_accepted' => 1])->assertRedirect('/cuanin');
            $this->assertTrue(CuaninSchema::ready());
            $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid', 'payment_proof' => 'payment_proofs/old.png']);
            $this->assertSame($admin->password, $admin->fresh()->password);
            $this->assertSame($client->password, $client->fresh()->password);
            foreach ([1,2] as $n) User::create(['name' => 'Marketing '.$n, 'email' => 'marketing'.$n.'@example.test', 'nomor_hp' => null, 'role' => 'marketing', 'status' => 'approved', 'password' => 'Eight888']);
            $this->assertDatabaseCount('cuanin_settlements', 0);
            CuaninSchema::ensureCreated(); $this->assertDatabaseCount('users', 4);
            $this->post('/admin/orders/'.$order->id.'/approve')->assertSessionHasNoErrors();
            $this->assertDatabaseHas('cuanin_settlements', ['gross' => 500000, 'admin_count' => 1]);
            $this->assertDatabaseHas('cuanin_wallets', ['user_id' => $admin->id, 'balance' => 500000]);
        } finally {
            Schema::dropAllTables(); RefreshDatabaseState::$migrated = false;
        }
    }
}
