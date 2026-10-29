<?php
namespace Tests\Feature;

use App\Models\{User, Order, Package, CuaninWallet, CuaninWithdrawal};
use App\Support\{CashSchema, CashLedger, Cuanin, CuaninSettlement};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CashLedgerTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private const PASSWORD = 'CashTestPassword123!';
    private function member(string $role = 'admin', ?string $name = null): User
    {
        $n = ++$this->sequence;
        return User::create(['name' => $name ?? 'Member '.$n, 'role' => $role, 'status' => 'approved', 'password' => self::PASSWORD,
            'nomor_hp' => '08123456'.str_pad($n, 4, '0', STR_PAD_LEFT), 'email' => 'cash'.$n.'@example.test']);
    }
    private function order(int $total, ?string $name = null): Order
    {
        $client = $this->member('client', $name);
        $package = Package::firstOrCreate(['slug' => 'cash-test'], ['name' => 'Paket Cash', 'price' => $total, 'is_active' => true]);
        return Order::create(['order_number' => 'CASH-'.Str::random(8), 'user_id' => $client->id, 'package_id' => $package->id,
            'amount' => $total, 'payment_status' => 'paid', 'payment_proof' => 'private/payment_proofs/test.png']);
    }
    private function asAdmin(User $admin): static { return $this->actingAs($admin)->withSession(['password_hash_web' => $admin->password]); }
    private function open(array $received, array $withdrawn, User $admin): void
    {
        CashSchema::ensure();
        $this->asAdmin($admin)->post('/admin/cash/preview', ['received' => $received, 'withdrawn' => $withdrawn, 'note' => 'Catatan uji'])
            ->assertRedirect('/admin/cash/preview')->assertSessionHasNoErrors();
        $this->get('/admin/cash/preview')->assertOk()->assertSee('Pratinjau Pencocokan Kas');
        $key = session('cash_reconciliation.key');
        $this->post('/admin/cash/commit', ['draft_key' => $key, 'current_password' => self::PASSWORD, 'confirmed' => 1])->assertRedirect('/admin/orders')->assertSessionHasNoErrors();
    }
    private function receiptPayload(int|string $amount, string $reference = 'BANK-001'): array
    {
        return ['amount' => $amount, 'reference' => $reference, 'request_key' => (string) Str::uuid(),
            'received_at' => now()->timezone('Asia/Jakarta')->subMinute()->format('Y-m-d\TH:i'), 'verified' => 1, 'current_password' => self::PASSWORD, 'activate_client' => 1];
    }

    public function test_mysql_debt_column_upgrade_preserves_integer_default_without_unsigned(): void
    {
        $connection = new \Illuminate\Database\MySqlConnection(fn()=>throw new \RuntimeException('No live database expected'));
        $connection->useDefaultSchemaGrammar();
        $blueprint = new \Illuminate\Database\Schema\Blueprint($connection, 'cuanin_wallets', fn($t)=>$t->bigInteger('balance')->default(0)->change());
        $sql=implode(';',$blueprint->toSql());
        $this->assertStringContainsString('modify `balance` bigint not null default',strtolower($sql));
        $this->assertStringNotContainsString('unsigned',strtolower($sql));
    }

    private function lailaExceptionFixture(): array
    {
        $admins=[];
        for ($i=1;$i<=14;$i++) {
            $names=[1=>'kojelzs',8=>'Faisal',14=>'Indra'];
            $person=$this->member(isset($names[$i])?'admin':'client',$names[$i]??'Filler '.$i);
            if (isset($names[$i])) $admins[$i]=$person;
        }
        Cuanin::wallet($admins[1]); Cuanin::wallet($admins[8]);
        $m=$this->member('marketing'); Cuanin::wallet($m); Cuanin::wallet($admins[14]);
        $order=$this->order(850000,'Laila'); $order->update(['order_number'=>'ORD-20260917-3WEYS']);
        $this->open([$order->id=>0],[1=>0,8=>0,14=>0,$m->id=>0],$admins[1]);
        DB::table('cash_state')->where('id',1)->update(['rotation'=>2]);
        $this->post('/admin/orders/'.$order->id.'/receipts',$this->receiptPayload(500000,'LAILA-PAID'))->assertSessionHasNoErrors();
        DB::transaction(function()use($admins){
            Cuanin::lock();
            Cuanin::entry(Cuanin::wallet($admins[14]),-50001,'withdrawal','laila-test-out','Pencairan sebelum koreksi');
            CashLedger::entry(-50001,'withdrawal','laila-test-out','Indra','Pencairan sebelum koreksi',1,user:14);
        });
        $this->assertSame(116666,Cuanin::wallet($admins[14])->balance);
        return [$admins,$order];
    }

    public function test_only_authorized_laila_exception_can_create_debt_and_future_credits_repay_it(): void
    {
        [$admins,$order]=$this->lailaExceptionFixture();
        $draft=$this->splitPreview($order,[1,8]);
        $this->assertTrue($draft['plan']['negative_exception']);
        $this->assertSame(-50001,$draft['plan']['balances'][14]['after']);
        $this->get('/admin/orders/'.$order->id.'/split/preview')->assertSee('confirmed_laila_debt')->assertSee('Pengecualian sekali');
        $payload=['draft_key'=>$draft['key'],'current_password'=>self::PASSWORD,'confirmed'=>1];
        $url='/admin/orders/'.$order->id.'/split';
        $this->post($url,$payload)->assertSessionHasErrors('confirmed_laila_debt');
        $this->assertSame(116666,Cuanin::wallet($admins[14])->balance);
        $this->post($url,[...$payload,'confirmed_laila_debt'=>1])->assertSessionHasNoErrors();
        $this->assertSame(-50001,Cuanin::wallet($admins[14])->balance);
        $this->assertSame(250000,Cuanin::wallet($admins[1])->balance);
        $this->assertSame(250000,Cuanin::wallet($admins[8])->balance);
        $this->assertSame(449999,(int)DB::table('cash_state')->value('balance'));
        $this->assertSame(449999,(int)DB::table('cuanin_wallets')->sum('balance'));
        $this->withSession(['cash_split_draft'=>$draft])->post($url,[...$payload,'confirmed_laila_debt'=>1])->assertSessionHasNoErrors();
        $this->assertSame(1,DB::table('cuanin_entries')->where('entry_key','laila-split-indra-20261005')->count());
        $this->asAdmin($admins[14])->get('/cuanin')->assertOk()->assertSee('Utang penyesuaian');
        Cuanin::wallet($admins[14])->update(['bank_name'=>'Bank','account_name'=>'Indra','account_number'=>'12345678']);
        $this->post('/cuanin/withdrawals',['amount'=>1,'request_key'=>(string)Str::uuid(),'current_password'=>self::PASSWORD])->assertSessionHasErrors('balance');
        try { DB::transaction(fn()=>Cuanin::entry(Cuanin::wallet($admins[14]),-1,'withdrawal','forbidden-debit','No')); $this->fail('Ordinary debit allowed'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('balance',$e->errors()); }
        $this->asAdmin($admins[1]);
        $next=$this->order(90000); $this->post('/admin/orders/'.$next->id.'/receipts',$this->receiptPayload(90000,'NEXT-1'))->assertSessionHasNoErrors();
        $this->assertSame(-20001,Cuanin::wallet($admins[14])->balance);
        $next=$this->order(210000); $this->post('/admin/orders/'.$next->id.'/receipts',$this->receiptPayload(210000,'NEXT-2'))->assertSessionHasNoErrors();
        $this->assertSame(49999,Cuanin::wallet($admins[14])->balance);
    }

    public function test_laila_exception_rejects_other_orders_larger_debt_reserved_funds_and_reuse(): void
    {
        [$admins,$order]=$this->lailaExceptionFixture();
        $url='/admin/orders/'.$order->id.'/split/preview';
        $data=['admin_ids'=>[1,8],'split_reason'=>'Khusus order Laila'];
        $order->update(['order_number'=>'OTHER-ORDER']);
        $this->post($url,$data)->assertSessionHasErrors('split');
        $order->update(['order_number'=>'ORD-20260917-3WEYS']);
        $wallet=Cuanin::wallet($admins[14]); $wallet->update(['reserved'=>1]);
        $this->post($url,$data)->assertSessionHasErrors('split');
        $wallet->update(['reserved'=>0,'balance'=>116665]);
        $this->post($url,$data)->assertSessionHasErrors('split');
        $wallet->update(['balance'=>116666]);
        $this->post($url,[...$data,'admin_ids'=>[1]])->assertSessionHasErrors('split');
        DB::table('cuanin_entries')->insert(['wallet_id'=>$wallet->id,'amount'=>0,'kind'=>'split_correction','entry_key'=>'laila-split-indra-20261005','description'=>'Already used','created_at'=>now()]);
        $this->post($url,$data)->assertSessionHasErrors('split');
        $this->assertSame(116666,$wallet->fresh()->balance);
    }

    private function splitPreview(Order $order, array $admins): array
    {
        $this->post('/admin/orders/'.$order->id.'/split/preview', ['admin_ids'=>$admins,'split_reason'=>'Kesepakatan tim: order Laila ditangani dua admin.'])->assertSessionHasNoErrors()->assertRedirect('/admin/orders/'.$order->id.'/split/preview');
        $this->get('/admin/orders/'.$order->id.'/split/preview')->assertOk()->assertSee('Pratinjau Koreksi Pembagian');
        return session('cash_split_draft');
    }

    public function test_laila_split_is_corrected_and_remaining_payment_uses_two_admins(): void
    {
        $a=$this->member(name:'kojelzs'); $b=$this->member(name:'Faisal'); $c=$this->member(name:'Indra'); $order=$this->order(850000,'Laila');
        $this->open([$order->id=>500000],[$a->id=>0,$b->id=>0,$c->id=>0],$a);
        $original=DB::table('cash_allocations')->get()->map(fn($r)=>(array)$r)->all();
        $draft=$this->splitPreview($order,[$a->id,$b->id]);
        $this->assertSame(500000,(int) DB::table('cuanin_wallets')->sum('balance'));
        $payload=['draft_key'=>$draft['key'],'current_password'=>self::PASSWORD,'confirmed'=>1];
        $this->post('/admin/orders/'.$order->id.'/split',$payload)->assertSessionHasNoErrors();
        $this->assertSame(250000,Cuanin::wallet($a)->balance); $this->assertSame(250000,Cuanin::wallet($b)->balance); $this->assertSame(0,Cuanin::wallet($c)->balance);
        $this->assertSame(500000,(int) DB::table('cash_state')->value('balance'));
        $this->assertSame(350000,$order->fresh()->outstanding_amount);
        $audit=DB::table('cash_split_changes')->first();
        $this->assertSame($original,json_decode($audit->snapshot,true)['original']);
        $this->assertSame(0,(int) DB::table('cuanin_entries')->where('kind','split_correction')->sum('amount'));
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Riwayat Koreksi Pembagian');
        $this->withSession(['cash_split_draft'=>$draft])->post('/admin/orders/'.$order->id.'/split',$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_split_changes',1);
        $this->assertSame(3,DB::table('cuanin_entries')->where('kind','split_correction')->count());
        $this->post('/admin/orders/'.$order->id.'/receipts',$this->receiptPayload(350000))->assertSessionHasNoErrors();
        $this->assertSame(425000,Cuanin::wallet($a)->balance); $this->assertSame(425000,Cuanin::wallet($b)->balance); $this->assertSame(0,Cuanin::wallet($c)->balance);
        $this->assertSame('Lunas',$order->fresh()->payment_label);
        // A second correction uses the current allocation, retaining both audits.
        $draft=$this->splitPreview($order,[$a->id,$c->id]);
        $this->post('/admin/orders/'.$order->id.'/split',[...$payload,'draft_key'=>$draft['key']])->assertSessionHasNoErrors();
        $this->assertSame(0,Cuanin::wallet($b)->balance); $this->assertSame(425000,Cuanin::wallet($c)->balance);
        $this->assertDatabaseCount('cash_split_changes',2);
    }

    public function test_split_correction_blocks_reserved_or_withdrawn_funds_and_stale_preview(): void
    {
        $a=$this->member(); $b=$this->member(); $c=$this->member(); $order=$this->order(850000);
        $this->open([$order->id=>500000],[$a->id=>0,$b->id=>0,$c->id=>0],$a);
        $draft=$this->splitPreview($order,[$a->id,$b->id]);
        Cuanin::wallet($c)->update(['reserved'=>1]);
        $payload=['draft_key'=>$draft['key'],'confirmed'=>1,'current_password'=>self::PASSWORD];
        $this->post('/admin/orders/'.$order->id.'/split',$payload)->assertSessionHasErrors('split');
        $this->assertDatabaseCount('cash_split_changes',0);
        Cuanin::wallet($c)->update(['reserved'=>0]);
        $this->post('/admin/orders/'.$order->id.'/receipts',$this->receiptPayload(100000))->assertSessionHasNoErrors();
        $this->post('/admin/orders/'.$order->id.'/split',$payload)->assertSessionHasErrors('split');
        $this->assertDatabaseCount('cash_split_changes',0);
        DB::transaction(fn()=>Cuanin::entry(Cuanin::wallet($c),-1,'withdrawal','test-split-paid','Transfer tercatat'));
        $this->post('/admin/orders/'.$order->id.'/split/preview',['admin_ids'=>[$a->id,$b->id],'split_reason'=>'Kesepakatan baru tim'])->assertSessionHasErrors('split');
    }

    public function test_split_correction_keeps_referral_bonus_and_rejects_unauthorized_requests(): void
    {
        $a=$this->member(); $b=$this->member(); $c=$this->member(); $m=$this->member('marketing'); $order=$this->order(850001);
        DB::table('order_referrals')->insert(['order_id'=>$order->id,'client_id'=>$order->user_id,'owner_id'=>$m->id,'code'=>'SPLIT-REF','created_at'=>now()]);
        $this->open([$order->id=>500001],[$a->id=>0,$b->id=>0,$c->id=>0,$m->id=>0],$a);
        DB::table('cash_bonuses')->insert(['owner_id'=>$m->id,'threshold'=>10,'amount'=>50000,'paid'=>0,'created_at'=>now()]);
        $this->post('/admin/orders/'.$order->id.'/receipts',$this->receiptPayload(100000,'SPLIT-BONUS'))->assertSessionHasNoErrors();
        $ref=DB::table('cash_allocations')->where('kind','!=','admin_share')->get()->toJson();
        $this->post('/admin/orders/'.$order->id.'/split/preview',['admin_ids'=>[$m->id],'split_reason'=>'Tidak boleh marketing'])->assertSessionHasErrors('admin_ids');
        $draft=$this->splitPreview($order,[$a->id,$b->id]);
        $url='/admin/orders/'.$order->id.'/split';
        $this->post($url,['draft_key'=>$draft['key'],'confirmed'=>1,'current_password'=>'wrong'])->assertSessionHasErrors('current_password');
        $this->post($url,['draft_key'=>$draft['key'],'confirmed'=>0,'current_password'=>self::PASSWORD])->assertSessionHasErrors('confirmed');
        $this->post($url,['draft_key'=>$draft['key'],'confirmed'=>1,'current_password'=>self::PASSWORD])->assertSessionHasNoErrors();
        $this->assertSame($ref,DB::table('cash_allocations')->where('kind','!=','admin_share')->get()->toJson());
        $this->assertSame(50000,(int) DB::table('cash_bonuses')->value('paid'));
        $this->assertSame(600001,(int) DB::table('cash_allocations')->sum('amount'));
        $this->assertSame(600001,(int) DB::table('cuanin_wallets')->sum('balance'));
        $this->asAdmin($m)->post($url,[])->assertForbidden();
        $this->asAdmin($order->user)->post('/admin/orders/'.$order->id.'/split/preview',[])->assertForbidden();
    }

    private function correctionPayload(Order $order, int $price, ?int $package = null): array
    {
        return ['new_total' => number_format($price, 0, ',', '.'), 'package_id' => $package ?? $order->package_id,
            'expected_total' => (int) $order->amount, 'expected_package_id' => $order->package_id,
            'reason' => 'Paket disesuaikan dengan kesepakatan client.', 'request_key' => (string) Str::uuid(),
            'confirmed' => 1, 'current_password' => self::PASSWORD];
    }

    public function test_bobby_price_and_package_correction_then_full_receipt_without_phantom_debt(): void
    {
        $a = $this->member(); $b = $this->member(); $c = $this->member(); $order = $this->order(600000, 'Bobby');
        $package = Package::create(['name'=>'All Prodi', 'slug'=>'all-prodi-correction', 'price'=>500000, 'is_active'=>true]);
        $this->open([$order->id=>0], [$a->id=>0,$b->id=>0,$c->id=>0], $a);
        $payload = $this->correctionPayload($order, 500000, $package->id);
        $url = '/admin/orders/'.$order->id.'/correction';
        $this->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect('/admin/orders/'.$order->id);
        $this->post($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_order_changes', 1);
        $this->assertDatabaseCount('cash_receipts', 0);
        $this->assertDatabaseHas('orders', ['id'=>$order->id,'amount'=>500000,'package_id'=>$package->id]);
        $this->assertDatabaseHas('cash_accounts', ['order_id'=>$order->id,'total'=>500000,'received'=>0]);
        $this->assertSame(0, (int) DB::table('cash_state')->value('balance'));
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Riwayat Koreksi Order')->assertSee('All Prodi');
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(500000))->assertSessionHasNoErrors();
        $this->assertSame('Lunas', $order->fresh()->payment_label);
        $this->assertSame(0, $order->fresh()->outstanding_amount);
        $this->assertSame(500000, (int) DB::table('cash_state')->value('balance'));
        $this->assertSame(500000, (int) DB::table('cuanin_wallets')->sum('balance'));
        $this->assertDatabaseHas('cash_order_changes', ['order_id'=>$order->id,'old_total'=>600000,'new_total'=>500000,'received'=>0,'actor_id'=>$a->id]);
    }

    public function test_correction_to_already_received_amount_marks_paid_without_moving_money(): void
    {
        $a = $this->member(); $order = $this->order(600000);
        $this->open([$order->id=>500000], [$a->id=>0], $a);
        $before = DB::table('cuanin_entries')->count(); $rotation = DB::table('cash_state')->value('rotation');
        $this->post('/admin/orders/'.$order->id.'/correction', $this->correctionPayload($order,500000))->assertSessionHasNoErrors();
        $this->assertSame('Lunas', $order->fresh()->payment_label);
        $this->assertSame($before, DB::table('cuanin_entries')->count());
        $this->assertDatabaseCount('cash_receipts',1);
        $this->assertDatabaseCount('cash_entries',1);
        $this->assertSame(500000, (int) DB::table('cash_state')->value('balance'));
        $this->assertSame($rotation, DB::table('cash_state')->value('rotation'));
        $this->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Riwayat Koreksi Order')->assertDontSee('id="correction_total"',false);
    }

    public function test_order_correction_validates_authority_price_and_stale_form(): void
    {
        $a = $this->member(); $order = $this->order(600000);
        $this->open([$order->id=>500000], [$a->id=>0], $a);
        $url = '/admin/orders/'.$order->id.'/correction'; $payload = $this->correctionPayload($order,550000);
        $this->post($url,[...$payload,'current_password'=>'incorrect'])->assertSessionHasErrors('current_password');
        $this->assertArrayNotHasKey('current_password',session('_old_input',[]));
        $this->post($url,[...$payload,'confirmed'=>0])->assertSessionHasErrors('confirmed');
        $this->post($url,[...$payload,'new_total'=>499999])->assertSessionHasErrors('new_total');
        $this->post($url,[...$payload,'expected_total'=>650000])->assertSessionHasErrors('correction');
        $this->post($url,$payload)->assertSessionHasNoErrors();
        // Separate stale submissions cannot overwrite the first correction.
        $this->post($url,[...$payload,'request_key'=>(string) Str::uuid(),'new_total'=>520000])->assertSessionHasErrors('correction');
        $this->assertDatabaseCount('cash_order_changes',1);
        $this->assertDatabaseHas('orders',['id'=>$order->id,'amount'=>550000]);
        $this->asAdmin($order->user)->post($url,$payload)->assertForbidden();
    }

    public function test_price_correction_completes_referral_without_duplicate_commission(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $order = $this->order(600000);
        DB::table('order_referrals')->insert(['order_id'=>$order->id,'client_id'=>$order->user_id,'owner_id'=>$m->id,'code'=>'CORRECTION-REF','created_at'=>now()]);
        $this->open([$order->id=>500000], [$a->id=>0,$m->id=>0], $a);
        $this->assertDatabaseCount('cash_conversions',0);
        $this->post('/admin/orders/'.$order->id.'/correction',$this->correctionPayload($order,500000))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cash_conversions',['client_id'=>$order->user_id,'owner_id'=>$m->id]);
        $this->assertSame(50000, (int) Cuanin::wallet($m)->balance);
        $this->assertSame(450000, (int) Cuanin::wallet($a)->balance);
        $this->assertDatabaseCount('cash_receipts',1);
    }

    public function test_report_matches_users_real_case_without_double_counting_old_payouts(): void
    {
        $admins = [$this->member(name: 'Yudo'), $this->member(name: 'Faisal'), $this->member(name: 'Indra')];
        $received = []; $orders = [];
        foreach ([['Eka',500000,500000],['Nurdin',500000,500000],['Putri',500000,500000],['Iis',500000,500000],['Angel',500000,0],['Bobby',600000,0],['Joevel',600000,600000],['Laila',850000,500000]] as [$name,$price,$paid]) {
            $order = $this->order($price, $name); $orders[$name] = $order; $received[$order->id] = $paid;
            DB::transaction(function () use ($order) { Cuanin::lock(); CuaninSettlement::distribute($order); $order->update(['payment_status' => 'approved']); });
        }
        // A previously recorded transfer is INCLUDED in the opening cumulative payout, not added again.
        $wallet = Cuanin::wallet($admins[0]);
        $oldWithdrawal = CuaninWithdrawal::create(['wallet_id' => $wallet->id, 'amount' => 100000, 'request_key' => (string) Str::uuid(), 'status' => 'paid', 'bank_snapshot' => [], 'processed_at' => now()]);
        DB::transaction(fn () => Cuanin::entry($wallet, -100000, 'withdrawal', 'withdrawal:'.$oldWithdrawal->id, 'Transfer lama'));
        $oldCount = DB::table('cuanin_entries')->count();
        $this->open($received, [$admins[0]->id=>999999,$admins[1]->id=>999999,$admins[2]->id=>550000], $admins[0]);
        $this->assertSame(3100000, (int) DB::table('cash_receipts')->sum('amount'));
        $this->assertSame(550002, (int) DB::table('cash_state')->value('balance'));
        $this->assertSame(550002, (int) DB::table('cuanin_wallets')->sum('balance'));
        $this->assertSame(2549998, -(int) DB::table('cash_entries')->where('amount', '<', 0)->sum('amount'));
        $this->assertSame($oldCount + 3, DB::table('cuanin_entries')->count());
        $this->get('/admin/orders')->assertOk()->assertSee('Rp3.100.000')->assertSee('Rp2.549.998')->assertSee('Rp550.002')->assertSee('Rp1.450.000')->assertSee('Dibayar Sebagian');
        $this->get('/admin/orders/'.$orders['Laila']->id)->assertOk()->assertSee('Rp350.000');
        $this->post('/admin/orders/'.$orders['Laila']->id.'/receipts', $this->receiptPayload('350.000'))->assertSessionHasNoErrors();
        $this->assertSame('approved', $orders['Laila']->fresh()->payment_status);
        $this->assertSame(900002, (int) DB::table('cash_state')->value('balance'));
        $this->get('/admin/orders')->assertSee('Rp1.100.000');
    }

    public function test_partial_receipts_are_exact_idempotent_and_do_not_recredit_order_total(): void
    {
        $a = $this->member(); $b = $this->member(); $c = $this->member(); $order = $this->order(850000);
        $this->open([$order->id=>0], [$a->id=>0,$b->id=>0,$c->id=>0], $a);
        $payload = $this->receiptPayload('500.000');
        $this->post('/admin/orders/'.$order->id.'/receipts', $payload)->assertSessionHasNoErrors();
        $this->post('/admin/orders/'.$order->id.'/receipts', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_receipts', 1);
        $this->assertSame(500000, (int) DB::table('cuanin_wallets')->sum('balance'));
        $this->assertSame(350000, $order->fresh()->outstanding_amount);
        $this->post('/admin/orders/'.$order->id.'/receipts', [...$payload, 'request_key'=>(string) Str::uuid()])->assertSessionHasErrors('reference');
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(350001, 'BANK-002'))->assertSessionHasErrors('amount');
        $this->post('/admin/orders/'.$order->id.'/approve', [])->assertSessionHasErrors();
        $this->assertSame(500000, (int) DB::table('cash_state')->value('balance'));
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(350000, 'BANK-003'))->assertSessionHasNoErrors();
        $this->assertSame(850000, (int) DB::table('cuanin_wallets')->sum('balance'));
        $this->assertSame('Lunas', $order->fresh()->payment_label);
    }

    public function test_payout_request_does_not_reduce_cash_until_transfer_is_marked_paid(): void
    {
        $a = $this->member(); $order = $this->order(1000000);
        $this->open([$order->id=>1000000], [$a->id=>0], $a);
        $wallet = Cuanin::wallet($a); $wallet->update(['bank_name'=>'Bank Uji','account_name'=>$a->name,'account_number'=>'1234567890']);
        $this->post('/cuanin/withdrawals', ['amount'=>'200.000','request_key'=>(string) Str::uuid(),'current_password'=>self::PASSWORD])->assertSessionHasNoErrors();
        $this->assertSame(1000000, (int) DB::table('cash_state')->value('balance'));
        $w = CuaninWithdrawal::firstOrFail();
        $payload = ['action'=>'paid','payment_reference'=>'TRANSFER-001','confirmed'=>1,'current_password'=>self::PASSWORD];
        $this->post('/admin/cuanin/requests/'.$w->id, $payload)->assertSessionHasNoErrors();
        $this->assertSame(800000, (int) DB::table('cash_state')->value('balance'));
        $this->assertSame(800000, $wallet->fresh()->balance);
        $this->post('/admin/cuanin/requests/'.$w->id, $payload)->assertSessionHasErrors('withdrawal');
        $this->assertSame(1, DB::table('cash_entries')->where('kind', 'withdrawal')->count());
    }

    public function test_stale_preview_and_repeat_reconciliation_do_not_change_balances(): void
    {
        $a = $this->member(); $order = $this->order(1000); CashSchema::ensure();
        $this->asAdmin($a)->post('/admin/cash/preview', ['received'=>[$order->id=>1000], 'withdrawn'=>[$a->id=>0]])->assertSessionHasNoErrors();
        $key = session('cash_reconciliation.key'); $order->update(['amount'=>2000]);
        $this->post('/admin/cash/commit', ['draft_key'=>$key,'confirmed'=>1,'current_password'=>self::PASSWORD])->assertSessionHasErrors('cash');
        $this->assertDatabaseCount('cash_entries', 0);
        $this->open([$order->id=>1000], [$a->id=>0], $a);
        $this->post('/admin/cash/commit', ['draft_key'=>$key,'confirmed'=>1,'current_password'=>self::PASSWORD])->assertStatus(409);
        $this->assertSame(1000, (int) DB::table('cash_state')->value('balance'));
    }

    public function test_preview_rejects_overpayments_overwithdrawals_and_unresolved_requests(): void
    {
        $a = $this->member(); $order = $this->order(500000); CashSchema::ensure(); $this->asAdmin($a);
        foreach ([[500001,0],[500000,500001]] as [$received,$paid]) $this->post('/admin/cash/preview', ['received'=>[$order->id=>$received],'withdrawn'=>[$a->id=>$paid]])->assertSessionHasErrors('cash');
        $wallet = Cuanin::wallet($a); $wallet->update(['reserved'=>1]);
        $this->post('/admin/cash/preview', ['received'=>[$order->id=>500000],'withdrawn'=>[$a->id=>0]])->assertSessionHasErrors('cash');
        $this->assertDatabaseCount('cash_receipts', 0);
    }

    public function test_receipt_requires_admin_verification_password_and_valid_amount_and_date(): void
    {
        $a = $this->member(); $order = $this->order(500000); $this->open([$order->id=>0], [$a->id=>0], $a);
        $payload = $this->receiptPayload(100000);
        foreach ([['amount'=>-1],['amount'=>'1.50'],['amount'=>['100']],['current_password'=>'wrong'],['verified'=>0],['received_at'=>now()->addDay()->format('Y-m-d\TH:i')]] as $change) {
            $this->post('/admin/orders/'.$order->id.'/receipts', [...$payload,...$change])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('cash_receipts', 0);
        foreach (['client','marketing','student'] as $role) {
            $user = $this->member($role); $this->actingAs($user)->withSession(['password_hash_web'=>$user->password]);
            $this->get('/admin/cash/setup')->assertForbidden();
            $this->post('/admin/orders/'.$order->id.'/receipts', $payload)->assertForbidden();
        }
    }

    public function test_partial_upload_preserves_client_access_and_verified_proof_history(): void
    {
        Storage::fake('local'); $a = $this->member(); $order = $this->order(850000);
        $this->open([$order->id=>0], [$a->id=>0], $a);
        $client = $order->user;
        $this->actingAs($client)->withSession(['password_hash_web'=>$client->password]);
        $this->post('/payment/'.$order->id.'/upload', ['payment_proof'=>UploadedFile::fake()->image('first.png')])->assertSessionHasNoErrors();
        $first = $order->fresh()->payment_proof;
        $this->asAdmin($a)->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(500000))->assertSessionHasNoErrors();
        $this->actingAs($client->fresh())->withSession(['password_hash_web'=>$client->password]);
        $this->post('/payment/'.$order->id.'/upload', ['payment_proof'=>UploadedFile::fake()->image('second.png')])->assertSessionHasNoErrors();
        $this->assertSame('approved', $client->fresh()->status);
        $receipt = DB::table('cash_receipts')->first(); $this->assertSame($first, $receipt->proof);
        $this->get('/payment-receipts/'.$receipt->id.'/proof')->assertOk();
        $other = $this->member('client'); $this->actingAs($other)->withSession(['password_hash_web'=>$other->password]);
        $this->get('/payment-receipts/'.$receipt->id.'/proof')->assertForbidden();
        $this->asAdmin($a)->post('/admin/orders/'.$order->id.'/reject', ['rejection_reason'=>'Bukti tidak valid'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $client->fresh()->status);
        $this->assertSame(500000, $order->fresh()->received_amount);
    }

    public function test_referral_commission_rounds_cumulatively_and_conversion_waits_for_full_payment(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $order = $this->order(15);
        DB::table('order_referrals')->insert(['order_id'=>$order->id,'owner_id'=>$m->id,'client_id'=>$order->user_id,'code'=>'REF-CASH','created_at'=>now(),'updated_at'=>now()]);
        $this->open([$order->id=>0], [$a->id=>0,$m->id=>0], $a);
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(9))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_conversions', 0);
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(6,'BANK-002'))->assertSessionHasNoErrors();
        $this->assertSame(1, Cuanin::wallet($m)->balance);
        $this->assertSame(14, Cuanin::wallet($a)->balance);
        $this->assertDatabaseCount('cash_conversions', 1);
    }

    public function test_client_deletion_is_blocked_for_debt_and_keeps_cash_history_when_paid(): void
    {
        $a = $this->member(); $order = $this->order(1000); $this->open([$order->id=>500], [$a->id=>0], $a);
        $payload = ['current_password'=>self::PASSWORD,'confirm_phone'=>$order->user->nomor_hp];
        $this->delete('/admin/clients/'.$order->user_id, $payload)->assertSessionHasErrors('client');
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(500))->assertSessionHasNoErrors();
        $this->delete('/admin/clients/'.$order->user_id, $payload)->assertSessionHasNoErrors();
        $this->assertSame(1000, (int) DB::table('cash_entries')->sum('amount'));
        $this->assertDatabaseCount('cash_receipts', 2);
        $this->get('/admin/orders')->assertOk()->assertSee('Rp1.000');
    }
    public function test_unpaid_rejected_client_can_be_deleted_without_changing_cash_or_other_clients(): void
    {
        $admin=$this->member();$cancelled=$this->order(500000);$other=$this->order(850000);
        $this->open([$cancelled->id=>0,$other->id=>500000],[$admin->id=>0],$admin);
        $cancelled->update(['payment_status'=>'rejected']);$before=DB::table('cash_entries')->sum('amount');
        $balance=Cuanin::wallet($admin)->balance;
        $this->delete('/admin/clients/'.$cancelled->user_id,['current_password'=>self::PASSWORD,'confirm_phone'=>$cancelled->user->nomor_hp])->assertRedirect('/admin/clients')->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('users',['id'=>$cancelled->user_id]);$this->assertDatabaseMissing('orders',['id'=>$cancelled->id]);
        $this->assertDatabaseMissing('cash_accounts',['order_id'=>$cancelled->id]);$this->assertDatabaseHas('orders',['id'=>$other->id]);
        $this->assertEquals($before,DB::table('cash_entries')->sum('amount'));$this->assertEquals($balance,Cuanin::wallet($admin)->balance);
        $new=$this->order(15000);$new->update(['payment_status'=>'rejected']);
        $this->delete('/admin/clients/'.$new->user_id,['current_password'=>self::PASSWORD,'confirm_phone'=>$new->user->nomor_hp])->assertRedirect('/admin/clients')->assertSessionHasNoErrors();
    }
    public function test_rejected_order_with_partial_payment_or_receipt_history_still_blocks_deletion(): void
    {
        $admin=$this->member();$order=$this->order(850000);$this->open([$order->id=>500000],[$admin->id=>0],$admin);
        $order->update(['payment_status'=>'rejected']);$payload=['current_password'=>self::PASSWORD,'confirm_phone'=>$order->user->nomor_hp];
        $this->delete('/admin/clients/'.$order->user_id,$payload)->assertSessionHasErrors('client');
        // Even an inconsistent zero snapshot must not erase the receipt's client.
        DB::table('cash_accounts')->where('order_id',$order->id)->update(['received'=>0]);
        $this->delete('/admin/clients/'.$order->user_id,$payload)->assertSessionHasErrors('client');
        $this->assertDatabaseHas('users',['id'=>$order->user_id]);$this->assertDatabaseHas('cash_receipts',['order_id'=>$order->id,'amount'=>500000]);
    }

    public function test_bonus_is_awarded_once_on_full_payment_and_funded_only_from_received_cash(): void
    {
        $a = $this->member(); $m = $this->member('marketing'); $order = $this->order(15000);
        DB::table('order_referrals')->insert(['order_id'=>$order->id,'owner_id'=>$m->id,'client_id'=>$order->user_id,'code'=>'BONUS-CASH','created_at'=>now(),'updated_at'=>now()]);
        $this->open([$order->id=>0], [$a->id=>0,$m->id=>0], $a);
        for ($i = 1; $i <= 9; $i++) DB::table('cash_conversions')->insert(['client_id'=>1000+$i,'owner_id'=>$m->id,'created_at'=>now()]);
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(5000))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_bonuses', 0);
        $this->post('/admin/orders/'.$order->id.'/receipts', $this->receiptPayload(10000, 'BONUS-PAY-2'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cash_bonuses', ['owner_id'=>$m->id,'threshold'=>10,'amount'=>50000,'paid'=>9000]);
        $this->assertSame(10500, Cuanin::wallet($m)->balance);
        $this->assertSame(4500, Cuanin::wallet($a)->balance);
        $later = $this->order(50000);
        $this->post('/admin/orders/'.$later->id.'/receipts', $this->receiptPayload(50000,'LATER-PAY'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cash_bonuses', 1);
        $this->assertSame(65000, (int) DB::table('cuanin_wallets')->sum('balance'));
        $this->assertSame(65000, (int) DB::table('cash_state')->value('balance'));
    }

    public function test_rejected_payout_keeps_cash_and_insufficient_cash_rolls_back_paid_action(): void
    {
        $a = $this->member(); $order = $this->order(500000); $this->open([$order->id=>500000], [$a->id=>0], $a);
        $wallet = Cuanin::wallet($a); $wallet->update(['bank_name'=>'Bank','account_name'=>'Admin','account_number'=>'1234567890']);
        $this->post('/cuanin/withdrawals', ['amount'=>200000,'request_key'=>(string) Str::uuid(),'current_password'=>self::PASSWORD])->assertSessionHasNoErrors();
        $w = CuaninWithdrawal::firstOrFail();
        DB::table('cash_state')->update(['balance'=>100000]);
        $this->post('/admin/cuanin/requests/'.$w->id, ['action'=>'paid','confirmed'=>1,'payment_reference'=>'PAID-TEST','current_password'=>self::PASSWORD])->assertSessionHasErrors('cash');
        $this->assertSame('pending', $w->fresh()->status); $this->assertSame(200000, $wallet->fresh()->reserved); $this->assertSame(500000, $wallet->fresh()->balance);
        DB::table('cash_state')->update(['balance'=>500000]);
        $this->post('/admin/cuanin/requests/'.$w->id, ['action'=>'rejected','confirmed'=>1,'payment_reference'=>'Batal','current_password'=>self::PASSWORD])->assertSessionHasNoErrors();
        $this->assertSame(500000, (int) DB::table('cash_state')->value('balance')); $this->assertSame(0, $wallet->fresh()->reserved);
    }

    public function test_setup_is_idempotent_and_clean_site_can_start_without_order_history(): void
    {
        $a = $this->member();
        $this->asAdmin($a)->post('/admin/cash/prepare', ['current_password'=>'wrong'])->assertSessionHasErrors('current_password');
        $this->assertFalse(CashSchema::installed());
        $this->post('/admin/cash/prepare', ['current_password'=>self::PASSWORD])->assertRedirect('/admin/cash/setup');
        CashSchema::ensure();
        $this->open([], [$a->id=>0], $a);
        $this->assertSame(0, (int) DB::table('cash_state')->value('balance'));
        $this->get('/admin/cash/opening')->assertOk()->assertDontSee('Simpan Pencocokan');
        $this->get('/admin/orders')->assertOk();
    }
}
