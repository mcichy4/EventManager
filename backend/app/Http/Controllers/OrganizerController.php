<?php

namespace App\Http\Controllers;

use App\Actions\Organizers\AddOrganizerMember;
use App\Actions\Organizers\CreateOrganizer;
use App\Http\Requests\AddOrganizerMemberRequest;
use App\Http\Requests\CreateOrganizerRequest;
use App\Models\Organizer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateOrganizerRequest $request, CreateOrganizer $createOrganizer): JsonResponse
    {
        $organizer = $createOrganizer->execute(
            $request->user(),
            $request->validated('name'),
            $request->validated('type'),
            $request->validated('description')
        );

        return response()->json($organizer, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Organizer $organizer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organizer $organizer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organizer $organizer)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizer $organizer)
    {
        //
    }

    public function addMember(AddOrganizerMemberRequest $request, AddOrganizerMember $addOrganizerMember, Organizer $organizer): JsonResponse
    {
        Gate::authorize('manageMember', $organizer);
        $addOrganizerMember->execute(
            User::findOrFail($request->validated('user_id')),
            $organizer
        );

        return response()->json(['message' => 'Member added successfully.'], 201);
    }

    public function listMembers(Organizer $organizer): JsonResponse
    {
        Gate::authorize('manageMember', $organizer);
        $members = $organizer
            ->users()
            ->withPivot('role')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->pivot->role,
                ];
            });

        return response()->json($members);
    }

    public function removeMember(Organizer $organizer, User $member): JsonResponse
    {
        Gate::authorize('manageMember', $organizer);
        app(\App\Actions\Organizers\DeleteOrganizerMember::class)->execute($organizer, $member);
        return response()->json(['message' => 'Member removed successfully.'], 204);
       }
}
