class UserModel {
  final int id;
  final String name;
  final String phone;
  final String? email;
  final String role;
  final String language;
  final String? avatar;
  final WargaModel? warga;

  const UserModel({
    required this.id,
    required this.name,
    required this.phone,
    this.email,
    required this.role,
    required this.language,
    this.avatar,
    this.warga,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      email: json['email'],
      role: json['role'],
      language: json['language'] ?? 'id',
      avatar: json['avatar'],
      warga: json['warga'] != null ? WargaModel.fromJson(json['warga']) : null,
    );
  }

  bool get isAdmin => role == 'admin';
}

class WargaModel {
  final int id;
  final int userId;
  final String nomorRumah;
  final String blok;
  final String? rt;
  final String? rw;
  final String statusHunian;
  final String? tanggalPindah;
  final bool isActive;
  final List<AnggotaKeluargaModel> anggotaKeluarga;

  const WargaModel({
    required this.id,
    required this.userId,
    required this.nomorRumah,
    required this.blok,
    this.rt,
    this.rw,
    required this.statusHunian,
    this.tanggalPindah,
    required this.isActive,
    required this.anggotaKeluarga,
  });

  factory WargaModel.fromJson(Map<String, dynamic> json) {
    // Defensive int parsing: server bisa kirim string atau int
    int _toInt(dynamic v, [int fallback = 0]) {
      if (v is int) return v;
      if (v is String) return int.tryParse(v) ?? fallback;
      if (v is num) return v.toInt();
      return fallback;
    }
    String? _toStringNullable(dynamic v) => v?.toString();
    return WargaModel(
      id: _toInt(json['id']),
      userId: _toInt(json['user_id']),
      nomorRumah: _toStringNullable(json['nomor_rumah']) ?? '',
      blok: _toStringNullable(json['blok']) ?? '',
      rt: _toStringNullable(json['rt']),
      rw: _toStringNullable(json['rw']),
      statusHunian: _toStringNullable(json['status_hunian']) ?? 'milik',
      tanggalPindah: _toStringNullable(json['tanggal_pindah']),
      isActive: json['is_active'] == true || json['is_active'] == 1 || json['is_active'] == '1',
      anggotaKeluarga: json['anggota_keluarga'] != null
          ? (json['anggota_keluarga'] as List)
              .map((a) => AnggotaKeluargaModel.fromJson(a as Map<String, dynamic>))
              .toList()
          : [],
    );
  }

  String get alamatLengkap => 'Blok $blok No. $nomorRumah';
}

class AnggotaKeluargaModel {
  final int id;
  final int wargaId;
  final String nama;
  final String hubungan;
  final String jenisKelamin;
  final String? tanggalLahir;
  final String? pekerjaan;

  const AnggotaKeluargaModel({
    required this.id,
    required this.wargaId,
    required this.nama,
    required this.hubungan,
    required this.jenisKelamin,
    this.tanggalLahir,
    this.pekerjaan,
  });

  factory AnggotaKeluargaModel.fromJson(Map<String, dynamic> json) {
    int _toInt(dynamic v) {
      if (v is int) return v;
      if (v is String) return int.tryParse(v) ?? 0;
      if (v is num) return v.toInt();
      return 0;
    }
    return AnggotaKeluargaModel(
      id: _toInt(json['id']),
      wargaId: _toInt(json['warga_id']),
      nama: json['nama']?.toString() ?? '',
      hubungan: json['hubungan']?.toString() ?? 'lainnya',
      jenisKelamin: json['jenis_kelamin']?.toString() ?? 'laki_laki',
      tanggalLahir: json['tanggal_lahir']?.toString(),
      pekerjaan: json['pekerjaan']?.toString(),
    );
  }
}
