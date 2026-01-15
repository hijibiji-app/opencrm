<?php

namespace App\Http\Controllers;

use App\Models\Reel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reels = Reel::with('user:id,name')
            ->latest()
            ->paginate(10);

        return Inertia::render('Reels/Index', [
            'reels' => $reels,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Reels/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:video,image,text',
            'caption' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'file' => [
                'nullable',
                'file',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->input('type') === 'video') {
                        if ($value->getSize() > 50 * 1024 * 1024) {
                            $fail('The video is too large. Max size 50MB.');
                        }
                    } elseif ($request->input('type') === 'image') {
                        if (!in_array($value->getClientOriginalExtension(), ['jpg', 'jpeg', 'png', 'webp'])) {
                            $fail('Only images (jpg, jpeg, png, webp) are allowed.');
                        }
                    }
                },
            ],
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $folder = $request->input('type') === 'video' ? 'reels/videos' : 'reels/images';
            $filePath = $request->file('file')->store($folder, 'public');
        }

        Reel::create([
            'user_id' => Auth::id(),
            'type' => $request->input('type'),
            'caption' => $request->input('caption'),
            'content' => $request->input('content'),
            'file_path' => $filePath ? '/storage/' . $filePath : null,
        ]);

        return redirect()->route('reels.index')->with('success', 'Reel posted successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reel $reel)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($reel->user_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        if ($reel->file_path) {
            $internalPath = str_replace('/storage/', '', $reel->file_path);
            Storage::disk('public')->delete($internalPath);
        }

        $reel->delete();

        return back()->with('success', 'Reel deleted.');
    }
}
