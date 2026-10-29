<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgress;
use App\Models\ClientCourse;
use App\Models\User;
use App\Support\ClientHandlerSchema;
use App\Support\CourseImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class AcademicProgressController extends Controller
{
    public function index(Request $request)
    {
        ClientHandlerSchema::ensure();
        $query = User::where('role', 'client')->with(['profile', 'courses.progresses', 'handlerAdmin:id,name']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhereHas('profile', function ($pq) use ($search) {
                      $pq->where('universitas', 'like', "%{$search}%")
                        ->orWhere('nim', 'like', "%{$search}%")
                        ->orWhere('program_studi', 'like', "%{$search}%");
                  });
            });
        }

        $clients = $query->latest()->paginate(15);

        return view('admin.academic.index', compact('clients'));
    }

    public function showClientAcademic($user_id)
    {
        ClientHandlerSchema::ensure();
        $client = User::where('role', 'client')
            ->with(['profile', 'courses.progresses', 'orders.package', 'handlerAdmin:id,name'])
            ->findOrFail($user_id);

        return view('admin.academic.show', compact('client'));
    }

    public function claimClient(Request $request, $user_id)
    {
        ClientHandlerSchema::ensure();
        $admin = $request->user();

        $result = DB::transaction(function () use ($admin, $user_id) {
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($user_id);

            if ((int) $client->handler_admin_id === (int) $admin->id) {
                return ['status' => 'same', 'name' => $client->name];
            }

            if ($client->handler_admin_id) {
                $handler = User::where('role', 'admin')->find($client->handler_admin_id);
                return ['status' => 'taken', 'name' => $client->name, 'handler' => $handler?->name ?? 'admin lain'];
            }

            $client->forceFill(['handler_admin_id' => $admin->id])->save();
            return ['status' => 'claimed', 'name' => $client->name];
        }, 3);

        return match ($result['status']) {
            'claimed' => back()->with('success', "{$result['name']} sekarang ditandai sebagai client yang Anda handle."),
            'same' => back()->with('success', "{$result['name']} sudah tercatat Anda handle."),
            default => back()->with('error', "{$result['name']} sudah di-handle oleh {$result['handler']}."),
        };
    }

    public function releaseClient(Request $request, $user_id)
    {
        ClientHandlerSchema::ensure();
        $admin = $request->user();

        $result = DB::transaction(function () use ($admin, $user_id) {
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($user_id);

            if (!$client->handler_admin_id) {
                return ['status' => 'empty', 'name' => $client->name];
            }

            if ((int) $client->handler_admin_id !== (int) $admin->id) {
                $handler = User::where('role', 'admin')->find($client->handler_admin_id);
                return ['status' => 'forbidden', 'name' => $client->name, 'handler' => $handler?->name ?? 'admin lain'];
            }

            $client->forceFill(['handler_admin_id' => null])->save();
            return ['status' => 'released', 'name' => $client->name];
        }, 3);

        return match ($result['status']) {
            'released' => back()->with('success', "{$result['name']} sudah dilepas. Admin lain sekarang bisa ambil client ini."),
            'empty' => back()->with('success', "{$result['name']} belum di-handle admin mana pun."),
            default => back()->with('error', "{$result['name']} masih di-handle oleh {$result['handler']}."),
        };
    }

    public function addCourse(Request $request, $user_id)
    {
        $request->validate([
            'course_name' => 'required|string|max:255',
            'course_code' => 'nullable|string|max:50',
        ], [
            'course_name.required' => 'Nama mata kuliah wajib diisi.',
        ]);

        $course = DB::transaction(function () use ($request, $user_id) {
        $client = User::where('role', 'client')->lockForUpdate()->findOrFail($user_id);

        $course = ClientCourse::create([
            'user_id' => $client->id,
            'course_name' => $request->course_name,
            'course_code' => $request->course_code,
        ]);

        // Auto-seed Sesi 1 through Sesi 8
        for ($session = 1; $session <= 8; $session++) {
            AcademicProgress::create([
                'client_course_id' => $course->id,
                'session_number' => $session,
                'discussion_status' => 'Belum Dikerjakan',
                'discussion_score' => null,
                'assignment_status' => 'Belum Dikerjakan',
                'assignment_score' => null,
                'notes' => null,
            ]);
        }
        return $course;
        }, 3);

        return back()->with('success', "Mata kuliah '{$course->course_name}' berhasil ditambahkan (Sesi 1 s/d 8 dibuat otomatis).");
    }

    public function addCourses(Request $request, $user_id)
    {
        $data = $request->validate(['course_list' => 'required|string|max:20000']);
        $rows = CourseImport::parse($data['course_list']);
        $added = DB::transaction(function () use ($rows, $user_id) {
            // Serialize additions for this client, including the single-course form.
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($user_id);
            $existing = ClientCourse::where('user_id', $client->id)->lockForUpdate()->get();
            $names = $codes = [];
            foreach ($existing as $course) {
                $names[CourseImport::key($course->course_name)] = true;
                if ($course->course_code) $codes[CourseImport::key($course->course_code)] = true;
            }
            $added = 0;
            foreach ($rows as $row) {
                $nameKey = CourseImport::key($row['course_name']);
                $codeKey = $row['course_code'] === null ? null : CourseImport::key($row['course_code']);
                if (isset($names[$nameKey]) || ($codeKey !== null && isset($codes[$codeKey]))) continue;
                $course = ClientCourse::create(['user_id' => $client->id] + $row);
                for ($session = 1; $session <= 8; $session++) {
                    $course->progresses()->create(['session_number' => $session,
                        'discussion_status' => 'Belum Dikerjakan', 'assignment_status' => 'Belum Dikerjakan']);
                }
                $names[$nameKey] = true;
                if ($codeKey !== null) $codes[$codeKey] = true;
                $added++;
            }
            return $added;
        }, 3);
        $skipped = count($rows) - $added;
        return redirect()->route('admin.academic.show', $user_id)->with('success',
            "{$added} mata kuliah ditambahkan dengan Sesi 1–8. {$skipped} baris duplikat dilewati. Progres lama tetap tersimpan.");
    }

    public function updateSession(Request $request, $user_id)
    {
        $data = $request->validate([
            'bulk_session' => 'required|integer|min:1|max:8',
            'bulk_discussion_status' => 'required|in:keep,Belum Dikerjakan,On Progress,Menunggu Nilai,Selesai',
            'bulk_assignment_status' => 'required|in:keep,Belum Dikerjakan,On Progress,Menunggu Nilai,Selesai',
        ]);
        $changes = [];
        if ($data['bulk_discussion_status'] !== 'keep') $changes['discussion_status'] = $data['bulk_discussion_status'];
        if ($data['bulk_assignment_status'] !== 'keep') $changes['assignment_status'] = $data['bulk_assignment_status'];
        if (!$changes) throw \Illuminate\Validation\ValidationException::withMessages(['bulk_session' => 'Pilih status diskusi atau tugas yang ingin diperbarui.']);
        $count = DB::transaction(function () use ($user_id, $data, $changes) {
            $client = User::where('role', 'client')->lockForUpdate()->findOrFail($user_id);
            $courses = ClientCourse::where('user_id', $client->id)->orderBy('id')->lockForUpdate()->get();
            if ($courses->isEmpty()) throw \Illuminate\Validation\ValidationException::withMessages(['bulk_session' => 'Tambahkan mata kuliah terlebih dahulu.']);
            foreach ($courses as $course) {
                $progress = AcademicProgress::where('client_course_id', $course->id)->where('session_number', $data['bulk_session'])->lockForUpdate()->first();
                if (!$progress) {
                    $progress = new AcademicProgress(['client_course_id' => $course->id, 'session_number' => $data['bulk_session'],
                        'discussion_status' => 'Belum Dikerjakan', 'assignment_status' => 'Belum Dikerjakan']);
                }
                // Only statuses are updated; existing scores and notes are preserved.
                $progress->fill($changes)->save();
            }
            return $courses->count();
        }, 3);
        return redirect()->route('admin.academic.show', $user_id)->with('success', "Status Sesi {$data['bulk_session']} berhasil diterapkan ke {$count} mata kuliah. Nilai dan catatan tetap.");
    }

    public function updateProgress(Request $request, $progress_id)
    {
        $progress = AcademicProgress::with('course.user')->findOrFail($progress_id);

        $request->validate([
            'discussion_status' => 'required|in:Belum Dikerjakan,On Progress,Menunggu Nilai,Selesai',
            'discussion_score' => 'nullable|integer|min:0|max:100',
            'assignment_status' => 'required|in:Belum Dikerjakan,On Progress,Menunggu Nilai,Selesai',
            'assignment_score' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $progress->update([
            'discussion_status' => $request->discussion_status,
            'discussion_score' => $request->discussion_score,
            'assignment_status' => $request->assignment_status,
            'assignment_score' => $request->assignment_score,
            'notes' => $request->notes,
        ]);

        return back()->with('success', "Progress Sesi {$progress->session_number} untuk {$progress->course->course_name} berhasil diperbarui.");
    }

    public function destroyCourse($course_id)
    {
        $course = ClientCourse::findOrFail($course_id);
        $courseName = $course->course_name;
        $course->delete();

        return back()->with('success', "Mata kuliah '{$courseName}' beserta seluruh progress Sesi 1-8 berhasil dihapus.");
    }
}
