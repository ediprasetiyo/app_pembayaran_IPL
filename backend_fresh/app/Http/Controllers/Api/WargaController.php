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
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
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
            // Normalize tanggal_lahir KK
            $tglLahirKK = null;
            if (!empty($request->tanggal_lahir)) {
                try {
                    $tglLahirKK = \Carbon\Carbon::parse($request->tanggal_lahir)->format('Y-m-d');
                } catch (\Throwable $e) { /* abaikan */ }
            }
            // Normalize tanggal_pindah
            $tglPindah = null;
            if (!empty($request->tanggal_pindah)) {
                try {
                    $tglPindah = \Carbon\Carbon::parse($request->tanggal_pindah)->format('Y-m-d');
                } catch (\Throwable $e) { /* abaikan */ }
            }

            $user = User::create([
                'name' => $request->name,
                'phone' => $request->phone,
                'tanggal_lahir' => $tglLahirKK,
                'tempat_lahir' => $request->tempat_lahir,
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
                'tanggal_pindah' => $tglPindah,
                'nik' => $request->nik,
                'uang_kedukaan_dibayar' => $sudahBayarKedukaan,
                'tanggal_bayar_kedukaan' => $sudahBayarKedukaan ? now() : null,
            ]);

            if ($request->anggota_keluarga) {
                foreach ($request->anggota_keluarga as $anggota) {
                    // Normalize tanggal_lahir untuk setiap anggota
                    if (!empty($anggota['tanggal_lahir'])) {
                        try {
                            $anggota['tanggal_lahir'] = \Carbon\Carbon::parse($anggota['tanggal_lahir'])->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $anggota['tanggal_lahir'] = null;
                        }
                    }
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
            'tanggal_lahir' => 'nullable|date',
            'tempat_lahir' => 'nullable|string|max:100',
            'nomor_kk' => 'nullable|string|size:16|unique:warga,nomor_kk,' . $warga->id,
            'nomor_rumah' => 'sometimes|string|max:10',
            'status_hunian' => 'sometimes|in:milik,sewa,kontrak',
            'rt' => 'nullable|string',
            'rw' => 'nullable|string',
            'tanggal_pindah' => 'nullable|date',
            'nik' => 'nullable|string|size:16|unique:warga,nik,' . $warga->id,
            'is_active' => 'sometimes|boolean',
            'uang_kedukaan_dibayar' => 'sometimes|boolean',
            'catatan' => 'nullable|string',
            // Password optional saat update — hanya update kalau dikirim
            'password' => 'nullable|string|min:6',
            // Anggota keluarga sync
            'anggota_keluarga' => 'nullable|array',
            'anggota_keluarga.*.nama' => 'required_with:anggota_keluarga|string',
            'anggota_keluarga.*.hubungan' => 'required_with:anggota_keluarga|in:kepala_keluarga,istri,anak,orang_tua,saudara,lainnya',
            'anggota_keluarga.*.jenis_kelamin' => 'required_with:anggota_keluarga|in:laki_laki,perempuan',
            'anggota_keluarga.*.tanggal_lahir' => 'nullable|date',
            'anggota_keluarga.*.nik' => 'nullable|string|max:16',
        ]);

        DB::beginTransaction();
        try {
            // Update User (KK) — name, phone, tanggal_lahir, tempat_lahir, password
            $userUpdate = $request->only(['name', 'phone', 'tanggal_lahir', 'tempat_lahir']);
            // Normalize tanggal_lahir
            if (!empty($userUpdate['tanggal_lahir'])) {
                try {
                    $userUpdate['tanggal_lahir'] = \Carbon\Carbon::parse($userUpdate['tanggal_lahir'])->format('Y-m-d');
                } catch (\Throwable $e) {
                    unset($userUpdate['tanggal_lahir']);
                }
            }
            if ($request->filled('password')) {
                $userUpdate['password'] = Hash::make($request->password);
            }
            if (!empty($userUpdate)) {
                $warga->user->update($userUpdate);
            }

            $statusKedukaanLama = $warga->uang_kedukaan_dibayar;

            $updateData = $request->only([
                'nomor_kk', 'nomor_rumah', 'blok', 'rt', 'rw',
                'status_hunian', 'tanggal_pindah', 'nik',
                'is_active', 'catatan',
            ]);
            // Normalize tanggal_pindah
            if (!empty($updateData['tanggal_pindah'])) {
                try {
                    $updateData['tanggal_pindah'] = \Carbon\Carbon::parse($updateData['tanggal_pindah'])->format('Y-m-d');
                } catch (\Throwable $e) {
                    unset($updateData['tanggal_pindah']);
                }
            }

            // Khusus uang_kedukaan_dibayar
            if ($request->has('uang_kedukaan_dibayar')) {
                $sudahBayar = $request->boolean('uang_kedukaan_dibayar');
                $updateData['uang_kedukaan_dibayar'] = $sudahBayar;
                $updateData['tanggal_bayar_kedukaan'] = $sudahBayar
                    ? ($warga->tanggal_bayar_kedukaan ?? now())
                    : null;
            }

            $warga->update($updateData);

            // ===== SYNC ANGGOTA KELUARGA =====
            // Strategi: kalau request kirim anggota_keluarga array, hapus semua dulu lalu insert ulang.
            // Cara ini paling reliable & match dengan UI yang allow add/remove arbitrarily.
            if ($request->has('anggota_keluarga')) {
                $existingIds = $warga->anggotaKeluarga()->pluck('id')->toArray();
                $payloadIds = collect($request->anggota_keluarga)->pluck('id')->filter()->toArray();

                // Hapus anggota yang ID-nya tidak ada di payload (user menghapus dari form)
                $toDelete = array_diff($existingIds, $payloadIds);
                if (!empty($toDelete)) {
                    \App\Models\AnggotaKeluarga::whereIn('id', $toDelete)->delete();
                }

                // Upsert anggota dari payload
                foreach ($request->anggota_keluarga as $anggota) {
                    $data = collect($anggota)->only([
                        'nama', 'hubungan', 'jenis_kelamin', 'tanggal_lahir',
                        'nik', 'pekerjaan', 'pendidikan', 'agama', 'status_perkawinan',
                    ])->toArray();
                    // Bersihkan empty string jadi null untuk field nullable
                    foreach (['tanggal_lahir', 'nik', 'pekerjaan', 'pendidikan', 'agama', 'status_perkawinan'] as $k) {
                        if (isset($data[$k]) && $data[$k] === '') $data[$k] = null;
                    }
                    // Normalize tanggal_lahir dari format apapun (ISO, dd/mm/yyyy, dll) ke Y-m-d
                    if (!empty($data['tanggal_lahir'])) {
                        try {
                            $data['tanggal_lahir'] = \Carbon\Carbon::parse($data['tanggal_lahir'])->format('Y-m-d');
                        } catch (\Throwable $e) {
                            $data['tanggal_lahir'] = null;
                        }
                    }
                    if (!empty($anggota['id']) && in_array($anggota['id'], $existingIds)) {
                        // Update existing
                        \App\Models\AnggotaKeluarga::where('id', $anggota['id'])->update($data);
                    } else {
                        // Insert new
                        $warga->anggotaKeluarga()->create($data);
                    }
                }
            }

            if ($request->has('uang_kedukaan_dibayar')) {
                $sudahBayar = $request->boolean('uang_kedukaan_dibayar');

                if ($sudahBayar) {
                    // CENTANG (warga lama) → hapus tagihan kedukaan yang belum bayar
                    IplTagihan::where('warga_id', $warga->id)
                        ->where('jenis', 'kedukaan')
                        ->where('status', 'belum_bayar')
                        ->delete();
                } else if ($statusKedukaanLama) {
                    // UNCENTANG (warga harus bayar lagi) → buatkan tagihan kedukaan
                    $existsKedukaan = IplTagihan::where('warga_id', $warga->id)
                        ->where('jenis', 'kedukaan')
                        ->where('status', 'belum_bayar')
                        ->exists();

                    if (!$existsKedukaan) {
                        IplTagihan::create([
                            'warga_id' => $warga->id,
                            'jenis' => 'kedukaan',
                            'bulan' => now()->month,
                            'tahun' => now()->year,
                            'nominal' => (int) env('IPL_KEDUKAAN_AMOUNT', 20000),
                            'jatuh_tempo' => now()->setDay(10),
                            'status' => 'belum_bayar',
                            'keterangan' => 'Uang kedukaan (sekali bayar untuk warga baru)',
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Data warga berhasil diperbarui.',
                'warga' => $warga->fresh()->load(['user', 'anggotaKeluarga']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui data: ' . $e->getMessage()], 500);
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
