class AppConstants {
  static const baseUrl = 'http://192.168.1.100:8000/api/v1';
  static const appName = 'IPL Griya Pesona Madani';
  static const perumahan = 'Perumahan Griya Pesona Madani Tenjo';
  static const blok = 'E';

  static const midtransClientKey = 'SB-Mid-client-xxxxxxxxxxxx';
  static const midtransIsProduction = false;

  static const Map<String, String> namaBulan = {
    '1': 'Januari', '2': 'Februari', '3': 'Maret',
    '4': 'April', '5': 'Mei', '6': 'Juni',
    '7': 'Juli', '8': 'Agustus', '9': 'September',
    '10': 'Oktober', '11': 'November', '12': 'Desember',
  };

  static const Map<String, String> statusTagihan = {
    'belum_bayar': 'Belum Bayar',
    'sudah_bayar': 'Lunas',
    'terlambat': 'Terlambat',
  };

  static const Map<String, String> statusPengaduan = {
    'baru': 'Baru',
    'diproses': 'Diproses',
    'selesai': 'Selesai',
    'ditolak': 'Ditolak',
  };

  static const Map<String, String> kategoriPengaduan = {
    'infrastruktur': 'Infrastruktur',
    'kebersihan': 'Kebersihan',
    'keamanan': 'Keamanan',
    'fasilitas': 'Fasilitas',
    'sosial': 'Sosial',
    'lainnya': 'Lainnya',
  };
}
