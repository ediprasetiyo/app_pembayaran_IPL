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
            // Data Kepala Keluarga
            'name' => 'required|string|max:100',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'nik' => 'nullable|string|size:16|unique:warga,nik',
            'nomor_kk' => 'nullable|string|size:16|unique:warga,nomor_kk',
            // Data Hunian
            'nomor_rumah' => 'required|string|max:10',
            'blok' => 'sometimes|string|max:5',
            'rt' => 'nullable|string|max:5',
            'rw' => 'nullable|string|max:5',
            'status_hunian' => 'required|in:milik,sewa,kontrak',
            'tanggal_pindah' => 'nullable|date',
            'uang_kedukaan_dibayar' => 'sometimes|boolean',
            // Anggota keluarga
            'anggota_keluarga' => 'nullable|array',
            'anggota_keluarga.*.nama' => 'required|string',
            'anggota_keluarga.*.hubungan' => 'required|in:kepala_keluarga,istri,anak,orang_tua,saudara,lainnya',
            'anggota_keluarga.*.jenis_kelamin' => 'required|in:laki_laki,perempuan',
            'anggota_keluarga.*.tanggal_lahir' => 'nullable|date',
            'anggota_keluarga.*.nik' => 'nullable|string|size:16',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'warga',
            ]);

            $sudahBayarKedukaan = (bool) $request->boolean('uang_kedukaan_dibayar');

            $warga = Warga::create([
                'user_id' => $user->id,
                'nomor_kk' => $request->nomor_kk,
                'nomor_rumah' => $request->nomor_rumah,
                'blok' => $request->blok ?? 'E',
                'rt' => $request->rt,
                'rw' => $request->rw,
                'status_hunian' => $request->status_hunian,
                'tanggal_pindah' => $request->tanggal_pindah,
                'nik' => $request->nik,
                'uang_kedukaan_dibayar' => $sudahBayarKedukaan,
                'tanggal_bayar_kedukaan' => $sudahBayarKedukaan ? now() : null,
            ]);

            if ($request->anggota_keluarga) {
                foreach ($request->anggota_keluarga as $anggota) {
                    $warga->anggotaKeluarga()->create($anggota);
                }
            }

            $this->generateTagihanBulanBerjalan($warga, !$sudahBayarKedukaan);

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

    public function destroy(Warga $warga): JsonResponse
    {
        DB::beginTransaction();
        try {
            // Hapus user yang terkait → otomatis hapus warga, anggota, tagihan via cascade
            $warga->user?->delete();
            $warga->delete();

            DB::commit();
            return response()->json(['message' => 'Data warga berhasil dihapus.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus: ' . $e->getMessage()], 500);
        }
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

        // Tagihan IPL bulan ini (jenis ipl_bulanan saja)
        $tagihanIplBulanIni = IplTagihan::bulanIni()->where('jenis', 'ipl_bulanan');
        $totalTagihanBulanIni = (clone $tagihanIplBulanIni)->count();
        $sudahBayar = (clone $tagihanIplBulanIni)->where('ipl_tagihan.status', 'sudah_bayar')->count();
        $belumBayar = (clone $tagihanIplBulanIni)->belumBayar()->count();

        // Total pendapatan IPL bulan ini
        $totalPendapatan = IplTagihan::bulanIni()
            ->where('ipl_tagihan.jenis', 'ipl_bulanan')
            ->where('ipl_tagihan.status', 'sudah_bayar')
            ->join('pembayaran', 'ipl_tagihan.id', '=', 'pembayaran.tagihan_id')
            ->where('pembayaran.status', 'success')
            ->sum('pembayaran.nominal');

        // === SUMMARY UANG KEDUKAAN ===
        // Total warga yang sudah bayar kedukaan
        $kedukaanWargaSudahBayar = Warga::where('blok', 'E')
            ->where('is_active', true)
            ->where('uang_kedukaan_dibayar', true)
            ->count();

        $kedukaanWargaBelumBayar = Warga::where('blok', 'E')
            ->where('is_active', true)
            ->where('uang_kedukaan_dibayar', false)
            ->count();

        // Total dana kedukaan yang sudah terkumpul (dari pembayaran tagihan jenis 'kedukaan')
        $tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
        $kedukaanTerkumpul = $kedukaanWargaSudahBayar * $tarifKedukaan;

        // Atau dari pembayaran riil (lebih akurat untuk yg via Midtrans)
        $kedukaanFromPembayaran = IplTagihan::where('ipl_tagihan.jenis', 'kedukaan')
            ->where('ipl_tagihan.status', 'sudah_bayar')
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
            'kedukaan' => [
                'total_dana' => max($kedukaanTerkumpul, (int) $kedukaanFromPembayaran),
                'warga_sudah_bayar' => $kedukaanWargaSudahBayar,
                'warga_belum_bayar' => $kedukaanWargaBelumBayar,
                'tarif_per_warga' => $tarifKedukaan,
            ],
        ]);
    }

    private function generateTagihanBulanBerjalan(Warga $warga, bool $buatTagihanKedukaan = true): void
    {
        $tarifIpl = (int) env('IPL_MONTHLY_AMOUNT', 65000);
        $tarifKedukaan = (int) env('IPL_KEDUKAAN_AMOUNT', 20000);
        $jatuhTempo = now()->setDay(10);

        // Tagihan IPL bulanan (selalu dibuat)
        IplTagihan::create([
            'warga_id' => $warga->id,
            'jenis' => 'ipl_bulanan',
            'bulan' => now()->month,
            'tahun' => now()->year,
            'nominal' => $tarifIpl,
            'jatuh_tempo' => $jatuhTempo,
            'status' => 'belum_bayar',
        ]);

        // Tagihan kedukaan — hanya untuk warga baru yang belum pernah bayar
        if ($buatTagihanKedukaan) {
            IplTagihan::create([
                'warga_id' => $warga->id,
                'jenis' => 'kedukaan',
                'bulan' => now()->month,
                'tahun' => now()->year,
                'nominal' => $tarifKedukaan,
                'jatuh_tempo' => $jatuhTempo,
                'status' => 'belum_bayar',
                'keterangan' => 'Uang kedukaan (sekali bayar untuk warga baru)',
            ]);
        }
    }
}
