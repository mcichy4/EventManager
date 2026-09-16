<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Http\Request;

use App\Http\Requests\CreateOrganizerRequest;
use App\Actions\Organizers\CreateOrganizer;

use Illuminate\Http\JsonResponse;

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
}
