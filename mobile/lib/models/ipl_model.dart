class TagihanModel {
  final int id;
  final int wargaId;
  final int bulan;
  final int tahun;
  final double nominal;
  final double denda;
  final String status;
  final String jatuhTempo;
  final String? tanggalBayar;
  final String? keterangan;
  final String namaBulan;
  final double totalTagihan;
  final PembayaranModel? pembayaran;

  const TagihanModel({
    required this.id,
    required this.wargaId,
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
      bulan: json['bulan'],
      tahun: json['tahun'],
      nominal: double.parse(json['nominal'].toString()),
      denda: double.parse(json['denda'].toString()),
      status: json['status'],
      jatuhTempo: json['jatuh_tempo'],
      tanggalBayar: json['tanggal_bayar'],
      keterangan: json['keterangan'],
      namaBulan: json['nama_bulan'] ?? '',
      totalTagihan: double.parse(json['total_tagihan'].toString()),
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

  const PembayaranModel({
    required this.id,
    required this.tagihanId,
    required this.orderId,
    required this.nominal,
    this.snapToken,
    this.redirectUrl,
    required this.status,
    this.paymentType,
  });

  factory PembayaranModel.fromJson(Map<String, dynamic> json) {
    return PembayaranModel(
      id: json['id'],
      tagihanId: json['tagihan_id'],
      orderId: json['order_id'],
      nominal: double.parse(json['nominal'].toString()),
      snapToken: json['midtrans_snap_token'],
      redirectUrl: json['midtrans_redirect_url'],
      status: json['status'],
      paymentType: json['midtrans_payment_type'],
    );
  }

  bool get isPending => status == 'pending';
  bool get isSuccess => status == 'success';
}
