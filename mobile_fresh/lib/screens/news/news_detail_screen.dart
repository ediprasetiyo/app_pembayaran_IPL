import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../models/news_model.dart';
import '../../providers/news_provider.dart';
import '../../utils/app_theme.dart';

class NewsDetailScreen extends StatefulWidget {
  final NewsModel news;
  const NewsDetailScreen({super.key, required this.news});

  @override
  State<NewsDetailScreen> createState() => _NewsDetailScreenState();
}

class _NewsDetailScreenState extends State<NewsDetailScreen> {
  final _commentCtrl = TextEditingController();
  List<CommentModel> _comments = [];
  bool _isLoadingComments = false;
  bool _isPosting = false;
  CommentModel? _replyTo;

  @override
  void initState() {
    super.initState();
    _loadComments();
  }

  @override
  void dispose() {
    _commentCtrl.dispose();
    super.dispose();
  }

  Future<void> _loadComments() async {
    setState(() => _isLoadingComments = true);
    final result = await context.read<NewsProvider>().fetchComments(widget.news.id);
    if (!mounted) return;
    setState(() {
      _comments = result;
      _isLoadingComments = false;
    });
  }

  Future<void> _postComment() async {
    if (_commentCtrl.text.trim().isEmpty) return;
    setState(() => _isPosting = true);
    final comment = await context.read<NewsProvider>().postComment(
      widget.news.id,
      _commentCtrl.text.trim(),
      parentId: _replyTo?.id,
    );
    if (!mounted) return;
    setState(() {
      _isPosting = false;
      if (comment != null) {
        _commentCtrl.clear();
        _replyTo = null;
        _loadComments();
      }
    });
  }

  Future<void> _toggleLike() async {
    await context.read<NewsProvider>().toggleLike(widget.news);
    if (mounted) setState(() {});
  }

  Future<void> _shareNews() async {
    final shareText = await context.read<NewsProvider>().getShareText(widget.news.id);
    if (shareText != null) {
      await Share.share(shareText);
    } else {
      await Share.share('${widget.news.judul}\n\n${widget.news.ringkasan ?? widget.news.konten}');
    }
  }

