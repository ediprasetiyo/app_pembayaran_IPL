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
    return WargaModel(
      id: json['id'],
      userId: json['user_id'],
      nomorRumah: json['nomor_rumah'],
      blok: json['blok'],
      rt: json['rt'],
      rw: json['rw'],
      statusHunian: json['status_hunian'],
      tanggalPindah: json['tanggal_pindah'],
      isActive: json['is_active'] ?? true,
      anggotaKeluarga: json['anggota_keluarga'] != null
          ? (json['anggota_keluarga'] as List)
              .map((a) => AnggotaKeluargaModel.fromJson(a))
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
    return AnggotaKeluargaModel(
      id: json['id'],
      wargaId: json['warga_id'],
      nama: json['nama'],
      hubungan: json['hubungan'],
      jenisKelamin: json['jenis_kelamin'],
      tanggalLahir: json['tanggal_lahir'],
      pekerjaan: json['pekerjaan'],
    );
  }
}
