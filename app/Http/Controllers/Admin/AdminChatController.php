<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminChatMessage;
use App\Support\AdminChatSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminChatController extends Controller
{
    public function index()
    {
        $ready = Schema::hasTable('admin_chat_messages');

        return response()->view('admin.chat', compact('ready'))->header('Cache-Control', 'no-store');
    }

    public function activate()
    {
        try {
            AdminChatSchema::ensureCreated();
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'Chat belum dapat diaktifkan. Periksa izin pembuatan tabel database dan log aplikasi, lalu coba lagi.');
        }

        return redirect()->route('admin.chat.index')->with('success', 'Chat Admin aktif. Semua admin dapat mulai berkirim pesan.');
    }

    public function messages(Request $request)
    {
        abort_unless(Schema::hasTable('admin_chat_messages'), 409, 'Aktifkan Chat Admin terlebih dahulu.');
        $data = $request->validate([
            'after' => ['nullable', 'integer', 'min:0', 'max:9007199254740991', 'prohibits:before'],
            'before' => ['nullable', 'integer', 'min:1', 'max:9007199254740991', 'prohibits:after'],
        ]);
        $query = AdminChatMessage::with('sender:id,name');
        if (isset($data['after'])) {
            $query->where('id', '>', $data['after'])->orderBy('id');
        } else {
            $query->when(isset($data['before']), fn ($q) => $q->where('id', '<', $data['before']))->orderByDesc('id');
        }
        $rows = $query->limit(51)->get();
        $hasMore = $rows->count() > 50;
        $messages = $rows->take(50)->sortBy('id')->values()->map(fn ($message) => $this->payload($message, $request));

        return response()->json(['messages' => $messages, 'has_more' => $hasMore])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('admin_chat_messages'), 409, 'Aktifkan Chat Admin terlebih dahulu.');
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000', 'regex:/\S/u'],
            'client_message_id' => ['required', 'uuid'],
        ], ['body.required' => 'Tulis pesan terlebih dahulu.', 'body.max' => 'Pesan maksimal 2.000 karakter.']);

        // Retrying a request after connection loss must not post the same message twice.
        $message = AdminChatMessage::firstOrCreate(['client_message_id' => $data['client_message_id']], [
            'user_id' => $request->user()->id,
            'sender_name' => $request->user()->name,
            'body' => $data['body'],
        ]);
        abort_unless((int) $message->user_id === (int) $request->user()->id, 409, 'ID pesan sudah digunakan. Muat ulang halaman.');

        return response()->json(['message' => $this->payload($message, $request)], $message->wasRecentlyCreated ? 201 : 200)
            ->header('Cache-Control', 'no-store');
    }

    private function payload(AdminChatMessage $message, Request $request): array
    {
        return [
            'id' => $message->id,
            'name' => $message->sender?->name ?? $message->sender_name,
            'body' => $message->body,
            'mine' => (int) $message->user_id === (int) $request->user()->id,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}
