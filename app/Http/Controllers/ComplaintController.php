<?php

namespace App\Http\Controllers;

use App\Http\Requests\LodgeComplaintRequest;
use App\Http\Resources\ComplaintResource;
use App\Mail\LodgeComplaint;
use App\Models\Complaint;
use App\Repositories\ComplaintRepository;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;

class ComplaintController extends Controller
{
    protected ComplaintRepository $complaintRepository;

    public function __construct(ComplaintRepository $complaintRepository)
    {
        $this->complaintRepository = $complaintRepository;
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a paginated listing of the resource.
     *
     * pagination count = 10
     *
     * @return ComplaintResource
     */
    public function index()
    {
        $complaints = $this->complaintRepository->query()->paginate(10);

        return ComplaintResource::collection($complaints);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Lodge a complaint to Productize.
     *
     * This method sends an email to the designated help email address with the subject and message provided
     * in the request. If the request includes an email address, it will use the authenticated user's email
     * address by default unless otherwise specified.
     *
     * @param  LodgeComplaintRequest  $request  The validated request containing the subject and message.
     * @return JsonResource
     */
    public function store(LodgeComplaintRequest $request)
    {
        $validated = $request->validated();

        $user = Auth::user();

        if (! $request->exists('email')) {
            $validated['email'] = $user->email;
        }

        $validated['user_id'] = $user->id;

        $complaint = $this->complaintRepository->create($validated);

        Mail::send(new LodgeComplaint($complaint));

        return new JsonResponse(['message' => 'email sent'], 201);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve the specified complaint.
     *
     * @param  \App\Models\Complaint  $complaint  The complaint to display.
     * @return \App\Http\Resources\ComplaintResource Returns a resource representing the queried complaint.
     */
    public function show(Complaint $complaint)
    {
        return new ComplaintResource($complaint);
    }
}
