<?php

namespace App\Http\Controllers;

use App\Models\Doc;
use Inertia\Inertia;
use Inertia\Response;

class DocController extends Controller
{
    public function index(): Response
    {
        $categories = Doc::published()->sorted()->get()->groupBy('category');

        return Inertia::render('public/docs/index', [
            'categories' => $categories,
        ]);
    }

    public function show(Doc $doc): Response
    {
        return Inertia::render('public/docs/show', [
            'doc' => $doc,
        ]);
    }
}
