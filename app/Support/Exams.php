<?php
namespace App\Support;

use App\Models\{Order,Package,User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Exams
{
    public static function path(string $relative): string { return config('exams.path',resource_path('exams')).'/'.$relative; }
    public static function isOrder(int $id): bool { return ExamSchema::ready() && DB::table('exam_purchases')->where('order_id',$id)->exists(); }
    public static function catalog(int $id): object { abort_unless(ExamSchema::ready(),404);$row=DB::table('exam_catalog')->find($id); abort_unless($row,404); return $row; }
    public static function paid(Order $order): bool
    {
        if ($order->payment_status !== 'approved') return false;
        if (CashSchema::installed() && !CashSchema::active()) return false;
        return !CashSchema::active() || $order->outstanding_amount === 0;
    }
    public static function authorized(Request $request,int $catalog): object
    {
        abort_unless(ExamSchema::ready(),404);
        // Demo uses the real exam engine without creating a purchase or cash entry.
        if ($request->user()?->role === 'admin' && $request->user()->status === 'approved') return self::catalog($catalog);
        abort_unless($request->user() && in_array($request->user()->role,['client','student']) && $request->user()->status !== 'rejected',403);
        $purchase=DB::table('exam_purchases')->where('user_id',$request->user()->id)->where('catalog_id',$catalog)->first();
        abort_unless($purchase && self::paid(Order::findOrFail($purchase->order_id)),403,'Paket ini belum dibeli atau pembayarannya belum lunas dan disetujui.');
        return self::catalog($catalog);
    }
    public static function acquire(Request $request): void
    {
        $token=$request->session()->get('exam_token') ?: Str::random(64);
        DB::transaction(function() use($request,$token) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing=DB::table('exam_sessions')->where('user_id',$request->user()->id)->first();
            if ($existing && now()->lt($existing->expires_at) && !hash_equals($existing->token_hash,hash('sha256',$token))) {
                throw ValidationException::withMessages(['exam'=>'Akses belajar/ujian sedang aktif di perangkat lain. Tutup akses di perangkat tersebut atau tunggu 10 menit tanpa aktivitas.']);
            }
            DB::table('exam_sessions')->updateOrInsert(['user_id'=>$request->user()->id],['token_hash'=>hash('sha256',$token),'expires_at'=>now()->addMinutes(10)]);
        },3);
        $request->session()->put('exam_token',$token);
    }
    public static function lease(Request $request): void
    {
        $token=$request->session()->get('exam_token');
        $row=DB::table('exam_sessions')->where('user_id',$request->user()->id)->first();
        abort_unless(is_string($token) && $row && now()->lt($row->expires_at) && hash_equals($row->token_hash,hash('sha256',$token)),403,'Sesi akses berakhir. Buka kembali paket melalui Paket Saya.');
        DB::table('exam_sessions')->where('user_id',$request->user()->id)->where('token_hash',$row->token_hash)->update(['expires_at'=>now()->addMinutes(10)]);
    }
    public static function questions(object $catalog,bool $includeDisabled=false): array
    {
        $path=Exams::path('questions.json');
        $cacheKey='exam_bank_'.hash('sha256',$path);
        if (!request()->attributes->has($cacheKey)) request()->attributes->set($cacheKey,is_file($path) ? json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR) : []);
        $bank=request()->attributes->get($cacheKey)[$catalog->code] ?? [];
        if (ExamSchema::ready()) {
            $edits=DB::table('exam_question_edits')->where('catalog_id',$catalog->id)->pluck('payload','asset');
            foreach($bank as &$question) if(isset($edits[$question['image']])) $question=array_replace($question,json_decode($edits[$question['image']],true,512,JSON_THROW_ON_ERROR));
            unset($question);
        }
        return array_values(array_filter($bank,fn($q)=>$includeDisabled || ($q['enabled']??true)));
    }
    public static function buy(User $user,int $catalog): Order
    {
        return DB::transaction(function() use($user,$catalog) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            DB::table('exam_members')->insertOrIgnore(['user_id'=>$user->id,'created_at'=>now()]);
            $row=DB::table('exam_catalog')->where('id',$catalog)->lockForUpdate()->first(); abort_unless($row,404);
            $existing=DB::table('exam_purchases')->where('user_id',$user->id)->where('catalog_id',$catalog)->first();
            if ($existing) return Order::findOrFail($existing->order_id);
            $package=Package::findOrFail($row->package_id);
            abort_unless($row->published && (int)$package->price>0,409,'Paket belum tersedia untuk dibeli.');
            $order=Order::create(['user_id'=>$user->id,'package_id'=>$package->id,'amount'=>$package->price,'order_number'=>'UAS-'.date('Ymd').'-'.strtoupper(Str::random(10)),'payment_status'=>'pending']);
            DB::table('exam_purchases')->insert(['user_id'=>$user->id,'catalog_id'=>$catalog,'order_id'=>$order->id,'created_at'=>now()]);
            return $order;
        },3);
    }
    public static function image(string $path,User $user): \Illuminate\Http\Response
    {
        abort_unless(function_exists('imagecreatefromjpeg'),503,'Ekstensi PHP GD diperlukan untuk watermark.');
        abort_unless(is_file($path),404);
        $image=imagecreatefromjpeg($path); abort_unless($image,404);
        $w=imagesx($image); $h=imagesy($image);
        $label='NUGASIN | AKUN #'.$user->id.' | '.Str::ascii(mb_substr($user->name,0,28)).' | '.now()->format('d/m/Y H:i');
        $color=imagecolorallocatealpha($image,25,68,130,65);
        // Watermark is burned into the pixels, not removable as a DOM overlay.
        for($y=25;$y<$h;$y+=200) for($x=18;$x<$w;$x+=550) imagestring($image,3,$x,$y,$label,$color);
        ob_start(); imagejpeg($image,null,88);$data=ob_get_clean();imagedestroy($image);
        return response($data)->header('Content-Type','image/jpeg')->header('Cache-Control','no-store, private, max-age=0')->header('Content-Disposition','inline; filename="halaman.jpg"')->header('X-Robots-Tag','noindex, noimageindex')->header('Content-Security-Policy',"default-src 'none'; sandbox");
    }
}
