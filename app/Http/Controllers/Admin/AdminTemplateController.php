<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Template;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Template::with('category');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->has('category') && $request->category !== '') {
            $query->where('category_id', $request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $templates = $query->latest()
            ->paginate(15)
            ->through(fn (Template $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'subject' => $t->subject,
                'category_id' => $t->category_id,
                'category_name' => $t->category?->name,
                'is_active' => $t->is_active,
                'created_at' => $t->created_at,
            ]);

        return Inertia::render('admin/templates/index', [
            'templates' => $templates,
            'categories' => Category::sorted()->get(['id', 'name']),
            'filters' => $request->only(['search', 'category', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/templates/create', [
            'categories' => Category::sorted()->get(['id', 'name']),
            'templates' => Template::orderBy('name')->get([
                'id',
                'name',
                'slug',
                'subject',
                'body_html',
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:templates,slug',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (! isset($validated['variables'])) {
            preg_match_all('/\{\{(\w+)\}\}/', $validated['body_html'], $matches);

            $validated['variables'] = array_values(array_unique($matches[1]));
        }

        Template::create($validated);

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template created successfully.');
    }

    public function edit(Template $template): Response
    {
        $template->load('category');

        return Inertia::render('admin/templates/edit', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'subject' => $template->subject,
                'body_html' => $template->body_html,
                'category_id' => $template->category_id,
                'thumbnail' => $template->thumbnail,
                'variables' => $template->variables,
                'is_active' => $template->is_active,
                'created_at' => $template->created_at,
            ],
            'categories' => Category::sorted()->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Template $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:templates,slug,'.$template->id,
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'variables' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (! isset($validated['variables'])) {
            preg_match_all('/\{\{(\w+)\}\}/', $validated['body_html'], $matches);

            $validated['variables'] = array_values(array_unique($matches[1]));
        }

        $template->update($validated);

        return redirect()->route('admin.templates.edit', $template)
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(Template $template): RedirectResponse
    {
        $template->delete();

        return redirect()->route('admin.templates.index')
            ->with('success', 'Template deleted.');
    }
}
