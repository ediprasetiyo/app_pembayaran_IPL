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
    return TagihanModel(
      id: json['id'],
      wargaId: json['warga_id'],
      jenis: json['jenis'] ?? 'ipl_bulanan',
      bulan: json['bulan'],
      tahun: json['tahun'],
      nominal: double.parse(json['nominal'].toString()),
      denda: double.parse((json['denda'] ?? 0).toString()),
      status: json['status'] ?? 'belum_bayar',
      jatuhTempo: DateTime.parse(json['jatuh_tempo']),
      tanggalBayar: json['tanggal_bayar'],
      keterangan: json['keterangan'],
      namaBulan: json['nama_bulan'] ?? '',
      totalTagihan: double.parse((json['total_tagihan'] ?? json['nominal']).toString()),
      pembayaran: json['pembayaran'] != null
          ? PembayaranModel.fromJson(json['pembayaran'])
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
    return PembayaranModel(
      id: json['id'],
      tagihanId: json['tagihan_id'],
      orderId: json['order_id'] ?? '',
      nominal: double.parse((json['nominal'] ?? 0).toString()),
      snapToken: json['midtrans_snap_token'],
      redirectUrl: json['midtrans_redirect_url'],
      status: json['status'] ?? 'pending',
      paymentType: json['midtrans_payment_type'],
      catatan: json['catatan'],
      midtransTransactionId: json['midtrans_transaction_id'],
      createdAt: DateTime.parse(json['created_at']),
      updatedAt: json['updated_at'] != null ? DateTime.parse(json['updated_at']) : null,
      tagihan: json['tagihan'] != null ? TagihanModel.fromJson(json['tagihan']) : null,
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
