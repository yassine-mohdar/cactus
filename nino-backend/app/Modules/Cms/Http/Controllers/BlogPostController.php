<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Enums\PostStatus;
use App\Modules\Cms\Models\BlogCategory;
use App\Modules\Cms\Models\BlogPost;
use App\Modules\Cms\Models\BlogTag;
use App\Modules\Settings\Services\AdminMediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function __construct(
        private readonly AdminMediaLibraryService $mediaLibrary,
    ) {}

    public function index(Request $request)
    {
        $query = BlogPost::with(['author', 'category', 'tags']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$s}%")->orWhere('slug', 'like', "%{$s}%"));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('blog_category_id', $request->category);
        }

        $posts = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = [
            'total' => BlogPost::count(),
            'published' => BlogPost::published()->count(),
            'draft' => BlogPost::draft()->count(),
            'scheduled' => BlogPost::scheduled()->count(),
            'views' => BlogPost::sum('views_count'),
            'seo_ready' => BlogPost::query()
                ->where(function ($query) {
                    $query->whereNotNull('meta_title')
                        ->orWhereNotNull('meta_description');
                })
                ->count(),
        ];

        $categories = BlogCategory::orderBy('name')->get();

        return view('admin.cms.posts.index', compact('posts', 'stats', 'categories'));
    }

    public function create()
    {
        $categories = BlogCategory::active()->orderBy('name')->get();
        $tags = BlogTag::orderBy('name')->get();
        $mediaOptions = $this->mediaLibrary->imageOptions(['blog', 'categories', 'products']);
        return view('admin.cms.posts.form', compact('categories', 'tags', 'mediaOptions'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePost($request);
        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title']);
        $validated['author_id'] = auth()->id();

        if ($validated['status'] === PostStatus::PUBLISHED->value && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $post = BlogPost::create($validated);

        // Sync tags
        if ($request->filled('tag_ids')) {
            $post->tags()->sync($request->input('tag_ids'));
        }

        return redirect()->route('admin.cms.posts.index')->with('success', 'Post created.');
    }

    public function edit(BlogPost $post)
    {
        $post->load('tags');
        $categories = BlogCategory::active()->orderBy('name')->get();
        $tags = BlogTag::orderBy('name')->get();
        $mediaOptions = $this->mediaLibrary->imageOptions(['blog', 'categories', 'products']);
        return view('admin.cms.posts.form', compact('post', 'categories', 'tags', 'mediaOptions'));
    }

    public function update(Request $request, BlogPost $post)
    {
        $validated = $this->validatePost($request, $post->id);

        // Auto-publish if status changed to published
        if ($validated['status'] === PostStatus::PUBLISHED->value && $post->status !== PostStatus::PUBLISHED) {
            $validated['published_at'] = now();
        }

        $post->update($validated);

        if ($request->has('tag_ids')) {
            $post->tags()->sync($request->input('tag_ids', []));
        }

        return redirect()->route('admin.cms.posts.index')->with('success', 'Post updated.');
    }

    public function destroy(BlogPost $post)
    {
        $post->delete();
        return redirect()->route('admin.cms.posts.index')->with('success', 'Post deleted.');
    }

    /**
     * Preview a draft post.
     */
    public function preview(BlogPost $post)
    {
        return view('admin.cms.posts.preview', compact('post'));
    }

    /**
     * Toggle publish/unpublish.
     */
    public function togglePublish(BlogPost $post)
    {
        if ($post->status === PostStatus::PUBLISHED) {
            $post->unpublish();
            return back()->with('success', 'Post unpublished.');
        }

        $post->publish();
        return back()->with('success', 'Post published.');
    }

    private function validatePost(Request $request, ?int $postId = null): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:blog_posts,slug,' . $postId,
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'featured_image' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,scheduled,archived',
            'scheduled_at' => 'nullable|date|after:now',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:300',
            'canonical_url' => 'nullable|url',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string',
            'og_image' => 'nullable|string|max:255',
            'noindex' => 'nullable|boolean',
        ]);
    }
}
