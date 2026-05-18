/// Helper untuk parsing yang defensive (handle int/string/double dari API)
int _toInt(dynamic v, [int fallback = 0]) {
  if (v == null) return fallback;
  if (v is int) return v;
  if (v is double) return v.toInt();
  if (v is String) return int.tryParse(v) ?? double.tryParse(v)?.toInt() ?? fallback;
  if (v is num) return v.toInt();
  return fallback;
}

double _toDouble(dynamic v, [double fallback = 0]) {
  if (v == null) return fallback;
  if (v is double) return v;
  if (v is int) return v.toDouble();
  if (v is String) return double.tryParse(v) ?? fallback;
  if (v is num) return v.toDouble();
  return fallback;
}

class TagihanModel {
  final int id;
  final int wargaId;
  final String jenis;
  final int bulan;
  final int tahun;
  final double nominal;
  final double denda;
  final String status;
  final DateTime jatuhTempo;
  final String? tanggalBayar;
  final String? keterangan;
  final String namaBulan;
  final double totalTagihan;
  final PembayaranModel? pembayaran;

  const TagihanModel({
    required this.id,
    required this.wargaId,
    required this.jenis,
    required this.bulan,
    required this.tahun,
    required this.nominal,
    required this.denda,
    required this.status,
    required this.jatuhTempo,
    this.tanggalBayar,
    this.keterangan,
    required this.namaBulan,
    required this.totalTagihan,
    this.pembayaran,
  });

  factory TagihanModel.fromJson(Map<String, dynamic> json) {
    DateTime parseDate(dynamic v) {
      if (v is String && v.isNotEmpty) {
        try { return DateTime.parse(v); } catch (_) {}
      }
      return DateTime.now();
    }
    return TagihanModel(
      id: _toInt(json['id']),
      wargaId: _toInt(json['warga_id']),
      jenis: json['jenis']?.toString() ?? 'ipl_bulanan',
      bulan: _toInt(json['bulan']),
      tahun: _toInt(json['tahun']),
      nominal: _toDouble(json['nominal']),
      denda: _toDouble(json['denda']),
      status: json['status']?.toString() ?? 'belum_bayar',
      jatuhTempo: parseDate(json['jatuh_tempo']),
      tanggalBayar: json['tanggal_bayar']?.toString(),
      keterangan: json['keterangan']?.toString(),
      namaBulan: json['nama_bulan']?.toString() ?? '',
      totalTagihan: _toDouble(json['total_tagihan'] ?? json['nominal']),
      pembayaran: json['pembayaran'] != null
          ? PembayaranModel.fromJson(json['pembayaran'] as Map<String, dynamic>)
          : null,
    );
  }

  bool get isBelumBayar => status == 'belum_bayar' || status == 'terlambat';
  bool get isSudahBayar => status == 'sudah_bayar';
  bool get isTerlambat => status == 'terlambat';
}

class PembayaranModel {
  final int id;
  final int tagihanId;
  final String orderId;
  final double nominal;
  final String? snapToken;
  final String? redirectUrl;
  final String status;
  final String? paymentType;
  final String? catatan;
  final String? midtransTransactionId;
  final DateTime createdAt;
  final DateTime? updatedAt;
  final TagihanModel? tagihan;

  const PembayaranModel({
    required this.id,
    required this.tagihanId,
    required this.orderId,
    required this.nominal,
    this.snapToken,
    this.redirectUrl,
    required this.status,
    this.paymentType,
    this.catatan,
    this.midtransTransactionId,
    required this.createdAt,
    this.updatedAt,
    this.tagihan,
  });

  factory PembayaranModel.fromJson(Map<String, dynamic> json) {
    DateTime parseDate(dynamic v) {
      if (v is String && v.isNotEmpty) {
        try { return DateTime.parse(v); } catch (_) {}
      }
      return DateTime.now();
    }
    return PembayaranModel(
      id: _toInt(json['id']),
      tagihanId: _toInt(json['tagihan_id']),
      orderId: json['order_id']?.toString() ?? '',
      nominal: _toDouble(json['nominal']),
      snapToken: json['midtrans_snap_token']?.toString(),
      redirectUrl: json['midtrans_redirect_url']?.toString(),
      status: json['status']?.toString() ?? 'pending',
      paymentType: json['midtrans_payment_type']?.toString(),
      catatan: json['catatan']?.toString(),
      midtransTransactionId: json['midtrans_transaction_id']?.toString(),
      createdAt: parseDate(json['created_at']),
      updatedAt: json['updated_at'] != null ? parseDate(json['updated_at']) : null,
      tagihan: json['tagihan'] != null ? TagihanModel.fromJson(json['tagihan'] as Map<String, dynamic>) : null,
    );
  }

  bool get isPending => status == 'pending';
  bool get isSuccess => status == 'success';

  String get methodLabel {
    final m = paymentType;
    if (m == null || m.isEmpty) return 'Pembayaran';
    return {
      'tunai': '💵 Tunai',
      'transfer': '🏦 Transfer',
      'lainnya': '📋 Lainnya',
      'bank_transfer': '🏦 Bank Transfer',
      'gopay': '🟢 GoPay',
      'shopeepay': '🟧 ShopeePay',
      'qris': '📱 QRIS',
      'credit_card': '💳 Kartu Kredit',
      'echannel': '🏦 Mandiri Bill',
      'cstore': '🏪 Convenience Store',
    }[m] ?? '💳 ${m.toUpperCase()}';
  }

  String get statusLabel {
    switch (status) {
      case 'success': return 'Berhasil';
      case 'pending': return 'Menunggu Pembayaran';
      case 'failed': return 'Gagal';
      case 'expired': return 'Kedaluwarsa';
      case 'cancel': return 'Dibatalkan';
      default: return status;
    }
  }
}
