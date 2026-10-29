<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Package,Order};
use App\Support\{ExamSchema,Exams};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamManagementController extends Controller
{
    public function questions(Request $request,int $id)
    {
        $catalog=Exams::catalog($id);$bank=Exams::questions($catalog,true);
        $index=(int)($request->validate(['q'=>'nullable|integer|min:0|max:10000'])['q']??0);
        abort_unless(isset($bank[$index]),404);$question=$bank[$index];$total=count($bank);
        return view('admin.exams.questions',compact('catalog','question','index','total'));
    }
    public function saveQuestion(Request $request,int $id,int $index)
    {
        $catalog=Exams::catalog($id);$bank=Exams::questions($catalog,true);abort_unless(isset($bank[$index]),404);$question=$bank[$index];
        $data=$request->validate(['prompt'=>'nullable|string|max:10000','answer'=>'nullable|in:A,B,C,D','enabled'=>'required|boolean',
            'choices'=>'nullable|array:A,B,C,D','choices.*'=>'required|string|max:4000','revision'=>'required|string|size:64']);
        if(($question['type']??'choice')!=='essay') $request->validate(['choices.A'=>'required','choices.B'=>'required','choices.C'=>'required','choices.D'=>'required']);
        DB::transaction(function()use($id,$index,$data,$request){
            $catalog=DB::table('exam_catalog')->where('id',$id)->lockForUpdate()->first();$question=Exams::questions($catalog,true)[$index];
            abort_unless(hash_equals(hash('sha256',json_encode($question)),$data['revision']),409,'Soal telah berubah. Muat ulang sebelum menyimpan.');
            $payload=['prompt'=>$data['prompt']??null,'answer'=>($question['type']??'choice')==='essay'?null:($data['answer']??null),'enabled'=>(bool)$data['enabled']];
            if(($question['type']??'choice')!=='essay')$payload['choices']=$data['choices'];
            DB::table('exam_question_edits')->updateOrInsert(['catalog_id'=>$id,'asset'=>$question['image']],['payload'=>json_encode($payload),'actor_id'=>$request->user()->id,'updated_at'=>now(),'created_at'=>now()]);
        });return back()->with('success','Soal disimpan. Perubahan berlaku untuk ujian baru; ujian yang sudah dimulai tetap memakai salinan sebelumnya.');
    }
    public function questionImage(int $id,int $index)
    {
        $bank=Exams::questions(Exams::catalog($id),true);abort_unless(isset($bank[$index]),404);$asset=$bank[$index]['image'];
        abort_unless(preg_match('~^questions/[a-f0-9]{16}-[0-9]+\\.jpg$~D',$asset),404);
        return Exams::image(Exams::path(''.$asset),auth()->user());
    }
    public function index()
    {
        ExamSchema::ensure();
        $catalog=DB::table('exam_catalog')->join('packages','packages.id','=','exam_catalog.package_id')->select('exam_catalog.*','packages.price')->orderBy('title')->get();
        $counts=[];$scored=[];foreach($catalog as $item){$questions=Exams::questions($item);$counts[$item->id]=count($questions);$scored[$item->id]=count(array_filter($questions,fn($q)=>!empty($q['answer'])));}
        return view('admin.exams.catalog',compact('catalog','counts','scored'));
    }
    public function import()
    {
        ExamSchema::ensure();
        $path=Exams::path('manifest.json');abort_unless(is_file($path),409,'Berkas bank soal belum tersedia.');
        $groups=collect(json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR))->groupBy('code');
        DB::transaction(function()use($groups){
            foreach($groups as $code=>$docs){
                if(DB::table('exam_catalog')->where('code',$code)->exists())continue;
                $package=Package::firstOrCreate(['slug'=>'uas-'.strtolower($code)],['name'=>'Simulasi UAS - '.$docs[0]['title'],'price'=>15000,'description'=>'Akses soal Simulasi UAS dan mode belajar.','is_active'=>false]);
                DB::table('exam_catalog')->insertOrIgnore(['code'=>$code,'title'=>$docs[0]['title'],'package_id'=>$package->id,'documents'=>json_encode($docs->values()),'published'=>false,'duration_minutes'=>60,'questions_per_attempt'=>40,'created_at'=>now(),'updated_at'=>now()]);
            }
        },3);
        return back()->with('success','Bank soal diimpor sebagai draft. Periksa harga dan isi paket, lalu aktifkan paket yang siap dijual.');
    }
    public function update(Request $request,int $id)
    {
        $data=$request->validate(['price'=>'required|integer|min:1000|max:100000000','published'=>'required|boolean','duration_minutes'=>'required|integer|min:5|max:240','questions_per_attempt'=>'required|integer|min:1|max:100']);
        if ($data['published']) {
            $catalog=Exams::catalog($id);
            $bank=Exams::questions($catalog);
            if (!function_exists('imagecreatefromjpeg') || !$bank) throw \Illuminate\Validation\ValidationException::withMessages(['published'=>'Aktifkan ekstensi PHP GD dan pastikan tersedia soal interaktif sebelum menjual paket.']);
            foreach ($bank as $question) if (!is_readable(Exams::path($question['image']))) throw \Illuminate\Validation\ValidationException::withMessages(['published'=>'Ada gambar soal yang belum terunggah. Unggah seluruh folder resources/exams terlebih dahulu.']);
            foreach (json_decode($catalog->documents,true) as $doc) for ($page=1;$page<=$doc['pages'];$page++) if (!is_readable(Exams::path($doc['id'].'/'.sprintf('%04d',$page).'.jpg'))) throw \Illuminate\Validation\ValidationException::withMessages(['published'=>'Halaman materi belum lengkap. Unggah seluruh folder resources/exams terlebih dahulu.']);
        }
        DB::transaction(function()use($id,$data){
            $row=DB::table('exam_catalog')->where('id',$id)->lockForUpdate()->first();abort_unless($row,404);
            Package::whereKey($row->package_id)->update(['price'=>$data['price'],'is_active'=>false]);
            DB::table('exam_catalog')->where('id',$id)->update(['published'=>$data['published'],'duration_minutes'=>$data['duration_minutes'],'questions_per_attempt'=>$data['questions_per_attempt'],'updated_at'=>now()]);
        });return back()->with('success','Paket diperbarui. Nominal order yang sudah dibuat tetap.');
    }
    public static function revision(object $row): string
    {
        return hash('sha256',json_encode([(int)$row->price,(bool)$row->published,(int)$row->duration_minutes,(int)$row->questions_per_attempt]));
    }
    public function updateAll(Request $request)
    {
        $data=$request->validate([
            'packages'=>'required|array|min:1|max:200',
            'packages.*'=>'required|array:price,published,duration_minutes,questions_per_attempt,revision',
            'packages.*.price'=>'required|integer|min:1000|max:100000000',
            'packages.*.published'=>'required|boolean',
            'packages.*.duration_minutes'=>'required|integer|min:5|max:240',
            'packages.*.questions_per_attempt'=>'required|integer|min:1|max:100',
            'packages.*.revision'=>'required|string|size:64',
        ])['packages'];
        DB::transaction(function()use($data){
            $rows=DB::table('exam_catalog')->orderBy('id')->lockForUpdate()->get();
            $ids=array_map('strval',array_keys($data));sort($ids);
            $expected=$rows->pluck('id')->map(fn($id)=>(string)$id)->all();sort($expected);
            abort_unless($ids===$expected,409,'Daftar paket berubah atau data tidak lengkap. Muat ulang halaman sebelum menyimpan.');
            $packages=Package::whereIn('id',$rows->pluck('package_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            // Validate every package before writing any: one failure leaves all prices unchanged.
            foreach($rows as $row){
                $row->price=$packages[$row->package_id]->price;$values=$data[$row->id];
                abort_unless(hash_equals(self::revision($row),$values['revision']),409,'Paket telah diubah oleh admin lain. Muat ulang halaman sebelum menyimpan.');
                if($values['published']){
                    $error=null;$bank=Exams::questions($row);
                    if(!function_exists('imagecreatefromjpeg') || !$bank) $error='Aktifkan PHP GD dan pastikan tersedia soal interaktif.';
                    foreach($bank as $question) if(!is_readable(Exams::path($question['image']))) $error='Gambar soal belum lengkap. Unggah seluruh resources/exams.';
                    foreach(json_decode($row->documents,true) as $doc) for($page=1;$page<=$doc['pages'];$page++) if(!is_readable(Exams::path($doc['id'].'/'.sprintf('%04d',$page).'.jpg'))) $error='Halaman materi belum lengkap. Unggah seluruh resources/exams.';
                    if($error) throw \Illuminate\Validation\ValidationException::withMessages(['packages.'.$row->id.'.published'=>$row->title.': '.$error]);
                }
            }
            foreach($rows as $row){
                $values=$data[$row->id];
                Package::whereKey($row->package_id)->update(['price'=>$values['price'],'is_active'=>false]);
                DB::table('exam_catalog')->where('id',$row->id)->update(['published'=>$values['published'],'duration_minutes'=>$values['duration_minutes'],'questions_per_attempt'=>$values['questions_per_attempt'],'updated_at'=>now()]);
            }
        },3);
        return back()->with('success','Semua paket berhasil disimpan. Nominal order yang sudah dibuat tetap.');
    }
    public function clients(Request $request)
    {
        ExamSchema::ensure();
        $query=DB::table('exam_members')->join('users','users.id','=','exam_members.user_id')->leftJoin('exam_purchases','exam_purchases.user_id','=','users.id')->leftJoin('exam_catalog','exam_catalog.id','=','exam_purchases.catalog_id')
            ->select('exam_purchases.*','users.name','users.nomor_hp','exam_catalog.title');
        $search=$request->validate(['search'=>'nullable|string|max:100'])['search']??'';
        if($search!=='')$query->where(fn($q)=>$q->where('users.name','like','%'.$search.'%')->orWhere('users.nomor_hp','like','%'.$search.'%')->orWhere('exam_catalog.title','like','%'.$search.'%'));
        $count=DB::table('exam_members')->count();
        $purchases=$query->orderByDesc('exam_purchases.id')->paginate(20)->withQueryString();$orders=Order::whereIn('id',$purchases->pluck('order_id'))->get()->keyBy('id');
        return view('admin.exams.clients',compact('purchases','orders','count','search'));
    }
    public function preview(int $id,int $page=1)
    {
        $catalog=Exams::catalog($id);$docs=json_decode($catalog->documents,true);abort_if($page<1,404);
        foreach($docs as $doc){if($page<=$doc['pages']){abort_unless(preg_match('/^[a-f0-9]{16}$/D',$doc['id']),404);return Exams::image(Exams::path(''.$doc['id'].'/'.sprintf('%04d',$page).'.jpg'),auth()->user());}$page-=$doc['pages'];}abort(404);
    }
}
