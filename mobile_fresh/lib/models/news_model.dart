int _toInt(dynamic v, [int fallback = 0]) {
  if (v == null) return fallback;
  if (v is int) return v;
  if (v is double) return v.toInt();
  if (v is String) return int.tryParse(v) ?? double.tryParse(v)?.toInt() ?? fallback;
  if (v is num) return v.toInt();
  return fallback;
}

class NewsModel {
  final int id;
  final String judul;
  final String slug;
  final String? ringkasan;
  final String konten;
  final String? gambar;
  final String? gambarUrl;
  final String kategori;
  final bool isPinned;
  final DateTime? publishedAt;
  final String? penulis;
  int likesCount;
  int commentsCount;
  bool isLiked;
  final int viewCount;

  NewsModel({
    required this.id,
    required this.judul,
    required this.slug,
    this.ringkasan,
    required this.konten,
    this.gambar,
    this.gambarUrl,
    required this.kategori,
    required this.isPinned,
    this.publishedAt,
    this.penulis,
    this.likesCount = 0,
    this.commentsCount = 0,
    this.isLiked = false,
    this.viewCount = 0,
  });

  factory NewsModel.fromJson(Map<String, dynamic> json) {
    DateTime? parseNullableDate(dynamic v) {
      if (v is String && v.isNotEmpty) {
        try { return DateTime.parse(v); } catch (_) {}
      }
      return null;
    }
    return NewsModel(
      id: _toInt(json['id']),
      judul: json['judul']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      ringkasan: json['ringkasan']?.toString(),
      konten: json['konten']?.toString() ?? '',
      gambar: json['gambar']?.toString(),
      gambarUrl: json['gambar_url']?.toString(),
      kategori: json['kategori']?.toString() ?? 'informasi',
      isPinned: json['is_pinned'] == true || json['is_pinned'] == 1 || json['is_pinned'] == '1',
      publishedAt: parseNullableDate(json['published_at']),
      penulis: json['pembuat']?['name']?.toString(),
      likesCount: _toInt(json['likes_count']),
      commentsCount: _toInt(json['comments_count']),
      isLiked: json['is_liked'] == true || json['is_liked'] == 1 || json['is_liked'] == '1',
      viewCount: _toInt(json['view_count']),
    );
  }

  String get kategoriEmoji {
    switch (kategori) {
      case 'pengumuman': return '📢';
      case 'kegiatan': return '🎉';
      case 'darurat': return '⚠️';
      default: return 'ℹ️';
    }
  }

  String get kategoriLabel {
    switch (kategori) {
      case 'pengumuman': return 'Pengumuman';
      case 'kegiatan': return 'Kegiatan';
      case 'darurat': return 'Darurat';
      default: return 'Informasi';
    }
  }
}

class CommentModel {
  final int id;
  final int newsId;
  final int? parentId;
  final String isi;
  final String userName;
  final int userId;
  final DateTime createdAt;
  final List<CommentModel> replies;

  CommentModel({
    required this.id,
    required this.newsId,
    this.parentId,
    required this.isi,
    required this.userName,
    required this.userId,
    required this.createdAt,
    this.replies = const [],
  });

  factory CommentModel.fromJson(Map<String, dynamic> json) {
    DateTime parseDate(dynamic v) {
      if (v is String && v.isNotEmpty) {
        try { return DateTime.parse(v); } catch (_) {}
      }
      return DateTime.now();
    }
    return CommentModel(
      id: _toInt(json['id']),
      newsId: _toInt(json['news_id']),
      parentId: json['parent_id'] != null ? _toInt(json['parent_id']) : null,
      isi: json['isi']?.toString() ?? '',
      userName: json['user']?['name']?.toString() ?? 'User',
      userId: _toInt(json['user_id']),
      createdAt: parseDate(json['created_at']),
      replies: (json['replies'] as List?)?.map((e) => CommentModel.fromJson(e as Map<String, dynamic>)).toList() ?? [],
    );
  }
}
