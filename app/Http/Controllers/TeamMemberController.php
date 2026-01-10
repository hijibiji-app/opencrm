<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Team $team)
    {
        Gate::authorize('manageMembers', $team);

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => 'required|string|in:admin,member',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return back()->withErrors(['email' => 'User is already a member of this team.']);
        }

        $team->members()->attach($user->id, ['role' => $request->role]);

        return back()->with('success', 'Member added successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, User $member)
    {
        Gate::authorize('manageMembers', $team);

        $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        if ($member->id === $team->owner_id) {
            return back()->withErrors(['role' => 'The owner role cannot be changed.']);
        }

        $team->members()->updateExistingPivot($member->id, ['role' => $request->role]);

        return back()->with('success', 'Member role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, User $member)
    {
        Gate::authorize('manageMembers', $team);

        if ($member->id === $team->owner_id) {
            return back()->withErrors(['member' => 'The team owner cannot be removed.']);
        }

        $team->members()->detach($member->id);

        return back()->with('success', 'Member removed successfully.');
    }
}
