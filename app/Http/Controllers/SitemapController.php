<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Doc;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [
            ['loc' => url('/'), 'priority' => '1.0'],
            ['loc' => url('/pricing'), 'priority' => '0.9'],
            ['loc' => url('/features'), 'priority' => '0.8'],
            ['loc' => url('/contact'), 'priority' => '0.6'],
            ['loc' => url('/privacy'), 'priority' => '0.3'],
            ['loc' => url('/terms'), 'priority' => '0.3'],
            ['loc' => url('/docs'), 'priority' => '0.7'],
            ['loc' => url('/blog'), 'priority' => '0.7'],
            ['loc' => url('/changelog'), 'priority' => '0.5'],
        ];

        foreach (Doc::published()->get() as $doc) {
            $urls[] = ['loc' => url('/docs/'.$doc->slug), 'priority' => '0.6'];
        }

        foreach (BlogPost::published()->latest()->get() as $post) {
            $urls[] = ['loc' => url('/blog/'.$post->slug), 'priority' => '0.6'];
        }

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'text/xml');
    }
}
