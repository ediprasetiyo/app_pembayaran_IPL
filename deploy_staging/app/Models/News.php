<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $table = 'news';

    protected $fillable = [
        'judul',
        'slug',
        'ringkasan',
        'konten',
        'gambar',
        'kategori',
        'is_published',
        'is_pinned',
        'dibuat_oleh',
        'published_at',
        'view_count',
        'share_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = ['gambar_url', 'likes_count', 'comments_count', 'is_liked'];

    protected static function booted(): void
    {
        static::creating(function ($news) {
            if (empty($news->slug)) {
                $base = Str::slug($news->judul);
                $slug = $base;
                $i = 1;
                while (self::where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $news->slug = $slug;
            }
            if ($news->is_published && empty($news->published_at)) {
                $news->published_at = now();
            }
        });
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function likes()
    {
        return $this->hasMany(NewsLike::class);
    }

    public function comments()
    {
        return $this->hasMany(NewsComment::class)
            ->whereNull('parent_id')
            ->with(['user:id,name', 'replies.user:id,name'])
            ->orderByDesc('created_at');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /**
     * Full URL utk gambar (untuk mobile access via WiFi LAN)
     */
    public function getGambarUrlAttribute(): ?string
    {
        if (empty($this->gambar)) return null;
        // Kalau sudah full URL, return apa adanya
        if (str_starts_with($this->gambar, 'http')) return $this->gambar;
        // Kalau path relatif (/storage/..), prepend dengan request scheme & host
        return URL::to($this->gambar);
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }

    public function getCommentsCountAttribute(): int
    {
        return NewsComment::where('news_id', $this->id)->count();
    }

    public function getIsLikedAttribute(): bool
    {
        if (auth()->guest()) return false;
        return $this->likes()->where('user_id', auth()->id())->exists();
    }
}
