<?php
namespace Tests\Feature;

use App\Models\{User, Package};
use App\Support\ServiceTerms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ServiceTermsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        $package=Package::firstOrCreate(['slug'=>'terms-test'], ['name'=>'Paket Uji','price'=>500000,'is_active'=>true]);
        return ['name'=>'Client Uji Syarat','nomor_hp'=>'08911112222','password'=>'TestPassword123!',
            'password_confirmation'=>'TestPassword123!','package_id'=>$package->id,
            'terms_accepted'=>'1','terms_version'=>ServiceTerms::VERSION];
    }

    public function test_public_terms_contain_all_sections_and_registration_checkbox_is_not_prechecked(): void
    {
        $this->get('/syarat-ketentuan')->assertOk()->assertSee('DEFINISI DAN SIFAT LAYANAN')->assertSee('TANGGUNG JAWAB NILAI')
            ->assertSee('KETENTUAN KHUSUS TUGAS PRAKTIKUM')->assertSee('ALUR PEMBAYARAN')->assertSee('KETENTUAN PEMBATALAN')
            ->assertSee('KERAHASIAAN DAN PRIVASI DATA')->assertSee('maksimal 2x (dua kali)')->assertSee('musyawarah untuk mufakat');
        $html=$this->get('/register')->assertOk()->assertSee('name="terms_accepted"', false)->assertSee('/syarat-ketentuan',false)->getContent();
        preg_match('/<input[^>]+id="terms_accepted"[^>]*>/', $html, $match);
        $this->assertStringContainsString('required',$match[0]);
        $this->assertStringNotContainsString('checked',$match[0]);
    }

    public function test_absent_declined_or_old_version_consent_cannot_create_account_or_order(): void
    {
        $payload=$this->payload();
        foreach([null,'0','false',[]] as $value) {
            $invalid=$payload; if($value===null) unset($invalid['terms_accepted']); else $invalid['terms_accepted']=$value;
            $this->post('/register',$invalid)->assertSessionHasErrors('terms_accepted');
        }
        foreach([null,'old-version',[]] as $version) {
            $invalid=$payload; if($version===null) unset($invalid['terms_version']); else $invalid['terms_version']=$version;
            $this->post('/register',$invalid)->assertSessionHasErrors('terms_version');
        }
        $this->assertDatabaseCount('users',0); $this->assertDatabaseCount('orders',0); $this->assertDatabaseCount('client_terms_acceptances',0);
    }

    public function test_registration_records_server_time_version_and_hash_atomically(): void
    {
        $this->travelTo(now()->setDate(2026,10,15)->setTime(12,30));
        $payload=$this->payload();
        $this->post('/register',$payload+['accepted_at'=>'1999-01-01','content_hash'=>'forged','user_id'=>999])->assertSessionHasNoErrors()->assertRedirect();
        $user=User::where('nomor_hp',$payload['nomor_hp'])->firstOrFail();
        $this->assertDatabaseHas('client_terms_acceptances',['user_id'=>$user->id,'version'=>ServiceTerms::VERSION,
            'content_hash'=>ServiceTerms::contentHash(),'accepted_at'=>now()->toDateTimeString()]);
        $this->assertDatabaseCount('client_terms_acceptances',1); $this->assertDatabaseCount('orders',1);
        $admin=User::create(['name'=>'Admin','nomor_hp'=>'08911113333','password'=>'TestPassword123!','role'=>'admin','status'=>'approved']);
        $this->actingAs($admin)->withSession(['password_hash_web'=>$admin->password])->get('/admin/clients/'.$user->id)
            ->assertOk()->assertSee('Disetujui saat pendaftaran')->assertSee(ServiceTerms::VERSION);
    }

    public function test_existing_installation_creates_table_without_artisan_and_does_not_backfill_old_users(): void
    {
        Schema::drop('client_terms_acceptances');
        $old=User::create(['name'=>'Lama','nomor_hp'=>'08911114444','password'=>'TestPassword123!','role'=>'client','status'=>'approved']);
        $this->assertNull(ServiceTerms::acceptanceFor($old->id));
        $this->post('/register',$this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('client_terms_acceptances',1);
        $this->assertNull(ServiceTerms::acceptanceFor($old->id));
        ServiceTerms::ensureSchema();
        $this->assertDatabaseCount('client_terms_acceptances',1);
    }

    public function test_failed_order_does_not_leave_account_or_consent_and_csrf_is_required(): void
    {
        $payload=$this->payload();
        DB::unprepared("CREATE TRIGGER fail_terms_order BEFORE INSERT ON orders BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->post('/register',$payload)->assertStatus(500);
        $this->assertDatabaseCount('users',0); $this->assertDatabaseCount('client_terms_acceptances',0);
        DB::unprepared('DROP TRIGGER fail_terms_order');
        $this->app['env']='local';
        $this->withSession(['_token'=>'csrf'])->post('/register',$payload)->assertStatus(419);
        $this->assertDatabaseCount('users',0);
    }
}
