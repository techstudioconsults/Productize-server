<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *  @version 1.0
 *  @since 09-05-2024
 */

namespace App\Http\Controllers;

use App\Models\community;
use App\Http\Requests\StorecommunityRequest;
use App\Http\Requests\UpdatecommunityRequest;
use App\Http\Resources\CommunityResource;
use App\Repositories\CommunityRepository;
use App\Mail\CommunityWelcomeMail;
use Illuminate\Support\Facades\Mail;

class CommunityController extends Controller
{
    public function __construct(
        protected CommunityRepository $communityRepository
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $community = $this->communityRepository->findAll();
        return CommunityResource::collection($community);
    }


    /**
     * Store a newly created resource in storage.
     * @param StorecommunityRequest $request 
     * 
     * creates a new StorecommunityRequest $request The HTTP request containing query parameters:
     *                         - title (required)
     *                         - question (required)
     *                         - answer (required)
     */
    public function store(StorecommunityRequest $request)
    {
        $community = $this->communityRepository->create($request->validated());

        // Send the welcome email
        Mail::to($community->email)->send(new CommunityWelcomeMail());

        return  response()->json(new CommunityResource($community), 201);
    }
}
