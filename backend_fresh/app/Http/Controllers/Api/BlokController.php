<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blok;
use App\Models\Warga;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlokController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $bloks = Blok::orderBy('kode')->get()->map(function ($b) {
            $b->jumlah_warga_aktif = Warga::where('blok', $b->kode)->where('is_active', true)->count();
            $b->jumlah_warga_total = Warga::where('blok', $b->kode)->count();
            return $b;
        });

        return response()->json([
            'data' => $bloks,
            'total' => $bloks->count(),
            'aktif' => $bloks->where('is_active', true)->count(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode' => 'required|string|max:10|unique:bloks,kode',
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'jumlah_rumah' => 'nullable|integer|min:0',
        ]);

        $blok = Blok::create($validated);

        AuditLogger::log('created', "Blok baru ditambah: {$blok->kode} - {$blok->nama}", $blok, newValues: $blok->toArray(), severity: 'info');

        return response()->json([
            'message' => 'Blok berhasil ditambahkan.',
            'data' => $blok,
        ], 201);
    }

    public function show(Blok $blok): JsonResponse
    {
        $blok->jumlah_warga_aktif = Warga::where('blok', $blok->kode)->where('is_active', true)->count();
        $blok->jumlah_warga_total = Warga::where('blok', $blok->kode)->count();
        return response()->json(['data' => $blok]);
    }

    public function update(Request $request, Blok $blok): JsonResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:10', Rule::unique('bloks', 'kode')->ignore($blok->id)],
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'jumlah_rumah' => 'nullable|integer|min:0',
        ]);

        $old = $blok->toArray();
        $oldKode = $blok->kode;
        $blok->update($validated);

        // Kalau kode blok berubah, update juga semua warga yang punya kode lama
        if ($oldKode !== $blok->kode) {
            Warga::where('blok', $oldKode)->update(['blok' => $blok->kode]);
        }

        AuditLogger::log('updated', "Blok diubah: {$blok->kode} - {$blok->nama}", $blok, oldValues: $old, newValues: $blok->toArray());

        return response()->json([
            'message' => 'Blok berhasil diperbarui.',
            'data' => $blok->fresh(),
        ]);
    }

    public function destroy(Blok $blok): JsonResponse
    {
        // Cek apakah ada warga di blok ini
        $jumlahWarga = Warga::where('blok', $blok->kode)->count();
        if ($jumlahWarga > 0) {
            return response()->json([
                'message' => "Blok {$blok->kode} masih punya {$jumlahWarga} warga. Pindahkan warga dulu sebelum hapus blok.",
            ], 422);
        }

        $old = $blok->toArray();
        $blok->delete();

        AuditLogger::log('deleted', "Blok dihapus: {$old['kode']} - {$old['nama']}", null, oldValues: $old, severity: 'warning');

        return response()->json(['message' => 'Blok berhasil dihapus.']);
    }
}
