<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudyUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->validate(['search' => 'nullable|string|max:100'])['search'] ?? '';
        $users = User::where('role', 'student')
            ->withCount('studyCourses')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('nomor_hp', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.study-users.index', compact('users', 'search'));
    }

    public function show($id)
    {
        $student = User::where('role', 'student')->with('studyCourses.sessions')->findOrFail($id);
        $stats = $this->stats($student);

        return view('admin.study-users.show', compact('student', 'stats'));
    }

    public function updateStatus(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected'])],
        ]);
        $student->update(['status' => $data['status']]);

        return back()->with('success', 'Status pengguna Study Tracker diperbarui.');
    }

    public function destroy(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'confirm_phone' => ['required', Rule::in([$student->nomor_hp])],
        ], [
            'current_password.current_password' => 'Password admin Anda tidak sesuai.',
            'confirm_phone.in' => 'Ketik nomor HP pengguna dengan tepat.',
        ]);

        DB::transaction(function () use ($student) {
            DB::table('sessions')->where('user_id', $student->id)->delete();
            $student->delete();
        });

        return redirect()->route('admin.study-users.index')->with('success', 'Pengguna Study Tracker berhasil dihapus.');
    }

    private function stats(User $student): array
    {
        $sessions = $student->studyCourses->flatMap->sessions;
        $today = now()->startOfDay();
        return [
            'courses' => $student->studyCourses->count(),
            'sessions' => $sessions->count(),
            'overdue' => $sessions->filter(fn ($session) => $session->assignment_enabled && $session->assignment_status !== 'Selesai' && $session->assignment_deadline && $session->assignment_deadline->lt($today))->count(),
            'done' => $sessions->filter(fn ($session) => $session->attendance_done && $session->discussion_status === 'Selesai' && (!$session->assignment_enabled || $session->assignment_status === 'Selesai'))->count(),
        ];
    }
}
