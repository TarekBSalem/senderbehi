<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(): Response
    {
        $posts = BlogPost::published()->latest()->paginate(10);

        return Inertia::render('public/blog/index', [
            'posts' => $posts,
        ]);
    }

    public function show(BlogPost $blogPost): Response
    {
        return Inertia::render('public/blog/show', [
            'post' => $blogPost,
        ]);
    }
}
