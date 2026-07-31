<?php

namespace App\Http\Controllers;

use App\Models\ChangelogEntry;
use Inertia\Inertia;
use Inertia\Response;

class ChangelogController extends Controller
{
    public function index(): Response
    {
        $entries = ChangelogEntry::published()->latest()->get();

        return Inertia::render('public/changelog', [
            'entries' => $entries,
        ]);
    }
}