  Color _kategoriColor(String kategori) {
    switch (kategori) {
      case 'pengumuman': return Colors.blue;
      case 'kegiatan': return Colors.green;
      case 'darurat': return Colors.red;
      default: return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final news = widget.news;

    return Scaffold(
      backgroundColor: Colors.white,
      body: SafeArea(
        child: Column(
          children: [
            Expanded(
              child: CustomScrollView(
                slivers: [
                  // ===== HERO IMAGE =====
                  SliverAppBar(
                    expandedHeight: 250,
                    pinned: true,
                    leading: IconButton(
                      icon: Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.35),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(Icons.arrow_back, color: Colors.white, size: 18),
                      ),
                      onPressed: () => Navigator.pop(context),
                    ),
                    actions: [
                      IconButton(
                        icon: Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.35),
                            shape: BoxShape.circle,
                          ),
                          child: const Icon(Icons.share, color: Colors.white, size: 18),
                        ),
                        onPressed: _shareNews,
                      ),
                      const SizedBox(width: 8),
                    ],
                    flexibleSpace: FlexibleSpaceBar(
                      background: news.gambarUrl != null
                          ? GestureDetector(
                              onTap: () => _showFullImage(news.gambarUrl!),
                              child: Stack(
                                fit: StackFit.expand,
                                children: [
                                  CachedNetworkImage(
                                    imageUrl: news.gambarUrl!,
                                    fit: BoxFit.cover,
                                    placeholder: (_, __) => Container(
                                      color: AppTheme.primaryColor.withOpacity(0.1),
                                      alignment: Alignment.center,
                                      child: const CircularProgressIndicator(strokeWidth: 2),
                                    ),
                                    errorWidget: (_, __, ___) => _placeholderImage(news),
                                  ),
                                  // Hint icon di pojok kanan bawah agar user tahu bisa diklik
                                  Positioned(
                                    right: 12,
                                    bottom: 12,
                                    child: Container(
                                      padding: const EdgeInsets.all(8),
                                      decoration: BoxDecoration(
                                        color: Colors.black.withOpacity(0.4),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(
                                        Icons.zoom_out_map,
                                        color: Colors.white,
                                        size: 18,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            )
                          : _placeholderImage(news),
                    ),
                  ),

                  // ===== HEADER INFO =====
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                decoration: BoxDecoration(
                                  color: _kategoriColor(news.kategori).withOpacity(0.12),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '${news.kategoriEmoji} ${news.kategoriLabel}',
                                  style: TextStyle(
                                    fontSize: 11,
                                    color: _kategoriColor(news.kategori),
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                              ),
                              if (news.isPinned) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                                    color: Colors.amber.shade100,
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Text('📌 Pinned',
                                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.w600)),
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 12),
                          Text(news.judul,
                              style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, height: 1.3)),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              const Icon(Icons.person_outline, size: 14, color: Color(0xFF9E9E9E)),
                              const SizedBox(width: 4),
                              Text(news.penulis ?? 'Admin',
                                  style: const TextStyle(fontSize: 12, color: Color(0xFF9E9E9E))),
                              const SizedBox(width: 12),
                              const Icon(Icons.access_time, size: 14, color: Color(0xFF9E9E9E)),
                              const SizedBox(width: 4),
                              Text(
                                news.publishedAt != null
                                    ? DateFormat('d MMM yyyy, HH:mm', 'id_ID').format(news.publishedAt!)
                                    : '-',
                                style: const TextStyle(fontSize: 12, color: Color(0xFF9E9E9E)),
                              ),
                            ],
                          ),
                          const Divider(height: 32),
                          if (news.ringkasan != null && news.ringkasan!.isNotEmpty) ...[
                            Container(
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF5F7FA),
                                borderRadius: BorderRadius.circular(10),
                                border: Border(left: BorderSide(color: AppTheme.primaryColor, width: 3)),
                              ),
                              child: Text(
                                news.ringkasan!,
                                style: const TextStyle(fontSize: 13, fontStyle: FontStyle.italic, color: Color(0xFF424242)),
                              ),
                            ),
                            const SizedBox(height: 16),
                          ],
                          // Render konten sebagai HTML
                          Html(
                            data: news.konten,
                            style: {
                              'body': Style(
                                fontSize: FontSize(14),
                                lineHeight: const LineHeight(1.6),
                                color: const Color(0xFF212121),
                                margin: Margins.zero,
                                padding: HtmlPaddings.zero,
                              ),
                              'p': Style(
                                margin: Margins.only(bottom: 12),
                              ),
                              'h1': Style(
                                fontSize: FontSize(22),
                                fontWeight: FontWeight.bold,
                                margin: Margins.only(top: 16, bottom: 8),
                              ),
                              'h2': Style(
                                fontSize: FontSize(19),
                                fontWeight: FontWeight.bold,
                                margin: Margins.only(top: 14, bottom: 8),
                              ),
                              'h3': Style(
                                fontSize: FontSize(16),
                                fontWeight: FontWeight.bold,
                                margin: Margins.only(top: 12, bottom: 6),
                              ),
                              'a': Style(
                                color: AppTheme.primaryColor,
                                textDecoration: TextDecoration.underline,
                                fontWeight: FontWeight.w600,
                              ),
                              'strong': Style(fontWeight: FontWeight.bold),
                              'em': Style(fontStyle: FontStyle.italic),
                              'blockquote': Style(
                                backgroundColor: const Color(0xFFF5F7FA),
                                padding: HtmlPaddings.all(12),
                                margin: Margins.symmetric(vertical: 8),
                                border: Border(
                                  left: BorderSide(color: AppTheme.primaryColor, width: 3),
                                ),
                                fontStyle: FontStyle.italic,
                              ),
                              'ul, ol': Style(
                                margin: Margins.only(bottom: 12, left: 8),
                              ),
                              'li': Style(
                                margin: Margins.only(bottom: 4),
                              ),
                            },
                            onLinkTap: (url, _, __) async {
                              if (url == null) return;
                              final uri = Uri.parse(url);
                              if (await canLaunchUrl(uri)) {
                                await launchUrl(uri, mode: LaunchMode.externalApplication);
                              }
                            },
                          ),
                          const SizedBox(height: 24),

                          // Reaction Bar
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF5F7FA),
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: Row(
                              children: [
                                Expanded(
                                  child: InkWell(
                                    onTap: _toggleLike,
                                    borderRadius: BorderRadius.circular(8),
                                    child: Padding(
                                      padding: const EdgeInsets.symmetric(vertical: 6),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(
                                            news.isLiked ? Icons.favorite : Icons.favorite_border,
                                            color: news.isLiked ? Colors.red : Colors.grey,
                                            size: 20,
                                          ),
                                          const SizedBox(width: 6),
                                          Text(
                                            '${news.likesCount}',
                                            style: TextStyle(
                                              color: news.isLiked ? Colors.red : const Color(0xFF424242),
                                              fontWeight: FontWeight.w600,
                                              fontSize: 13,
                                            ),
                                          ),
                                          const SizedBox(width: 4),
                                          const Text('Suka', style: TextStyle(fontSize: 12, color: Color(0xFF757575))),
                                        ],
                                      ),
                                    ),
                                  ),
                                ),
                                Container(width: 1, height: 22, color: const Color(0xFFE0E0E0)),
                                Expanded(
                                  child: Padding(
                                    padding: const EdgeInsets.symmetric(vertical: 6),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        const Icon(Icons.chat_bubble_outline, color: Colors.grey, size: 18),
                                        const SizedBox(width: 6),
                                        Text(
                                          '${news.commentsCount}',
                                          style: const TextStyle(
                                            color: Color(0xFF424242),
                                            fontWeight: FontWeight.w600,
                                            fontSize: 13,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        const Text('Komentar',
                                            style: TextStyle(fontSize: 12, color: Color(0xFF757575))),
                                      ],
                                    ),
                                  ),
                                ),
                                Container(width: 1, height: 22, color: const Color(0xFFE0E0E0)),
                                Expanded(
                                  child: InkWell(
                                    onTap: _shareNews,
                                    borderRadius: BorderRadius.circular(8),
                                    child: const Padding(
                                      padding: EdgeInsets.symmetric(vertical: 6),
                                      child: Row(
                                        mainAxisAlignment: MainAxisAlignment.center,
                                        children: [
                                          Icon(Icons.share_outlined, color: Colors.grey, size: 18),
                                          SizedBox(width: 6),
                                          Text('Bagikan',
                                              style: TextStyle(fontSize: 12, color: Color(0xFF757575))),
                                        ],
                                      ),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 20),
                          const Divider(height: 1),
                          const SizedBox(height: 16),

                          // ===== KOMENTAR =====
                          Row(
                            children: [
                              const Icon(Icons.chat_bubble_outline, size: 20),
                              const SizedBox(width: 6),
                              Text(
                                'Komentar (${news.commentsCount})',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),
                          if (_isLoadingComments)
                            const Padding(
                              padding: EdgeInsets.symmetric(vertical: 20),
                              child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                            )
                          else if (_comments.isEmpty)
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: 24),
                              child: Column(
                                children: [
                                  Icon(Icons.mode_comment_outlined, size: 36, color: Colors.grey.shade300),
                                  const SizedBox(height: 6),
                                  const Text('Belum ada komentar',
                                      style: TextStyle(color: Color(0xFF9E9E9E), fontSize: 12)),
                                  const Text('Jadilah yang pertama berkomentar!',
                                      style: TextStyle(color: Color(0xFFBDBDBD), fontSize: 11)),
                                ],
                              ),
                            )
                          else
                            ..._comments.map(_commentTile),
                          const SizedBox(height: 80),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // ===== INPUT KOMENTAR =====
            Container(
              decoration: BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: Colors.grey.shade200)),
              ),
              padding: EdgeInsets.only(
                left: 12,
                right: 12,
                top: 8,
                bottom: MediaQuery.of(context).viewInsets.bottom + 10,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (_replyTo != null)
                    Container(
                      padding: const EdgeInsets.all(8),
                      margin: const EdgeInsets.only(bottom: 8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF5F7FA),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.reply, size: 14, color: Color(0xFF757575)),
                          const SizedBox(width: 6),
                          Text('Balas ${_replyTo!.userName}',
                              style: const TextStyle(fontSize: 11, color: Color(0xFF757575))),
                          const Spacer(),
                          GestureDetector(
                            onTap: () => setState(() => _replyTo = null),
                            child: const Icon(Icons.close, size: 14, color: Color(0xFF9E9E9E)),
                          ),
                        ],
                      ),
                    ),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _commentCtrl,
                          maxLines: 3,
                          minLines: 1,
                          decoration: InputDecoration(
                            hintText: _replyTo != null ? 'Tulis balasan...' : 'Tulis komentar...',
                            isDense: true,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(20),
                              borderSide: BorderSide(color: Colors.grey.shade300),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(20),
                              borderSide: BorderSide(color: Colors.grey.shade300),
                            ),
                            filled: true,
                            fillColor: Colors.white,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      CircleAvatar(
                        backgroundColor: AppTheme.primaryColor,
                        radius: 20,
                        child: _isPosting
                            ? const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                              )
                            : IconButton(
                                icon: const Icon(Icons.send, color: Colors.white, size: 18),
                                onPressed: _postComment,
                              ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showFullImage(String imageUrl) {
    Navigator.of(context).push(
      PageRouteBuilder(
        opaque: false,
        barrierColor: Colors.black.withOpacity(0.95),
        pageBuilder: (_, __, ___) => _FullScreenImageViewer(imageUrl: imageUrl),
      ),
    );
  }

  Widget _placeholderImage(NewsModel news) {
    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            _kategoriColor(news.kategori).withOpacity(0.7),
            _kategoriColor(news.kategori).withOpacity(0.3),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      alignment: Alignment.center,
      child: Text(news.kategoriEmoji, style: const TextStyle(fontSize: 80)),
    );
  }

  Widget _commentTile(CommentModel c) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              CircleAvatar(
                radius: 16,
                backgroundColor: AppTheme.primaryColor.withOpacity(0.15),
                child: Text(
                  c.userName.isNotEmpty ? c.userName[0].toUpperCase() : 'U',
                  style: TextStyle(color: AppTheme.primaryColor, fontWeight: FontWeight.bold, fontSize: 12),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF5F7FA),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(c.userName,
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 12)),
                          const SizedBox(height: 4),
                          Text(c.isi, style: const TextStyle(fontSize: 13, height: 1.4)),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.only(left: 6, top: 4),
                      child: Row(
                        children: [
                          Text(_relativeTime(c.createdAt),
                              style: const TextStyle(fontSize: 10, color: Color(0xFFBDBDBD))),
                          const SizedBox(width: 10),
                          GestureDetector(
                            onTap: () => setState(() => _replyTo = c),
                            child: const Text('Balas',
                                style: TextStyle(fontSize: 10, color: Color(0xFF757575), fontWeight: FontWeight.w600)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (c.replies.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(left: 42, top: 8),
              child: Column(
                children: c.replies.map((r) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      CircleAvatar(
                        radius: 12,
                        backgroundColor: AppTheme.primaryColor.withOpacity(0.15),
                        child: Text(
                          r.userName.isNotEmpty ? r.userName[0].toUpperCase() : 'U',
                          style: TextStyle(color: AppTheme.primaryColor, fontWeight: FontWeight.bold, fontSize: 10),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF5F7FA),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(r.userName,
                                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 11)),
                              const SizedBox(height: 2),
                              Text(r.isi, style: const TextStyle(fontSize: 12, height: 1.4)),
                              const SizedBox(height: 2),
                              Text(_relativeTime(r.createdAt),
                                  style: const TextStyle(fontSize: 9, color: Color(0xFFBDBDBD))),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                )).toList(),
              ),
            ),
        ],
      ),
    );
  }

