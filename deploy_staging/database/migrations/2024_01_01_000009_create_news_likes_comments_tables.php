<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['news_id', 'user_id']);
        });

        Schema::create('news_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('news_comments')->nullOnDelete();
            $table->text('isi');
            $table->timestamps();

            $table->index(['news_id', 'created_at']);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->unsignedInteger('view_count')->default(0)->after('is_pinned');
            $table->unsignedInteger('share_count')->default(0)->after('view_count');
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->dropColumn(['view_count', 'share_count']);
        });
        Schema::dropIfExists('news_comments');
        Schema::dropIfExists('news_likes');
    }
};
