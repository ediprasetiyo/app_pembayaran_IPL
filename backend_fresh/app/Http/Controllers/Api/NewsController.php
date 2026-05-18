<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\NewsComment;
use App\Models\NewsLike;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = News::with('pembuat:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        // Warga hanya lihat yang sudah dipublikasi; admin/super_admin lihat semua
        if (!in_array($user->role, ['admin', 'super_admin'])) {
            $query->published();
        }

        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('judul', 'like', "%{$request->search}%")
                  ->orWhere('ringkasan', 'like', "%{$request->search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    public function latest(Request $request): JsonResponse
    {
        $news = News::with('pembuat:id,name')
            ->published()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->limit(10)
            ->get();

        return response()->json(['data' => $news]);
    }

    public function show(Request $request, News $news): JsonResponse
    {
        // Increment view count
        $news->increment('view_count');

        return response()->json([
            'news' => $news->load(['pembuat:id,name', 'comments'])
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'judul' => 'required|string|max:200',
            'ringkasan' => 'nullable|string|max:500',
            'konten' => 'required|string',
            'kategori' => 'required|in:pengumuman,kegiatan,informasi,darurat',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'is_published' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
        ]);

        $data = $request->only(['judul', 'ringkasan', 'konten', 'kategori']);
        $data['is_published'] = $request->boolean('is_published', true);
        $data['is_pinned'] = $request->boolean('is_pinned', false);
        $data['dibuat_oleh'] = $request->user()->id;

        if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
            try {
                $cloudinary = app(CloudinaryService::class);
                $url = null;
                if ($cloudinary->isConfigured()) {
                    $url = $cloudinary->upload($request->file('gambar'), 'ipl/news');
                }
                if (!$url) {
                    $path = $request->file('gambar')->store('news', 'public');
                    if (!empty($path)) {
                        $url = Storage::url($path);
                    }
                }
                if ($url) $data['gambar'] = $url;
            } catch (\Throwable $e) {
                \Log::warning('News image upload failed: ' . $e->getMessage());
            }
        }

        $news = News::create($data);

        return response()->json([
            'message' => 'Berita berhasil dibuat.',
            'news' => $news->load('pembuat:id,name'),
        ], 201);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $request->validate([
            'judul' => 'sometimes|string|max:200',
            'ringkasan' => 'nullable|string|max:500',
            'konten' => 'sometimes|string',
            'kategori' => 'sometimes|in:pengumuman,kegiatan,informasi,darurat',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'is_published' => 'sometimes|boolean',
            'is_pinned' => 'sometimes|boolean',
        ]);

        $data = $request->only(['judul', 'ringkasan', 'konten', 'kategori']);

        if ($request->has('is_published')) {
            $data['is_published'] = $request->boolean('is_published');
            if ($data['is_published'] && !$news->published_at) {
                $data['published_at'] = now();
            }
        }
        if ($request->has('is_pinned')) {
            $data['is_pinned'] = $request->boolean('is_pinned');
        }

        if ($request->hasFile('gambar') && $request->file('gambar')->isValid()) {
            try {
                $cloudinary = app(CloudinaryService::class);
                // Hapus gambar lama
                if ($news->gambar) {
                    if (str_contains($news->gambar, 'cloudinary.com') && $cloudinary->isConfigured()) {
                        $cloudinary->deleteByUrl($news->gambar);
                    } else {
                        $oldPath = str_replace('/storage/', '', parse_url($news->gambar, PHP_URL_PATH) ?? '');
                        if (!empty($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                }
                $url = null;
                if ($cloudinary->isConfigured()) {
                    $url = $cloudinary->upload($request->file('gambar'), 'ipl/news');
                }
                if (!$url) {
                    $path = $request->file('gambar')->store('news', 'public');
                    if (!empty($path)) {
                        $url = Storage::url($path);
                    }
                }
                if ($url) $data['gambar'] = $url;
            } catch (\Throwable $e) {
                \Log::warning('News image upload failed: ' . $e->getMessage());
            }
        }

        $news->update($data);

        return response()->json([
            'message' => 'Berita berhasil diperbarui.',
            'news' => $news->fresh()->load('pembuat:id,name'),
        ]);
    }

    public function destroy(News $news): JsonResponse
    {
        if ($news->gambar) {
            $oldPath = str_replace('/storage/', '', parse_url($news->gambar, PHP_URL_PATH) ?? '');
            if (!empty($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        $news->delete();

        return response()->json(['message' => 'Berita berhasil dihapus.']);
    }

    // ============ LIKE ============
    public function toggleLike(Request $request, News $news): JsonResponse
    {
        $existing = NewsLike::where('news_id', $news->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            NewsLike::create([
                'news_id' => $news->id,
                'user_id' => $request->user()->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $news->likes()->count(),
        ]);
    }

    // ============ COMMENTS ============
    public function comments(News $news): JsonResponse
    {
        $comments = NewsComment::where('news_id', $news->id)
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $comments,
            'total' => NewsComment::where('news_id', $news->id)->count(),
        ]);
    }

    public function storeComment(Request $request, News $news): JsonResponse
    {
        $request->validate([
            'isi' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:news_comments,id',
        ]);

        $comment = NewsComment::create([
            'news_id' => $news->id,
            'user_id' => $request->user()->id,
            'parent_id' => $request->parent_id,
            'isi' => $request->isi,
        ]);

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan.',
            'comment' => $comment->load('user:id,name'),
        ], 201);
    }

    public function deleteComment(Request $request, NewsComment $comment): JsonResponse
    {
        // Hanya pemilik atau admin yang bisa hapus
        if ($comment->user_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin'])) {
            return response()->json(['message' => 'Tidak diizinkan.'], 403);
        }

        $comment->delete();
        return response()->json(['message' => 'Komentar dihapus.']);
    }

    // ============ SHARE ============
    public function share(News $news): JsonResponse
    {
        $news->increment('share_count');

        return response()->json([
            'share_count' => $news->fresh()->share_count,
            'share_text' => "📰 *{$news->judul}*\n\n" . ($news->ringkasan ?? mb_substr(strip_tags($news->konten), 0, 200) . '...') . "\n\n— Berita Griya Pesona Madani",
        ]);
    }
}