  String _relativeTime(DateTime d) {
    final diff = DateTime.now().difference(d);
    if (diff.inMinutes < 1) return 'baru saja';
    if (diff.inHours < 1) return '${diff.inMinutes} mnt';
    if (diff.inDays < 1) return '${diff.inHours} jam';
    if (diff.inDays < 7) return '${diff.inDays} hari';
    return DateFormat('d MMM yyyy', 'id_ID').format(d);
  }
}

// ============== FULL SCREEN IMAGE VIEWER ==============
class _FullScreenImageViewer extends StatefulWidget {
  final String imageUrl;
  const _FullScreenImageViewer({required this.imageUrl});

  @override
  State<_FullScreenImageViewer> createState() => _FullScreenImageViewerState();
}

class _FullScreenImageViewerState extends State<_FullScreenImageViewer> {
  final _transformationController = TransformationController();
  double _dragOffset = 0;

  void _close() => Navigator.of(context).pop();

  @override
  void dispose() {
    _transformationController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: GestureDetector(
        onVerticalDragUpdate: (details) {
          setState(() => _dragOffset += details.delta.dy);
        },
        onVerticalDragEnd: (_) {
          if (_dragOffset.abs() > 100) {
            _close();
          } else {
            setState(() => _dragOffset = 0);
          }
        },
        child: Stack(
          children: [
            // Image (zoom & pan)
            Transform.translate(
              offset: Offset(0, _dragOffset),
              child: Container(
                color: Colors.black.withOpacity(
                  (1 - (_dragOffset.abs() / 400)).clamp(0.5, 1.0),
                ),
                child: Center(
                  child: InteractiveViewer(
                    transformationController: _transformationController,
                    minScale: 1.0,
                    maxScale: 5.0,
                    boundaryMargin: const EdgeInsets.all(20),
                    child: CachedNetworkImage(
                      imageUrl: widget.imageUrl,
                      fit: BoxFit.contain,
                      placeholder: (_, __) => const SizedBox(
                        width: 40,
                        height: 40,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor: AlwaysStoppedAnimation(Colors.white),
                        ),
                      ),
                      errorWidget: (_, __, ___) => const Icon(
                        Icons.broken_image,
                        color: Colors.white,
                        size: 60,
                      ),
                    ),
                  ),
                ),
              ),
            ),

            // Top bar: Close + zoom reset
            Positioned(
              top: MediaQuery.of(context).padding.top + 8,
              left: 8,
              right: 8,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Material(
                    color: Colors.black.withOpacity(0.4),
                    shape: const CircleBorder(),
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: _close,
                      child: const Padding(
                        padding: EdgeInsets.all(10),
                        child: Icon(Icons.close, color: Colors.white, size: 22),
                      ),
                    ),
                  ),
                  Material(
                    color: Colors.black.withOpacity(0.4),
                    shape: const CircleBorder(),
                    child: InkWell(
                      customBorder: const CircleBorder(),
                      onTap: () => _transformationController.value = Matrix4.identity(),
                      child: const Padding(
                        padding: EdgeInsets.all(10),
                        child: Icon(Icons.refresh, color: Colors.white, size: 22),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Bottom hint
            Positioned(
              bottom: MediaQuery.of(context).padding.bottom + 16,
              left: 0,
              right: 0,
              child: Center(
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.5),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'Cubit untuk zoom · Geser ke bawah untuk tutup',
                    style: TextStyle(color: Colors.white70, fontSize: 11),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
