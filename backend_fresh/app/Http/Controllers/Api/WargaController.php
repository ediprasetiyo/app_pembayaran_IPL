<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnggotaKeluarga;
use App\Models\IplTagihan;
use App\Models\User;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WargaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Warga::with(['user', 'anggotaKeluarga'])
            ->where('blok', 'E');

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('phone', 'like', "%{$request->search}%");
            })->orWhere('nomor_rumah', 'like', "%{$request->search}%");
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'aktif');
        }

        return response()->json($query->orderBy('nomor_rumah')->paginate(15));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'nomor_rumah' => 'required|string|max:10',
            'blok' => 'sometimes|string|max:5',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'status_hunian' => 'required|in:milik,sewa,kontrak',
            'tanggal_pindah' => 'nullable|date',
            'nik' => 'nullable|string|max:20|unique:warga,nik',
            'alamat_asal' => 'nullable|string',
            'anggota_keluarga' => 'nullable|array',
            'anggota_keluarga.*.nama' => 'required|string',
            'anggota_keluarga.*.hubungan' => 'required|in:kepala_keluarga,istri,anak,orang_tua,saudara,lainnya',
            'anggota_keluarga.*.jenis_kelamin' => 'required|in:laki_laki,perempuan',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'warga',
            ]);

            $warga = Warga::create([
                'user_id' => $user->id,
                'nomor_rumah' => $request->nomor_rumah,
                'blok' => $request->blok ?? 'E',
                'rt' => $request->rt,
                'rw' => $request->rw,
                'status_hunian' => $request->status_hunian,
                'tanggal_pindah' => $request->tanggal_pindah,
                'nik' => $request->nik,
                'alamat_asal' => $request->alamat_asal,
            ]);

            if ($request->anggota_keluarga) {
                foreach ($request->anggota_keluarga as $anggota) {
                    $warga->anggotaKeluarga()->create($anggota);
                }
            }

            $this->generateTagihanBulanBerjalan($warga);

            DB::commit();

            return response()->json([
                'message' => 'Data warga berhasil ditambahkan.',
                'warga' => $warga->load(['user', 'anggotaKeluarga']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan data: ' . $e->getMessage()], 500);
        }
    }

    public function show(Warga $warga): JsonResponse
    {
        return response()->json([
            'warga' => $warga->load(['user', 'anggotaKeluarga', 'tagihan']),
        ]);
    }

    public function update(Request $request, Warga $warga): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|string|unique:users,phone,' . $warga->user_id,
            'nomor_rumah' => 'sometimes|string|max:10',
            'status_hunian' => 'sometimes|in:milik,sewa,kontrak',
            'rt' => 'nullable|string',
            'rw' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            if ($request->has('name') || $request->has('phone')) {
                $warga->user->update($request->only(['name', 'phone']));
            }

            $warga->update($request->only([
                'nomor_rumah', 'blok', 'rt', 'rw',
                'status_hunian', 'tanggal_pindah', 'nik',
                'alamat_asal', 'is_active', 'catatan',
            ]));

            DB::commit();

            return response()->json([
                'message' => 'Data warga berhasil diperbarui.',
                'warga' => $warga->fresh()->load(['user', 'anggotaKeluarga']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui data.'], 500);
        }
    }

    public function addAnggotaKeluarga(Request $request, Warga $warga): JsonResponse
    {
        $request->validate([
            'nama' => 'required|string',
            'hubungan' => 'required|in:kepala_keluarga,istri,anak,orang_tua,saudara,lainnya',
            'jenis_kelamin' => 'required|in:laki_laki,perempuan',
            'tanggal_lahir' => 'nullable|date',
            'nik' => 'nullable|string|unique:anggota_keluarga,nik',
            'pekerjaan' => 'nullable|string',
            'pendidikan' => 'nullable|string',
            'agama' => 'nullable|string',
        ]);

        $anggota = $warga->anggotaKeluarga()->create($request->all());

        return response()->json([
            'message' => 'Anggota keluarga berhasil ditambahkan.',
            'anggota' => $anggota,
        ], 201);
    }

    public function updateAnggotaKeluarga(Request $request, Warga $warga, AnggotaKeluarga $anggota): JsonResponse
    {
        if ($anggota->warga_id !== $warga->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $anggota->update($request->only([
            'nama', 'hubungan', 'jenis_kelamin', 'tanggal_lahir',
            'nik', 'pekerjaan', 'pendidikan', 'agama', 'status_perkawinan',
        ]));

        return response()->json([
            'message' => 'Data anggota keluarga diperbarui.',
            'anggota' => $anggota->fresh(),
        ]);
    }

    public function deleteAnggotaKeluarga(Warga $warga, AnggotaKeluarga $anggota): JsonResponse
    {
        if ($anggota->warga_id !== $warga->id) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $anggota->delete();

        return response()->json(['message' => 'Anggota keluarga berhasil dihapus.']);
    }

    public function dashboard(): JsonResponse
    {
        $totalWarga = Warga::where('blok', 'E')->where('is_active', true)->count();
        $totalTagihanBulanIni = IplTagihan::bulanIni()->count();
        $sudahBayar = IplTagihan::bulanIni()->where('status', 'sudah_bayar')->count();
        $belumBayar = IplTagihan::bulanIni()->belumBayar()->count();
        $totalPendapatan = IplTagihan::bulanIni()->where('status', 'sudah_bayar')
            ->join('pembayaran', 'ipl_tagihan.id', '=', 'pembayaran.tagihan_id')
            ->where('pembayaran.status', 'success')
            ->sum('pembayaran.nominal');

        return response()->json([
            'total_warga' => $totalWarga,
            'tagihan_bulan_ini' => $totalTagihanBulanIni,
            'sudah_bayar' => $sudahBayar,
            'belum_bayar' => $belumBayar,
            'total_pendapatan' => $totalPendapatan,
            'persentase_bayar' => $totalTagihanBulanIni > 0
                ? round(($sudahBayar / $totalTagihanBulanIni) * 100, 1)
                : 0,
        ]);
    }

    private function generateTagihanBulanBerjalan(Warga $warga): void
    {
        $tarifIpl = config('app.ipl_monthly_amount', 150000);

        IplTagihan::create([
            'warga_id' => $warga->id,
            'bulan' => now()->month,
            'tahun' => now()->year,
            'nominal' => $tarifIpl,
            'jatuh_tempo' => now()->endOfMonth(),
            'status' => 'belum_bayar',
        ]);
    }
}
