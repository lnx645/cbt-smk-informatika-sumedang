<?php

use App\Models\JamPelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    JamPelajaran::insert([
        ['label' => 'JP 1', 'jam_mulai' => '08:00', 'jam_selesai' => '08:45', 'is_break' => false, 'urutan' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['label' => 'Istirahat 1', 'jam_mulai' => '09:30', 'jam_selesai' => '10:00', 'is_break' => true, 'urutan' => 2, 'created_at' => now(), 'updated_at' => now()],
        ['label' => 'JP 2', 'jam_mulai' => '10:00', 'jam_selesai' => '10:45', 'is_break' => false, 'urutan' => 3, 'created_at' => now(), 'updated_at' => now()],
    ]);
    $this->actingAs(User::factory()->create(['role' => 'admin']), 'web');
});

it('displays the jam pelajaran index page', function () {
    $response = $this->get('/admin/jam-pelajaran');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/JamPelajaran/Index')
        ->has('jpList'),
    );
});

it('can create a new jam pelajaran', function () {
    $response = $this->post('/admin/jam-pelajaran', [
        'label' => 'JP Uji',
        'jam_mulai' => '07:00',
        'jam_selesai' => '07:45',
        'is_break' => false,
        'urutan' => 99,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('jam_pelajarans', [
        'label' => 'JP Uji',
        'jam_mulai' => '07:00',
        'jam_selesai' => '07:45',
        'urutan' => 99,
    ]);
});

it('can update an existing jam pelajaran', function () {
    $jp = JamPelajaran::where('label', 'JP 1')->first();

    $response = $this->put("/admin/jam-pelajaran/{$jp->id}", [
        'label' => 'JP 1 Updated',
        'jam_mulai' => '08:30',
        'jam_selesai' => '09:15',
        'is_break' => false,
        'urutan' => 1,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('jam_pelajarans', [
        'id' => $jp->id,
        'label' => 'JP 1 Updated',
        'jam_mulai' => '08:30',
    ]);
});

it('can delete a jam pelajaran', function () {
    $jp = JamPelajaran::where('label', 'JP 1')->first();

    $response = $this->delete("/admin/jam-pelajaran/{$jp->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('jam_pelajarans', ['id' => $jp->id]);
});

it('validates required fields on store', function () {
    $response = $this->post('/admin/jam-pelajaran', []);

    $response->assertSessionHasErrors(['label', 'jam_mulai', 'jam_selesai', 'urutan']);
});

it('validates end time is after start time', function () {
    $response = $this->post('/admin/jam-pelajaran', [
        'label' => 'Test',
        'jam_mulai' => '10:00',
        'jam_selesai' => '09:00',
        'is_break' => false,
        'urutan' => 10,
    ]);

    $response->assertSessionHasErrors('jam_selesai');
});
