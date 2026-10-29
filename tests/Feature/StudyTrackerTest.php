<?php

namespace Tests\Feature;

use App\Models\StudyCourse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudyTrackerTest extends TestCase
{
    use RefreshDatabase;

    private int $sequence = 0;
    private const PASSWORD = 'StudyPass123!';

    private function user(string $role = 'student'): User
    {
        $n = ++$this->sequence;
        return User::create([
            'name' => ucfirst($role).' '.$n,
            'nomor_hp' => '08177000'.str_pad((string) $n, 4, '0', STR_PAD_LEFT),
            'email' => $role.$n.'@example.test',
            'password' => self::PASSWORD,
            'role' => $role,
            'status' => 'approved',
        ]);
    }

    public function test_public_landing_replaces_joki_task_entry_and_registers_student(): void
    {
        $this->get('/')->assertOk()->assertSee('Study Tracker')->assertDontSee('Joki Tugas');
        $this->get('/study-tracker')->assertOk()->assertSee('Study Tracker E-Learning')->assertSee('Mulai Tracking Gratis')->assertSee('Mulai dari data kuliahmu sendiri')->assertDontSee('Prioritas Minggu Ini');

        $this->post('/study-tracker/register', [
            'name' => 'Raka Tracker',
            'nomor_hp' => '081234567890',
            'email' => 'raka@example.test',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
        ])->assertRedirect('/study-tracker/dashboard');

        $student = User::where('nomor_hp', '081234567890')->firstOrFail();
        $this->assertSame('student', $student->role);
        $this->assertSame('approved', $student->status);
        $this->assertTrue(Hash::check(self::PASSWORD, $student->password));
    }

    public function test_student_course_creates_eight_sessions_with_assignments_on_three_five_seven(): void
    {
        $student = $this->user();
        $this->actingAs($student)->post('/study-tracker/courses', [
            'course_name' => 'Manajemen Keuangan',
            'course_code' => 'EKMA4213',
            'semester' => 4,
            'active_session' => 4,
            'target_grade' => 'A',
        ])->assertRedirect('/study-tracker/dashboard');

        $course = StudyCourse::with('sessions')->firstOrFail();
        $this->assertSame($student->id, $course->user_id);
        $this->assertSame([3, 5, 7], $course->sessions->where('assignment_enabled', true)->pluck('session_number')->values()->all());
        $this->assertSame('Belum Ada', $course->sessions->firstWhere('session_number', 1)->assignment_status);
        $this->assertSame('Belum Dikerjakan', $course->sessions->firstWhere('session_number', 3)->assignment_status);

        $this->get('/study-tracker/dashboard')->assertOk()->assertSee('Manajemen Keuangan')->assertSee('Sesi 4');
    }

    public function test_student_updates_session_and_enables_assignment_on_other_session(): void
    {
        $student = $this->user();
        $course = $student->studyCourses()->create(['course_name' => 'Basis Data', 'active_session' => 2]);
        for ($i = 1; $i <= 8; $i++) {
            $course->sessions()->create(['session_number' => $i, 'assignment_enabled' => in_array($i, [3, 5, 7], true), 'assignment_status' => in_array($i, [3, 5, 7], true) ? 'Belum Dikerjakan' : 'Belum Ada']);
        }
        $session = $course->sessions()->where('session_number', 2)->firstOrFail();

        $this->actingAs($student)->put('/study-tracker/courses/'.$course->id.'/sessions/'.$session->id, [
            'attendance_done' => '1',
            'discussion_status' => 'Selesai',
            'discussion_score' => '88',
            'assignment_enabled' => '1',
            'assignment_status' => 'On Progress',
            'assignment_deadline' => now()->addDay()->format('Y-m-d'),
            'assignment_score' => '90',
            'notes' => 'Diskusi aman.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $session->refresh();
        $this->assertTrue($session->attendance_done);
        $this->assertTrue($session->assignment_enabled);
        $this->assertSame('On Progress', $session->assignment_status);
        $this->assertSame(88, $session->discussion_score);

        $this->get('/study-tracker/dashboard')->assertOk()->assertSee('Deadline dekat')->assertSee('1');
    }

    public function test_students_cannot_access_each_others_courses_and_admin_sees_users(): void
    {
        $student = $this->user();
        $other = $this->user();
        $admin = $this->user('admin');
        $course = $student->studyCourses()->create(['course_name' => 'Rahasia', 'active_session' => 1]);
        $course->sessions()->create(['session_number' => 1]);

        $this->actingAs($other)->get('/study-tracker/courses/'.$course->id)->assertNotFound();
        $this->post('/logout');
        $this->actingAs($admin)->get('/study-tracker/dashboard')->assertForbidden();

        $this->actingAs($admin)->get('/admin/study-users')
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee($other->name);

        $this->get('/admin/study-users/'.$student->id)
            ->assertOk()
            ->assertSee('Rahasia')
            ->assertSee('Hapus Pengguna Study Tracker');
    }

    public function test_main_login_routes_student_to_tracker_dashboard(): void
    {
        $student = $this->user();
        $this->post('/login', ['nomor_hp' => $student->nomor_hp, 'password' => self::PASSWORD])
            ->assertRedirect('/study-tracker/dashboard');
    }
}
