import 'package:flutter/foundation.dart';
import '../models/news_model.dart';
import '../services/api_service.dart';

class NewsProvider extends ChangeNotifier {
  final _api = ApiService();
  List<NewsModel> _latestNews = [];
  bool _isLoading = false;

  List<NewsModel> get latestNews => _latestNews;
  bool get isLoading => _isLoading;

  Future<void> fetchLatest() async {
    _isLoading = true;
    notifyListeners();
    try {
      final res = await _api.get('/news/latest');
      final List data = res.data['data'] ?? [];
      _latestNews = data.map((e) => NewsModel.fromJson(e)).toList();
    } catch (e) {
      debugPrint('Fetch latest news error: $e');
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Toggle like — update lokal langsung agar UI responsif
  Future<bool> toggleLike(NewsModel news) async {
    final originalLiked = news.isLiked;
    final originalCount = news.likesCount;

    // Optimistic update
    news.isLiked = !originalLiked;
    news.likesCount += news.isLiked ? 1 : -1;
    notifyListeners();

    try {
      final res = await _api.post('/news/${news.id}/like');
      news.isLiked = res.data['liked'] ?? false;
      news.likesCount = res.data['likes_count'] ?? news.likesCount;
      notifyListeners();
      return true;
    } catch (e) {
      // Rollback
      news.isLiked = originalLiked;
      news.likesCount = originalCount;
      notifyListeners();
      return false;
    }
  }

  /// Fetch comments untuk news tertentu
  Future<List<CommentModel>> fetchComments(int newsId) async {
    try {
      final res = await _api.get('/news/$newsId/comments');
      final List data = res.data['data'] ?? [];
      return data.map((e) => CommentModel.fromJson(e)).toList();
    } catch (e) {
      debugPrint('Fetch comments error: $e');
      return [];
    }
  }

  /// Post komentar baru
  Future<CommentModel?> postComment(int newsId, String isi, {int? parentId}) async {
    try {
      final res = await _api.post('/news/$newsId/comments', data: {
        'isi': isi,
        if (parentId != null) 'parent_id': parentId,
      });
      // Update comment count
      final news = _latestNews.firstWhere((n) => n.id == newsId, orElse: () => _latestNews.first);
      news.commentsCount += 1;
      notifyListeners();
      return CommentModel.fromJson(res.data['comment']);
    } catch (e) {
      debugPrint('Post comment error: $e');
      return null;
    }
  }

  /// Get share text from backend
  Future<String?> getShareText(int newsId) async {
    try {
      final res = await _api.post('/news/$newsId/share');
      return res.data['share_text'];
    } catch (e) {
      return null;
    }
  }
}
