<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        $events = Event::orderByDesc('created_at')->paginate(20);

        return Inertia::render('panitia/Event/Index', [
            'events' => $events,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:uts,uas,usbk'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'in:draft,aktif,selesai'],
        ]);

        Event::create($data);

        return back()->with('success', 'Event berhasil dibuat.');
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'kategori' => ['required', 'in:uts,uas,usbk'],
            'deskripsi' => ['nullable', 'string'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'status' => ['required', 'in:draft,aktif,selesai'],
        ]);

        $event->update($data);

        return back()->with('success', 'Event berhasil diupdate.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        return back()->with('success', 'Event berhasil dihapus.');
    }
}
