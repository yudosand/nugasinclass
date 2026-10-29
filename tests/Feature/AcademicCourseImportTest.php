<?php
namespace Tests\Feature;

use App\Models\{User, ClientCourse, AcademicProgress};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicCourseImportTest extends TestCase
{
    use RefreshDatabase;
    private int $sequence = 0;
    private function user(string $role = 'client'): User
    {
        return User::create(['name'=>'Import '.++$this->sequence, 'nomor_hp'=>'085999000'.$this->sequence,
            'role'=>$role, 'status'=>'approved', 'password'=>'TestPassword123!']);
    }
    private function url(User $client): string { return '/admin/academic/client/'.$client->id.'/courses/bulk'; }

    public function test_import_seeds_eight_sessions_and_skips_duplicates_without_changing_existing_progress(): void
    {
        $client=$this->user(); $other=$this->user(); $this->actingAs($this->user('admin'));
        $existing=ClientCourse::create(['user_id'=>$client->id,'course_name'=>'Basis Data','course_code'=>'STSI4105']);
        AcademicProgress::create(['client_course_id'=>$existing->id,'session_number'=>1,'discussion_status'=>'Selesai','assignment_status'=>'Selesai','discussion_score'=>90,'notes'=>'Tetap']);
        ClientCourse::create(['user_id'=>$other->id,'course_name'=>'Proses Bisnis']);
        $list=" basis  DATA\r\nSTSI4105 | Nama Lain\r\nSTSI4206 | Proses Bisnis\r\n\nAnalisis dan Visualisasi Data\r\nproses bisnis";
        $this->post($this->url($client),['course_list'=>$list,'user_id'=>$other->id])->assertSessionHasNoErrors()->assertRedirect('/admin/academic/client/'.$client->id);
        $this->assertSame(3,$client->courses()->count()); $this->assertSame(1,$other->courses()->count());
        $this->assertDatabaseCount('academic_progresses',17);
        foreach($client->courses()->where('id','!=',$existing->id)->get() as $course) {
            $this->assertSame(range(1,8),$course->progresses->pluck('session_number')->all());
            foreach($course->progresses as $row) { $this->assertSame('Belum Dikerjakan',$row->discussion_status); $this->assertSame('Belum Dikerjakan',$row->assignment_status); $this->assertNull($row->discussion_score); }
        }
        $this->post($this->url($client),['course_list'=>$list])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('academic_progresses',17);
        $this->assertSame(90,$existing->progresses->first()->discussion_score); $this->assertSame('Tetap',$existing->progresses->first()->notes);
    }

    public function test_invalid_lists_are_rejected_before_any_write_and_input_is_preserved(): void
    {
        $client=$this->user(); $this->actingAs($this->user('admin'));
        foreach(['',"\n \n",'Valid' . "\n" . str_repeat('a',256),"Valid\nCODE |",'A | B | C',str_repeat("Matkul\n",51),str_repeat('a',20001)] as $list) {
            $this->from('/admin/academic/client/'.$client->id)->post($this->url($client),['course_list'=>$list])->assertSessionHasErrors('course_list');
            $this->assertDatabaseCount('client_courses',0);
        }
        $this->post($this->url($client),['course_list'=>"Valid\nCODE |"])->assertSessionHasInput('course_list',"Valid\nCODE |");
        $this->get('/admin/academic/client/'.$client->id)->assertOk()->assertSee('bulkCoursesModal');
    }

    public function test_only_admin_can_import_into_client_and_csrf_is_required(): void
    {
        $client=$this->user(); $payload=['course_list'=>'Basis Data'];
        $this->post($this->url($client),$payload)->assertRedirect('/login');
        foreach(['client','student','marketing'] as $role) {
            $actor=$this->user($role);
            $this->actingAs($actor)->withSession(['password_hash_web'=>$actor->password])->post($this->url($client),$payload)->assertForbidden();
        }
        $admin=$this->user('admin');
        $this->actingAs($admin)->withSession(['password_hash_web'=>$admin->password])->post($this->url($admin),$payload)->assertNotFound();
        $this->app['env']='local';
        $this->withSession(['_token'=>'csrf'])->post($this->url($client),$payload)->assertStatus(419);
        $this->assertDatabaseCount('client_courses',0);
    }

    public function test_failed_session_creation_rolls_back_entire_import(): void
    {
        $client=$this->user(); $this->actingAs($this->user('admin'));
        DB::unprepared("CREATE TRIGGER fail_import BEFORE INSERT ON academic_progresses WHEN NEW.session_number = 5 BEGIN SELECT RAISE(ABORT, 'test failure'); END");
        $this->post($this->url($client),['course_list'=>"Basis Data\nProses Bisnis"])->assertStatus(500);
        $this->assertDatabaseCount('client_courses',0); $this->assertDatabaseCount('academic_progresses',0);
        DB::unprepared('DROP TRIGGER fail_import');
    }
}
