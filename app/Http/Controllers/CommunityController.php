<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 09-05-2024
 */

namespace App\Http\Controllers;

use App\Http\Requests\StorecommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Mail\CommunityWelcomeMail;
use App\Repositories\CommunityRepository;
use Illuminate\Support\Facades\Mail;

class CommunityController extends Controller
{
    public function __construct(
        protected CommunityRepository $communityRepository
    ) {
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Retrieves a paginated list of all community member.
     *
     * @return \App\Http\Resources\CommunityResource Returns a paginated collection of all community members.
     */
    public function index()
    {
        $community = $this->communityRepository->find();

        return CommunityResource::collection($community);
    }

    /**
     * @author @obajide028 Odesanya Babajide
     *
     * Store a newly created resource in storage.
     *
     * @param  StorecommunityRequest  $request
     *
     * creates a new community member
     */
    public function store(StorecommunityRequest $request)
    {
        $community = $this->communityRepository->create($request->validated());

        // Send the welcome email
        Mail::to($community->email)->send(new CommunityWelcomeMail());

        return response()->json(new CommunityResource($community), 201);
    }
}
