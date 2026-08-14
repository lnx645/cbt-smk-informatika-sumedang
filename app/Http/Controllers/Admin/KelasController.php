<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $kelas = Kelas::with(['jurusan', 'walikelas'])->get();

        $buildTree = function ($parentId) use ($kelas, &$buildTree) {
            return $kelas
                ->where('parent_id', $parentId)
                ->sortBy('nama')
                ->map(function ($item) use ($buildTree) {
                    $node = $item->toArray();
                    $node['children'] = $buildTree($item->id);

                    return $node;
                })
                ->values()
                ->all();
        };

        return inertia('admin/Kelas/Index', [
            'kelas_parent' => $buildTree(null),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
