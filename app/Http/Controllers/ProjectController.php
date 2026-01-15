<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = Project::with('user:id,name');

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        // Filter by platform
        if ($request->has('platform') && $request->platform) {
            $query->where('platform', $request->platform);
        }

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $projects = $query->latest()
            ->paginate(10)
            ->withQueryString();

        // Get unique platforms and categories for filters
        $platforms = Project::distinct()->pluck('platform')->toArray();
        $categories = Project::distinct()->pluck('category')->toArray();

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
            'filters' => $request->only(['search', 'platform', 'category', 'status']),
            'platforms' => $platforms,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Projects/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'description' => 'nullable|string',
        ]);

        $validated['user_id'] = Auth::id();

        Project::create($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        $project->load('user:id,name');
        
        return Inertia::render('Projects/Show', [
            'project' => $project,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project): Response
    {
        return Inertia::render('Projects/Edit', [
            'project' => $project,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'platform' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive,maintenance',
            'description' => 'nullable|string',
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    /**
     * Display a listing of project domains grouped by platform and category.
     */
    public function domainList(Request $request): Response
    {
        $projects = Project::whereNotNull('domain')
            ->where('domain', '!=', '')
            ->get();

        $groupedProjects = $projects->groupBy('platform')->map(function ($platformItems) {
            return $platformItems->groupBy('category');
        });

        return Inertia::render('Projects/DomainList', [
            'groupedProjects' => $groupedProjects,
        ]);
    }
}
