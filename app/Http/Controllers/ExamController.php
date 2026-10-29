<?php
namespace App\Http\Controllers;

use App\Models\{User,Order};
use App\Support\{ExamSchema,Exams,ServiceTerms};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};
use Illuminate\Validation\Rule;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $catalog=ExamSchema::ready()?DB::table('exam_catalog')->join('packages','packages.id','=','exam_catalog.package_id')->where('published',true)->where('price','>',0)->select('exam_catalog.*','packages.price')->orderBy('title')->get():collect();
        return view('exams.catalog',compact('catalog'));
    }
    public function registerForm() { return view('exams.register'); }
    public function register(Request $request)
    {
        abort_unless(ExamSchema::ready(),503,'Paket Simulasi UAS sedang disiapkan oleh admin.');
        $data=$request->validate(['name'=>'required|string|max:255','nomor_hp'=>['required','regex:/^[0-9]{9,15}$/D','unique:users,nomor_hp'],
            'email'=>'nullable|email|max:255|unique:users,email','password'=>'required|string|min:8|max:72|confirmed',
            'terms_accepted'=>'accepted','terms_version'=>['required',Rule::in([ServiceTerms::VERSION])]]);
        ServiceTerms::ensureSchema();
        $user=DB::transaction(function()use($data) {
            $user=User::create(['name'=>$data['name'],'nomor_hp'=>$data['nomor_hp'],'email'=>isset($data['email'])?strtolower($data['email']):null,'password'=>$data['password'],'role'=>'student','status'=>'approved']);
            DB::table('exam_members')->insert(['user_id'=>$user->id,'created_at'=>now()]);
            DB::table('client_terms_acceptances')->insert(['user_id'=>$user->id,'version'=>ServiceTerms::VERSION,'content_hash'=>ServiceTerms::contentHash(),'accepted_at'=>now()]);
            return $user;
        });
        Auth::login($user); $request->session()->regenerate();
        return redirect()->route('landing.simulasi')->with('success','Akun berhasil dibuat. Pilih paket soal yang ingin dibeli.');
    }
    public function loginForm() { return view('exams.login'); }
    public function login(Request $request)
    {
        $data=$request->validate(['nomor_hp'=>'required|string|max:30','password'=>'required|string|max:72']);
        if (!Auth::attempt($data)) return back()->withErrors(['nomor_hp'=>'Nomor HP atau password tidak sesuai.'])->onlyInput('nomor_hp');
        if ((!in_array(Auth::user()->role,['student','client']) || Auth::user()->status==='rejected') && !(Auth::user()->role==='admin' && Auth::user()->status==='approved')) {
            Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();
            return back()->withErrors(['nomor_hp'=>'Akun ini tidak dapat mengakses Simulasi UAS.']);
        }
        $request->session()->regenerate();return redirect()->route('exams.mine');
    }
    public function buy(Request $request,int $id)
    {
        abort_unless(ExamSchema::ready(),404);
        abort_unless(in_array($request->user()->role,['client','student']) && $request->user()->status!=='rejected',403);
        $request->validate(['terms_accepted'=>'accepted','terms_version'=>['required',Rule::in([ServiceTerms::VERSION])]]);
        ServiceTerms::ensureSchema();
        $order=DB::transaction(function()use($request,$id) {
            $order=Exams::buy($request->user(),$id);
            DB::table('client_terms_acceptances')->insertOrIgnore(['user_id'=>$request->user()->id,'version'=>ServiceTerms::VERSION,'content_hash'=>ServiceTerms::contentHash(),'accepted_at'=>now()]);
            return $order;
        });
        return redirect()->route('payment.show',$order->id);
    }
    public function mine(Request $request)
    {
        $purchases=ExamSchema::ready()?DB::table('exam_purchases')->join('exam_catalog','exam_catalog.id','=','exam_purchases.catalog_id')->where('user_id',$request->user()->id)->select('exam_purchases.*','exam_catalog.title','exam_catalog.code')->get():collect();
        $orders=Order::whereIn('id',$purchases->pluck('order_id'))->get()->keyBy('id');
        $attempts=ExamSchema::ready()?DB::table('exam_attempts')->where('user_id',$request->user()->id)->whereNotNull('finished_at')->orderByDesc('id')->limit(20)->get():collect();
        $demoCatalog=ExamSchema::ready() && $request->user()->role==='admin' && $request->user()->status==='approved'
            ?DB::table('exam_catalog')->orderBy('title')->get():collect();
        return view('exams.mine',compact('purchases','orders','attempts','demoCatalog'));
    }
    public function open(Request $request,int $id)
    {
        Exams::authorized($request,$id);Exams::acquire($request);
        return redirect()->route('exams.learn',['id'=>$id]);
    }
    public function close(Request $request)
    {
        if (ExamSchema::ready() && is_string($token=$request->session()->get('exam_token'))) DB::table('exam_sessions')->where('user_id',$request->user()->id)->where('token_hash',hash('sha256',$token))->delete();
        $request->session()->forget('exam_token'); return redirect()->route('exams.mine')->with('success','Akses ditutup.');
    }
    public function heartbeat(Request $request,int $id)
    {
        Exams::authorized($request,$id);Exams::lease($request);return response()->noContent();
    }
    public function reopen(Request $request,int $id)
    {
        abort_unless(ExamSchema::ready(),404);
        $attempt=DB::table('exam_attempts')->where('user_id',$request->user()->id)->find($id);abort_unless($attempt,404);
        Exams::authorized($request,$attempt->catalog_id);Exams::acquire($request);
        return redirect()->route('exams.attempt',$id);
    }
    private function page(object $catalog,int $page): string
    {
        abort_if($page<1,404);
        foreach(json_decode($catalog->documents,true,512,JSON_THROW_ON_ERROR) as $doc) {
            if($page<=$doc['pages']) { abort_unless(preg_match('/^[a-f0-9]{16}$/D',$doc['id']),404); return Exams::path(''.$doc['id'].'/'.sprintf('%04d',$page).'.jpg'); }
            $page-=$doc['pages'];
        }
        abort(404);
    }
    public function learn(Request $request,int $id,int $page=1)
    {
        if ($request->has('page')) $page=(int)$request->validate(['page'=>'required|integer|min:1|max:5000'])['page'];
        $catalog=Exams::authorized($request,$id);Exams::lease($request);$this->page($catalog,$page);
        $pages=array_sum(array_column(json_decode($catalog->documents,true),'pages'));
        return view('exams.learn',compact('catalog','page','pages'));
    }
    public function pageImage(Request $request,int $id,int $page)
    {
        $catalog=Exams::authorized($request,$id);Exams::lease($request);
        return Exams::image($this->page($catalog,$page),$request->user());
    }
    public function start(Request $request,int $id)
    {
        $catalog=Exams::authorized($request,$id);Exams::acquire($request);
        $bank=Exams::questions($catalog);
        abort_if(!$bank,409,'Soal interaktif paket ini belum tersedia. Gunakan mode belajar terlebih dahulu.');
        $attempt=DB::transaction(function()use($request,$catalog,$bank) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $existing=DB::table('exam_attempts')->where('user_id',$request->user()->id)->where('catalog_id',$catalog->id)->whereNull('finished_at')->first();
            if($existing) return $existing->id;
            $selected=collect($bank)->shuffle()->take($catalog->questions_per_attempt)->values()->all();
            return DB::table('exam_attempts')->insertGetId(['user_id'=>$request->user()->id,'catalog_id'=>$catalog->id,'questions'=>json_encode($selected),
                'answers'=>'{}','started_at'=>now(),'ends_at'=>now()->addMinutes($catalog->duration_minutes)]);
        });
        return redirect()->route('exams.attempt',$attempt);
    }
    private function attemptFor(Request $request,int $id): object
    {
        abort_unless(ExamSchema::ready(),404);
        $attempt=DB::table('exam_attempts')->where('user_id',$request->user()->id)->find($id);abort_unless($attempt,404);
        Exams::authorized($request,$attempt->catalog_id);Exams::lease($request);return $attempt;
    }
    private function finish(object $attempt): void
    {
        $answers=json_decode($attempt->answers,true);$correct=0;
        foreach(json_decode($attempt->questions,true) as $index=>$question) if(!empty($question['answer']) && ($answers[$index]??null)===$question['answer'])$correct++;
        DB::table('exam_attempts')->where('id',$attempt->id)->whereNull('finished_at')->update(['finished_at'=>now(),'correct'=>$correct]);
    }
    public function attempt(Request $request,int $id)
    {
        $attempt=$this->attemptFor($request,$id);
        if(!$attempt->finished_at && now()->gte($attempt->ends_at)) DB::transaction(function()use($id){$this->finish(DB::table('exam_attempts')->where('id',$id)->lockForUpdate()->first());});
        $attempt=DB::table('exam_attempts')->find($id);$catalog=Exams::catalog($attempt->catalog_id);
        $questions=json_decode($attempt->questions,true);$answers=json_decode($attempt->answers,true);
        $data=$request->validate(['q'=>'nullable|integer|min:0|max:499']);$index=(int)($data['q']??0);
        $index=max(0,min(count($questions)-1,$index));
        return view($attempt->finished_at?'exams.result':'exams.attempt',compact('attempt','catalog','questions','answers','index'));
    }
    public function answer(Request $request,int $id)
    {
        $this->attemptFor($request,$id);
        $data=$request->validate(['index'=>'required|integer|min:0|max:499','answer'=>'nullable|string|max:20000','finish'=>'nullable|boolean','next'=>'required|integer|min:0|max:499']);
        DB::transaction(function()use($id,$data){
            $attempt=DB::table('exam_attempts')->where('id',$id)->lockForUpdate()->first();
            if($attempt->finished_at)return;
            if(now()->gte($attempt->ends_at)){$this->finish($attempt);return;}
            $questions=json_decode($attempt->questions,true);abort_unless(isset($questions[$data['index']]),422);
            $answers=json_decode($attempt->answers,true);
            if(isset($data['answer']) && $data['answer']!=='') {
                if (($questions[$data['index']]['type']??'choice')!=='essay') abort_unless(in_array($data['answer'],$questions[$data['index']]['options'],true),422);
                $answers[$data['index']]=$data['answer'];
            }
            $attempt->answers=json_encode($answers);DB::table('exam_attempts')->where('id',$id)->update(['answers'=>$attempt->answers]);
            if(!empty($data['finish']))$this->finish($attempt);
        });
        return redirect()->route('exams.attempt',['id'=>$id,'q'=>$data['next']]);
    }
    public function questionImage(Request $request,int $id,int $index)
    {
        $attempt=$this->attemptFor($request,$id);$questions=json_decode($attempt->questions,true);abort_unless(isset($questions[$index]),404);
        $asset=$questions[$index]['image'];abort_unless(preg_match('~^questions/[a-f0-9]{16}-[0-9]+\.jpg$~D',$asset),404);
        return Exams::image(Exams::path(''.$asset),$request->user());
    }
}
