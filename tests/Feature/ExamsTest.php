<?php
namespace Tests\Feature;
use App\Models\{User,Package,Order};
use App\Support\{Exams,ExamSchema,CuaninSchema,CashSchema,ServiceTerms};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,File,Storage};
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
class ExamsTest extends TestCase
{
    use RefreshDatabase;
    private string $fixture;
    private int $seq=0;
    protected function setUp():void
    {
        parent::setUp();config(['app.key'=>'base64:'.base64_encode(str_repeat('t',32))]);
        $this->fixture=storage_path('framework/testing/exams-'.bin2hex(random_bytes(5)));
        File::ensureDirectoryExists($this->fixture.'/exams/0123456789abcdef');File::ensureDirectoryExists($this->fixture.'/exams/questions');
        $image=imagecreatetruecolor(900,500);imagefill($image,0,0,imagecolorallocate($image,255,255,255));imagejpeg($image,$this->fixture.'/exams/0123456789abcdef/0001.jpg');imagejpeg($image,$this->fixture.'/exams/questions/0123456789abcdef-1.jpg');imagedestroy($image);
        file_put_contents($this->fixture.'/exams/questions.json',json_encode(['TEST1001'=>[['image'=>'questions/0123456789abcdef-1.jpg','type'=>'choice','options'=>['A','B','C','D'],'choices'=>['A'=>'Alpha','B'=>'Beta','C'=>'Gamma','D'=>'Delta'],'answer'=>'C','source'=>'Test.pdf','page'=>1]]]));
        file_put_contents($this->fixture.'/exams/manifest.json',json_encode([['code'=>'TEST1001','id'=>'0123456789abcdef','pages'=>1,'title'=>'Paket Uji','source'=>'Test.pdf']]));
        config(['exams.path'=>$this->fixture.'/exams']);
    }
    protected function tearDown():void {File::deleteDirectory($this->fixture);parent::tearDown();}
    private function user(string $role='student'):User {return User::create(['name'=>'Exam User '.++$this->seq,'nomor_hp'=>'081234567'.str_pad($this->seq,3,'0',STR_PAD_LEFT),'role'=>$role,'status'=>'approved','password'=>'TestPass123!']);}
    private function loginAs(User $u):static {return $this->actingAs($u)->withSession(['password_hash_web'=>$u->password]);}
    private function catalog(bool $published=true):object
    {
        $p=Package::create(['name'=>'Paket Uji','slug'=>'uas-'.bin2hex(random_bytes(3)),'price'=>25000,'is_active'=>false]);
        $id=DB::table('exam_catalog')->insertGetId(['package_id'=>$p->id,'code'=>'TEST1001','title'=>'Paket Uji','published'=>$published,'documents'=>json_encode([['id'=>'0123456789abcdef','pages'=>1]]),'duration_minutes'=>10,'questions_per_attempt'=>1,'created_at'=>now(),'updated_at'=>now()]);return Exams::catalog($id);
    }
    private function paid(User $u,object $c):Order {$o=Exams::buy($u,$c->id);$o->update(['payment_status'=>'approved']);return $o;}
    private function bulkPayload():array
    {
        $items=DB::table('exam_catalog')->join('packages','packages.id','=','exam_catalog.package_id')->select('exam_catalog.*','packages.price')->get();
        $data=[];foreach($items as $item)$data[$item->id]=['price'=>35000,'published'=>0,'duration_minutes'=>45,'questions_per_attempt'=>30,'revision'=>\App\Http\Controllers\Admin\ExamManagementController::revision($item)];
        return ['packages'=>$data];
    }
    public function test_bulk_save_is_atomic_preserves_old_orders_and_detects_stale_or_incomplete_forms():void
    {
        $first=$this->catalog();$o=$this->paid($this->user(),$first);
        DB::table('exam_catalog')->where('id',$first->id)->update(['code'=>'TEST1002']);$second=$this->catalog();
        $admin=$this->user('admin');$this->loginAs($admin);
        $payload=$this->bulkPayload();$invalid=$payload;$invalid['packages'][$second->id]['price']=0;
        $this->put('/admin/simulasi/paket',$invalid)->assertSessionHasErrors('packages.'.$second->id.'.price');
        $this->assertEquals(25000,Package::find($first->package_id)->price);
        $incomplete=$payload;unset($incomplete['packages'][$second->id]);$this->put('/admin/simulasi/paket',$incomplete)->assertStatus(409);
        $invalid=$payload;$invalid['packages'][$first->id]['published']=1;
        $this->put('/admin/simulasi/paket',$invalid)->assertSessionHasErrors('packages.'.$first->id.'.published');
        $this->assertEquals(25000,Package::find($second->package_id)->price);
        $this->put('/admin/simulasi/paket',$payload)->assertRedirect()->assertSessionHasNoErrors();
        foreach([$first,$second] as $c){$this->assertEquals(35000,Package::find($c->package_id)->price);$this->assertDatabaseHas('exam_catalog',['id'=>$c->id,'duration_minutes'=>45,'published'=>0]);}
        $this->assertEquals(25000,$o->fresh()->amount);
        $this->put('/admin/simulasi/paket',$payload)->assertStatus(409);
        $this->get('/admin/simulasi/paket')->assertOk()->assertSee('Simpan Semua Paket')->assertSee('Demo Ujian');
        $this->loginAs($this->user())->put('/admin/simulasi/paket',$this->bulkPayload())->assertForbidden();
    }
    public function test_admin_can_demo_draft_learning_and_scored_exam_without_orders_or_membership():void
    {
        $catalog=$this->catalog(false);$admin=$this->user('admin');$this->loginAs($admin);
        $this->get('/simulasi/paket-saya')->assertOk()->assertSee('Demo Simulasi UAS Admin');
        $this->post('/simulasi/paket/'.$catalog->id.'/buka')->assertRedirect();
        $this->get('/simulasi/paket/'.$catalog->id.'/belajar')->assertOk()->assertSee('Mode Demo Admin');
        $this->get('/simulasi/paket/'.$catalog->id.'/halaman/1')->assertOk()->assertHeader('Content-Type','image/jpeg');
        $this->post('/simulasi/paket/'.$catalog->id.'/mulai')->assertRedirect();$id=DB::table('exam_attempts')->value('id');
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'C','finish'=>1])->assertRedirect();
        $this->assertDatabaseHas('exam_attempts',['id'=>$id,'user_id'=>$admin->id,'correct'=>1]);
        $this->get('/simulasi/ujian/'.$id)->assertOk()->assertSee('Hasil Ujian');
        $this->assertDatabaseCount('orders',0);$this->assertDatabaseCount('exam_purchases',0);$this->assertDatabaseCount('exam_members',0);
        $this->post('/simulasi/paket/'.$catalog->id.'/beli')->assertForbidden();
        $this->loginAs($this->user('admin'))->get('/simulasi/ujian/'.$id)->assertNotFound();
        foreach(['client','student','marketing'] as $role) $this->loginAs($this->user($role))->post('/simulasi/paket/'.$catalog->id.'/mulai',['demo'=>1])->assertForbidden();
        $admin->update(['status'=>'rejected']);$this->loginAs($admin)->post('/simulasi/paket/'.$catalog->id.'/buka')->assertForbidden();
    }
    public function test_admin_can_login_through_simulation_login_for_demo():void
    {
        $this->catalog(false);$admin=$this->user('admin');
        $this->post('/simulasi/masuk',['nomor_hp'=>$admin->nomor_hp,'password'=>'TestPass123!'])->assertRedirect('/simulasi/paket-saya');
        $this->get('/simulasi/paket-saya')->assertOk()->assertSee('Demo Simulasi UAS Admin');
    }
    public function test_registration_requires_consent_and_does_not_grant_paid_access():void
    {
        $payload=['name'=>'Peserta','nomor_hp'=>'081234567890','password'=>'TestPass123!','password_confirmation'=>'TestPass123!'];
        $this->post('/simulasi/daftar',$payload)->assertSessionHasErrors('terms_accepted');
        $this->post('/simulasi/daftar',$payload+['terms_accepted'=>1,'terms_version'=>ServiceTerms::VERSION])->assertRedirect('/simulasi')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('users',1);$this->assertDatabaseCount('client_terms_acceptances',1);$this->assertDatabaseCount('exam_purchases',0);
    }
    public function test_server_price_duplicate_purchase_and_published_gate():void
    {
        $u=$this->user();$c=$this->catalog();$this->loginAs($u);$payload=['terms_accepted'=>1,'terms_version'=>ServiceTerms::VERSION,'amount'=>1];
        $this->post('/simulasi/paket/'.$c->id.'/beli',$payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/simulasi/paket/'.$c->id.'/beli',$payload)->assertRedirect();$this->assertDatabaseCount('orders',1);$o=Order::first();$this->assertEquals(25000,$o->amount);
        Package::whereKey($c->package_id)->update(['price'=>50000]);$this->assertEquals(25000,$o->fresh()->amount);
        DB::table('exam_catalog')->where('id',$c->id)->update(['published'=>false]);$other=$this->user();$this->loginAs($other)->post('/simulasi/paket/'.$c->id.'/beli',$payload)->assertStatus(409);
    }
    public function test_guests_other_buyers_and_pending_payment_cannot_access_media():void
    {
        $c=$this->catalog();$u=$this->user();$o=Exams::buy($u,$c->id);
        $this->get('/simulasi/paket/'.$c->id.'/halaman/1')->assertRedirect('/login');
        $this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/buka')->assertForbidden();
        $o->update(['payment_status'=>'approved']);$this->post('/simulasi/paket/'.$c->id.'/buka')->assertRedirect();
        $this->get('/simulasi/paket/'.$c->id.'/halaman/1')->assertOk()->assertHeader('Content-Type','image/jpeg')->assertHeader('X-Frame-Options','DENY');
        $this->get('/simulasi/paket/'.$c->id.'/halaman/2')->assertNotFound();
        $this->loginAs($this->user())->get('/simulasi/paket/'.$c->id.'/halaman/1')->assertForbidden();
    }
    public function test_one_active_device_and_close_releases_lease():void
    {
        $u=$this->user();$c=$this->catalog();$this->paid($u,$c);$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/buka')->assertRedirect();$token=session('exam_token');
        $this->withSession(['exam_token'=>'different-device-token'])->post('/simulasi/paket/'.$c->id.'/buka')->assertSessionHasErrors('exam');
        $this->get('/simulasi/paket/'.$c->id.'/halaman/1')->assertForbidden();
        $this->withSession(['exam_token'=>$token])->post('/simulasi/tutup')->assertRedirect('/simulasi/paket-saya');$this->assertDatabaseCount('exam_sessions',0);
        $this->post('/simulasi/paket/'.$c->id.'/buka')->assertRedirect()->assertSessionHasNoErrors();
    }
    public function test_attempt_is_private_server_scored_and_answers_only_after_finish():void
    {
        $c=$this->catalog();$u=$this->user();$this->paid($u,$c);$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/mulai')->assertRedirect();$id=DB::table('exam_attempts')->value('id');
        $this->post('/simulasi/paket/'.$c->id.'/mulai')->assertRedirect();$this->assertDatabaseCount('exam_attempts',1);
        $this->get('/simulasi/ujian/'.$id)->assertOk()->assertSee('Gamma')->assertDontSee('Kunci sumber')->assertDontSee('"answer":"C"',false);
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'X'])->assertStatus(422);
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'C','finish'=>1,'correct'=>999])->assertRedirect();
        $this->assertDatabaseHas('exam_attempts',['id'=>$id,'correct'=>1]);
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'A','finish'=>1])->assertRedirect();$this->assertDatabaseHas('exam_attempts',['id'=>$id,'correct'=>1]);
        $this->get('/simulasi/ujian/'.$id)->assertOk()->assertSee('Kunci sumber');
        $this->loginAs($this->user())->get('/simulasi/ujian/'.$id.'/soal/0')->assertNotFound();
    }
    public function test_late_answers_are_not_accepted():void
    {
        $c=$this->catalog();$u=$this->user();$this->paid($u,$c);$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/mulai');$id=DB::table('exam_attempts')->value('id');
        DB::table('exam_attempts')->where('id',$id)->update(['ends_at'=>now()->subSecond()]);
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'C','finish'=>1])->assertRedirect();$this->assertDatabaseHas('exam_attempts',['id'=>$id,'correct'=>0,'answers'=>'{}']);
    }
    public function test_essay_is_saved_and_unknown_key_is_not_scored_as_correct():void
    {
        $path=$this->fixture.'/exams/questions.json';$bank=json_decode(file_get_contents($path),true);$bank['TEST1001'][0]['type']='essay';$bank['TEST1001'][0]['answer']=null;$bank['TEST1001'][0]['options']=[];file_put_contents($path,json_encode($bank));
        $c=$this->catalog();$u=$this->user();$this->paid($u,$c);$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/mulai');$id=DB::table('exam_attempts')->value('id');
        $this->get('/simulasi/ujian/'.$id)->assertOk()->assertSee('Jawaban uraian');
        $this->post('/simulasi/ujian/'.$id,['index'=>0,'next'=>0,'answer'=>'<script>unsafe</script>','finish'=>1])->assertRedirect();
        $this->get('/simulasi/ujian/'.$id)->assertOk()->assertSee('Tinjauan mandiri')->assertSee('&lt;script&gt;unsafe&lt;/script&gt;',false)->assertDontSee('<script>unsafe</script>',false);
        $this->assertDatabaseHas('exam_attempts',['id'=>$id,'correct'=>0]);
    }
    public function test_admin_import_is_repeatable_prices_private_and_question_edits_versioned():void
    {
        $admin=$this->user('admin');$this->loginAs($admin)->post('/admin/simulasi/import')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/simulasi/import')->assertRedirect();$this->assertDatabaseCount('exam_catalog',1);$c=DB::table('exam_catalog')->first();
        $this->put('/admin/simulasi/paket/'.$c->id,['price'=>45000,'published'=>1,'duration_minutes'=>30,'questions_per_attempt'=>20])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/admin/simulasi/paket')->assertOk();$this->get('/admin/simulasi/clients')->assertOk();$this->get('/admin/simulasi/paket/'.$c->id.'/soal')->assertOk();
        $question=Exams::questions($c)[0];$payload=['revision'=>hash('sha256',json_encode($question)),'prompt'=>'Revisi soal','answer'=>'B','enabled'=>1,'choices'=>$question['choices']];
        $this->put('/admin/simulasi/paket/'.$c->id.'/soal/0',$payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->put('/admin/simulasi/paket/'.$c->id.'/soal/0',$payload)->assertStatus(409);
        $this->assertSame('B',Exams::questions($c)[0]['answer']);
        $this->loginAs($this->user())->put('/admin/simulasi/paket/'.$c->id,['price'=>1])->assertForbidden();
        $this->get('/admin/simulasi/paket/'.$c->id.'/soal')->assertForbidden();
    }
    public function test_proof_approval_rejection_preserves_account_and_existing_packages():void
    {
        CuaninSchema::ensureCreated();$admin=$this->user('admin');$u=$this->user();$c=$this->catalog();$o=Exams::buy($u,$c->id);Storage::fake('local');
        $this->loginAs($u)->post('/payment/'.$o->id.'/upload',['payment_proof'=>UploadedFile::fake()->image('proof.jpg')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals('approved',$u->fresh()->status);$this->get('/payment/'.$o->id)->assertOk()->assertSee('WhatsApp');
        $this->loginAs($admin)->post('/admin/orders/'.$o->id.'/reject',['rejection_reason'=>'Bukti perlu diperiksa'])->assertRedirect();$this->assertEquals('approved',$u->fresh()->status);
        $this->loginAs($u)->post('/payment/'.$o->id.'/upload',['payment_proof'=>UploadedFile::fake()->image('proof.jpg')])->assertRedirect();
        $this->loginAs($admin)->post('/admin/orders/'.$o->id.'/approve')->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/orders/'.$o->id.'/approve')->assertRedirect();$this->assertDatabaseCount('cuanin_settlements',1);$this->assertTrue(Exams::paid($o->fresh()));
    }
    public function test_partial_receipts_do_not_unlock_a_paid_package():void
    {
        $c=$this->catalog();$u=$this->user();$o=$this->paid($u,$c);CashSchema::ensure();DB::table('cash_state')->updateOrInsert(['id'=>1],['active'=>true]);
        DB::table('cash_accounts')->insert(['order_id'=>$o->id,'order_number'=>$o->order_number,'client_id'=>$u->id,'client_name'=>$u->name,'total'=>25000,'received'=>10000,'admin_ids'=>'[]']);
        $this->assertFalse(Exams::paid($o->fresh()));$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/buka')->assertForbidden();
        DB::table('cash_accounts')->where('order_id',$o->id)->update(['received'=>25000]);$this->assertTrue(Exams::paid($o->fresh()));
    }
    public function test_heartbeat_and_result_reopen_keep_ownership_and_device_checks():void
    {
        $c=$this->catalog();$u=$this->user();$this->paid($u,$c);$this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/mulai');$id=DB::table('exam_attempts')->value('id');$token=session('exam_token');
        $this->travel(8)->minutes();$this->post('/simulasi/paket/'.$c->id.'/heartbeat')->assertNoContent();
        $this->travel(8)->minutes();$this->get('/simulasi/ujian/'.$id)->assertOk();
        $this->withSession(['exam_token'=>'another-device'])->post('/simulasi/paket/'.$c->id.'/heartbeat')->assertForbidden();
        $this->post('/simulasi/ujian/'.$id.'/buka')->assertSessionHasErrors('exam');
        $this->withSession(['exam_token'=>$token])->post('/simulasi/tutup');
        $this->post('/simulasi/ujian/'.$id.'/buka')->assertRedirect('/simulasi/ujian/'.$id);
        $this->loginAs($this->user())->post('/simulasi/ujian/'.$id.'/buka')->assertNotFound();
    }
    public function test_real_cash_receipt_unlocks_exam_once_and_posts_to_cuanin():void
    {
        CuaninSchema::ensureCreated();CashSchema::ensure();DB::table('cash_state')->where('id',1)->update(['active'=>true]);
        $admin=$this->user('admin');$u=$this->user();$c=$this->catalog();$o=Exams::buy($u,$c->id);
        $payload=['amount'=>25000,'reference'=>'UAS-TEST-RECEIPT','received_at'=>now()->subMinute()->timezone('Asia/Jakarta')->format('Y-m-d\TH:i'),'request_key'=>(string)\Illuminate\Support\Str::uuid(),'verified'=>1,'current_password'=>'TestPass123!','activate_client'=>1];
        $this->loginAs($admin)->post('/admin/orders/'.$o->id.'/receipts',$payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->post('/admin/orders/'.$o->id.'/receipts',$payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_receipts',1);$this->assertTrue(Exams::paid($o->fresh()));
        $this->assertDatabaseHas('cash_state',['id'=>1,'balance'=>25000]);$this->assertDatabaseHas('cuanin_wallets',['user_id'=>$admin->id,'balance'=>25000]);
        $this->loginAs($u)->post('/simulasi/paket/'.$c->id.'/buka')->assertRedirect()->assertSessionHasNoErrors();
    }
}
