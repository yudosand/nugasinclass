<?php
namespace Tests\Feature;

use App\Models\{User, ClientCourse, AcademicProgress};
use App\Support\AcademicProgressSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicBulkSessionTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private function user(string $role='client'): User
    {
        return User::create(['name'=>'Uji '.++$this->sequence,'nomor_hp'=>'085555000'.$this->sequence,'role'=>$role,'status'=>'approved','password'=>'TestPassword123!']);
    }
    private function course(User $client): ClientCourse
    {
        $course=ClientCourse::create(['user_id'=>$client->id,'course_name'=>'Matkul '.++$this->sequence]);
        for($session=1;$session<=8;$session++) AcademicProgress::create(['client_course_id'=>$course->id,'session_number'=>$session,
            'discussion_status'=>'On Progress','assignment_status'=>'Menunggu Nilai','discussion_score'=>90,'assignment_score'=>85,'notes'=>'Catatan tetap '.$session]);
        return $course;
    }
    private function payload(array $extra=[]): array { return array_replace(['bulk_session'=>2,'bulk_discussion_status'=>'Selesai','bulk_assignment_status'=>'Selesai'],$extra); }

    public function test_one_action_updates_all_courses_for_selected_client_and_session_only(): void
    {
        $admin=$this->user('admin'); $client=$this->user(); $other=$this->user();
        $courses=collect([$this->course($client),$this->course($client),$this->course($client)]); $otherCourse=$this->course($other);
        $this->actingAs($admin)->put('/admin/academic/client/'.$client->id.'/session',$this->payload(['notes'=>'overwrite','discussion_score'=>0,'user_id'=>$other->id]))
            ->assertSessionHasNoErrors()->assertRedirect('/admin/academic/client/'.$client->id);
        foreach($courses as $course) foreach($course->progresses as $row) {
            $this->assertSame($row->session_number===2?'Selesai':'On Progress',$row->discussion_status);
            $this->assertSame($row->session_number===2?'Selesai':'Menunggu Nilai',$row->assignment_status);
            $this->assertSame(90,$row->discussion_score); $this->assertSame(85,$row->assignment_score); $this->assertSame('Catatan tetap '.$row->session_number,$row->notes);
        }
        $this->assertSame('On Progress',$otherCourse->progresses->firstWhere('session_number',2)->discussion_status);
        $this->assertDatabaseCount('academic_progresses',32);
        $this->get('/admin/academic/client/'.$client->id)->assertOk()->assertSee('Update Sesi Semua Mata Kuliah')->assertSee('Terapkan ke 3 Mata Kuliah');
        $this->put('/admin/academic/client/'.$client->id.'/session',$this->payload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('academic_progresses',32);
    }

    public function test_single_status_can_be_updated_and_dashboard_completion_follows_bulk_status(): void
    {
        $client=$this->user(); $course=$this->course($client); $this->actingAs($this->user('admin')); $url='/admin/academic/client/'.$client->id.'/session';
        $this->put($url,$this->payload(['bulk_discussion_status'=>'keep']))->assertSessionHasNoErrors();
        $row=$course->progresses()->where('session_number',2)->first();
        $this->assertSame('On Progress',$row->discussion_status); $this->assertSame('Selesai',$row->assignment_status);
        $this->put($url,$this->payload())->assertSessionHasNoErrors();
        $this->put($url,$this->payload(['bulk_session'=>1]))->assertSessionHasNoErrors();
        $summary=AcademicProgressSummary::forCourses($client->courses()->with('progresses')->get());
        $this->assertSame(2,$summary['completed']);
        $this->put($url,$this->payload(['bulk_discussion_status'=>'Belum Dikerjakan','bulk_assignment_status'=>'keep']))->assertSessionHasNoErrors();
        $this->assertSame('Belum Dikerjakan',$row->fresh()->discussion_status);
        $this->assertSame('Selesai',$row->fresh()->assignment_status);
    }

    public function test_validation_empty_courses_and_nonclient_targets_do_not_write(): void
    {
        $client=$this->user(); $course=$this->course($client); $admin=$this->user('admin'); $this->actingAs($admin); $url='/admin/academic/client/'.$client->id.'/session';
        foreach([['bulk_session'=>0],['bulk_session'=>9],['bulk_session'=>2.5],['bulk_discussion_status'=>'invalid'],['bulk_discussion_status'=>'keep','bulk_assignment_status'=>'keep']] as $extra) $this->put($url,$this->payload($extra))->assertSessionHasErrors();
        $empty=$this->user(); $this->put('/admin/academic/client/'.$empty->id.'/session',$this->payload())->assertSessionHasErrors('bulk_session');
        $this->put('/admin/academic/client/'.$admin->id.'/session',$this->payload())->assertNotFound();
        $this->assertSame(0,AcademicProgress::where('discussion_status','Selesai')->count());
    }

    public function test_bulk_action_requires_admin_and_csrf(): void
    {
        $client=$this->user(); $this->course($client); $url='/admin/academic/client/'.$client->id.'/session';
        $this->put($url,$this->payload())->assertRedirect('/login');
        foreach(['client','marketing','student'] as $role) { $user=$this->user($role); $this->actingAs($user)->withSession(['password_hash_web'=>$user->password])->put($url,$this->payload())->assertForbidden(); }
        $admin=$this->user('admin'); $this->app['env']='local';
        $this->actingAs($admin)->withSession(['password_hash_web'=>$admin->password,'_token'=>'csrf'])->put($url,$this->payload())->assertStatus(419);
        $this->assertSame(0,AcademicProgress::where('discussion_status','Selesai')->count());
    }

    public function test_missing_session_is_created_and_failed_bulk_rolls_back_all_courses(): void
    {
        $client=$this->user(); $a=$this->course($client); $b=$this->course($client); $this->actingAs($this->user('admin')); $url='/admin/academic/client/'.$client->id.'/session';
        $b->progresses()->where('session_number',2)->delete();
        $this->put($url,$this->payload())->assertSessionHasNoErrors();
        $this->assertSame('Selesai',$b->progresses()->where('session_number',2)->first()->discussion_status);
        DB::unprepared('CREATE TRIGGER fail_bulk BEFORE UPDATE ON academic_progresses WHEN NEW.client_course_id = '.$b->id." BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->put($url,$this->payload(['bulk_session'=>3]))->assertStatus(500);
        $this->assertSame('On Progress',$a->progresses()->where('session_number',3)->first()->discussion_status);
        $this->assertSame('Menunggu Nilai',$b->progresses()->where('session_number',3)->first()->assignment_status);
        DB::unprepared('DROP TRIGGER fail_bulk');
    }
}
