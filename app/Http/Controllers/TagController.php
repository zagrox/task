<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * Display a listing of the tags
     */
    public function index()
    {
        $tags = Tag::all();
        return view('tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new tag
     */
    public function create()
    {
        return view('tags.create');
    }

    /**
     * Store a newly created tag in the database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags',
            'color' => 'required|string|max:7',
            'description' => 'nullable|string|max:255',
            'is_repository' => 'nullable|boolean',
            'repository_url' => 'nullable|string|max:255', 
        ]);

        $name = $request->name;
        // Add repo: prefix if this is a repository tag
        if ($request->is_repository) {
            $name = 'repo:' . $name;
        }

        Tag::create([
            'name' => $name,
            'color' => $request->color,
            'description' => $request->description,
        ]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully');
    }

    /**
     * Display the specified tag
     */
    public function show(Tag $tag)
    {
        return view('tags.show', compact('tag'));
    }

    /**
     * Show the form for editing the specified tag
     */
    public function edit(Tag $tag)
    {
        return view('tags.edit', compact('tag'));
    }

    /**
     * Update the specified tag in the database
     */
    public function update(Request $request, Tag $tag)
    {
        $unique_rule = 'required|string|max:50|unique:tags,name,' . $tag->id;
        
        $request->validate([
            'name' => $unique_rule,
            'color' => 'required|string|max:7',
            'description' => 'nullable|string|max:255',
            'is_repository' => 'nullable|boolean',
            'repository_url' => 'nullable|string|max:255',
        ]);

        $name = $request->name;
        // Check if this was previously a repository tag or is becoming one
        $isRepoTag = strpos($tag->name, 'repo:') === 0;
        
        if ($request->is_repository && !$isRepoTag) {
            // Add repo: prefix if becoming a repository tag
            $name = 'repo:' . $name;
        } else if (!$request->is_repository && $isRepoTag) {
            // Remove repo: prefix if no longer a repository tag
            $name = $request->name;
        } else if ($isRepoTag && $request->is_repository) {
            // Keep repo: prefix but use new name
            $name = 'repo:' . $name;
        }

        $tag->update([
            'name' => $name,
            'color' => $request->color,
            'description' => $request->description,
        ]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully');
    }

    /**
     * Remove the specified tag from the database
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('tags.index')
            ->with('success', 'Tag deleted successfully');
    }
} 