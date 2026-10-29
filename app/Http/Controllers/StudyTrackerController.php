<?php

namespace App\Http\Controllers;

use App\Models\StudyCourse;
use App\Models\StudySession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudyTrackerController extends Controller
{
    private const STATUSES = ['Belum Dikerjakan', 'On Progress', 'Menunggu Nilai', 'Selesai'];

    public function landing()
    {
        return view('study.landing');
    }

    public function registerForm()
    {
        return view('study.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nomor_hp' => ['required', 'regex:/^[0-9]{9,15}$/D', 'unique:users,nomor_hp'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'nomor_hp.regex' => 'Nomor HP hanya boleh angka 9-15 digit.',
        ]);

        $user = DB::transaction(fn () => User::create([
            'name' => $data['name'],
            'nomor_hp' => $data['nomor_hp'],
            'email' => $data['email'] ? strtolower($data['email']) : null,
            'password' => $data['password'],
            'role' => 'student',
            'status' => 'approved',
        ]));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('study.dashboard')->with('success', 'Akun Study Tracker berhasil dibuat. Tambahkan mata kuliah pertama kamu.');
    }

    public function loginForm()
    {
        return view('study.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:72'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $identity = trim($data['identity']);
        $field = str_contains($identity, '@') ? 'email' : 'nomor_hp';
        $credentials = [$field => $field === 'email' ? strtolower($identity) : $identity, 'password' => $data['password'], 'role' => 'student', 'status' => 'approved'];

        if (Auth::attempt($credentials, (bool) ($data['remember'] ?? false))) {
            $request->session()->regenerate();
            return redirect()->route('study.dashboard');
        }

        return back()->withErrors(['identity' => 'Akun Study Tracker atau password tidak sesuai.'])->onlyInput('identity');
    }

    public function dashboard(Request $request)
    {
        $user = $this->student($request);
        $courses = $user->studyCourses()->with('sessions')->latest()->get();
        $summary = $this->summary($courses);

        return view('study.dashboard', compact('courses', 'summary'));
    }

    public function storeCourse(Request $request)
    {
        $user = $this->student($request);
        $data = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'active_session' => ['required', 'integer', 'min:1', 'max:8'],
            'target_grade' => ['nullable', 'string', 'max:5'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $course = $user->studyCourses()->create($data);
            for ($session = 1; $session <= 8; $session++) {
                $hasAssignment = in_array($session, [3, 5, 7], true);
                $course->sessions()->create([
                    'session_number' => $session,
                    'assignment_enabled' => $hasAssignment,
                    'assignment_status' => $hasAssignment ? 'Belum Dikerjakan' : 'Belum Ada',
                ]);
            }
        });

        return redirect()->route('study.dashboard')->with('success', 'Mata kuliah berhasil ditambahkan.');
    }

    public function showCourse(Request $request, StudyCourse $course)
    {
        $this->authorizeCourse($request, $course);
        $course->load('sessions');
        $grade = $this->gradeProjection($course);

        return view('study.course', compact('course', 'grade'));
    }

    public function updateCourse(Request $request, StudyCourse $course)
    {
        $this->authorizeCourse($request, $course);
        $data = $request->validate([
            'course_name' => ['required', 'string', 'max:255'],
            'course_code' => ['nullable', 'string', 'max:50'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'active_session' => ['required', 'integer', 'min:1', 'max:8'],
            'target_grade' => ['nullable', 'string', 'max:5'],
        ]);
        $course->update($data);

        return back()->with('success', 'Data mata kuliah diperbarui.');
    }

    public function updateSession(Request $request, StudyCourse $course, StudySession $session)
    {
        $this->authorizeCourse($request, $course);
        abort_unless((int) $session->study_course_id === (int) $course->id, 404);
        $data = $request->validate([
            'attendance_done' => ['nullable', 'boolean'],
            'discussion_status' => ['required', Rule::in(self::STATUSES)],
            'assignment_enabled' => ['nullable', 'boolean'],
            'assignment_status' => ['required', Rule::in(['Belum Ada', ...self::STATUSES])],
            'assignment_deadline' => ['nullable', 'date'],
            'discussion_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'assignment_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $assignmentEnabled = (bool) ($data['assignment_enabled'] ?? false);
        $session->update([
            'attendance_done' => (bool) ($data['attendance_done'] ?? false),
            'discussion_status' => $data['discussion_status'],
            'assignment_enabled' => $assignmentEnabled,
            'assignment_status' => $assignmentEnabled ? $data['assignment_status'] : 'Belum Ada',
            'assignment_deadline' => $assignmentEnabled ? ($data['assignment_deadline'] ?? null) : null,
            'discussion_score' => $data['discussion_score'] ?? null,
            'assignment_score' => $assignmentEnabled ? ($data['assignment_score'] ?? null) : null,
            'notes' => $data['notes'] ?? null,
        ]);

        return back()->with('success', 'Sesi '.$session->session_number.' berhasil diperbarui.');
    }

    public function destroyCourse(Request $request, StudyCourse $course)
    {
        $this->authorizeCourse($request, $course);
        $course->delete();

        return redirect()->route('study.dashboard')->with('success', 'Mata kuliah dihapus dari Study Tracker.');
    }

    private function student(Request $request): User
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'student' && $user->status === 'approved', 403);
        return $user;
    }

    private function authorizeCourse(Request $request, StudyCourse $course): void
    {
        $user = $this->student($request);
        abort_unless((int) $course->user_id === (int) $user->id, 404);
    }

    private function summary($courses): array
    {
        $sessions = $courses->flatMap->sessions;
        $today = now()->startOfDay();
        $upcoming = $sessions->filter(fn ($session) => $session->assignment_enabled && $session->assignment_status !== 'Selesai' && $session->assignment_deadline && $session->assignment_deadline->between($today, $today->copy()->addDays(3)))->count();
        $overdue = $sessions->filter(fn ($session) => $session->assignment_enabled && $session->assignment_status !== 'Selesai' && $session->assignment_deadline && $session->assignment_deadline->lt($today))->count();
        $pendingDiscussion = $sessions->where('discussion_status', '!=', 'Selesai')->count();
        $completed = $sessions->filter(fn ($session) => $session->attendance_done && $session->discussion_status === 'Selesai' && (!$session->assignment_enabled || $session->assignment_status === 'Selesai'))->count();
        $total = max($sessions->count(), 1);

        return [
            'courses' => $courses->count(),
            'pending_discussion' => $pendingDiscussion,
            'upcoming_assignments' => $upcoming,
            'overdue_assignments' => $overdue,
            'progress' => (int) round(($completed / $total) * 100),
            'active_sessions' => $courses->pluck('active_session')->unique()->sort()->values()->all(),
        ];
    }

    private function gradeProjection(StudyCourse $course): array
    {
        $sessions = $course->sessions;
        $discussion = $sessions->whereNotNull('discussion_score')->avg('discussion_score');
        $assignment = $sessions->where('assignment_enabled', true)->whereNotNull('assignment_score')->avg('assignment_score');
        $known = collect([$discussion, $assignment])->filter(fn ($value) => $value !== null);
        $prediction = $known->isEmpty() ? null : (int) round($known->avg());
        $target = strtoupper((string) $course->target_grade);

        return compact('discussion', 'assignment', 'prediction', 'target');
    }
}
