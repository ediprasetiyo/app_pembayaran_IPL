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
    return NewsModel(
      id: json['id'],
      judul: json['judul'] ?? '',
      slug: json['slug'] ?? '',
      ringkasan: json['ringkasan'],
      konten: json['konten'] ?? '',
      gambar: json['gambar'],
      gambarUrl: json['gambar_url'],
      kategori: json['kategori'] ?? 'informasi',
      isPinned: json['is_pinned'] == true || json['is_pinned'] == 1,
      publishedAt: json['published_at'] != null ? DateTime.parse(json['published_at']) : null,
      penulis: json['pembuat']?['name'],
      likesCount: json['likes_count'] ?? 0,
      commentsCount: json['comments_count'] ?? 0,
      isLiked: json['is_liked'] == true || json['is_liked'] == 1,
      viewCount: json['view_count'] ?? 0,
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
    return CommentModel(
      id: json['id'],
      newsId: json['news_id'],
      parentId: json['parent_id'],
      isi: json['isi'] ?? '',
      userName: json['user']?['name'] ?? 'User',
      userId: json['user_id'],
      createdAt: DateTime.parse(json['created_at']),
      replies: (json['replies'] as List?)?.map((e) => CommentModel.fromJson(e)).toList() ?? [],
    );
  }
}
