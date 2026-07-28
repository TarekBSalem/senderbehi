<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Template::where('is_active', true);

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $templates = $query->orderBy('name')->get();

        return Inertia::render('templates/index', [
            'templates' => $templates,
            'categories' => Template::where('is_active', true)
                ->distinct()
                ->pluck('category'),
        ]);
    }

    public function show(string $slug): Response
    {
        $template = Template::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return Inertia::render('templates/show', [
            'template' => $template,
        ]);
    }
}
