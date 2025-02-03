<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatusEnum;
use App\Http\Requests\GetPackageRequest;
use App\Http\Requests\StoreFunnelRequest;
use App\Http\Requests\UpdateFunnelRequest;
use App\Http\Resources\FunnelResource;
use App\Jobs\CreateFunnelCampaignList;
use App\Jobs\FunnelCampaignSubscriber;
use App\Mail\ProductReady;
use App\Models\Funnel;
use App\Models\Product;
use App\Repositories\FunnelRepository;
use Auth;
use Illuminate\Http\Request;
use Mail;

class FunnelController extends Controller
{
    public function __construct(
        protected FunnelRepository $funnelRepository,
    ) {}

    /**
     * Retrieves and returns a paginated collection of funnels associated with the currently authenticated user,
     * filtered by status, start date, and end date as provided in the request.
     *
     * @param  \Illuminate\Http\Request  $request  The HTTP request instance containing filter parameters.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection A collection of paginated funnels.
     *
     * @throws \Illuminate\Validation\ValidationException If the input validation fails for the filters.
     */
    public function user(Request $request)
    {
        $user = Auth::user();

        $filter = [
            'user_id' => $user->id,
            'status' => $request->status,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $query = $this->funnelRepository->query($filter);

        // Paginate the results
        $paginatedFunnels = $query->paginate(10);

        // Append the query parameters to the pagination links
        $paginatedFunnels->appends($request->query());

        return FunnelResource::collection($paginatedFunnels);
    }

    /**
     * Displays the details of a specific funnel.
     *
     * This method returns a resource representation of the funnel, which includes all its attributes and relationships.
     * The `FunnelResource` will format and return the funnel data for API responses.
     *
     * @param  \App\Models\Funnel  $funnel  The funnel model to display.
     * @return \App\Http\Resources\FunnelResource The resource representation of the funnel.
     */
    public function show(Funnel $funnel)
    {
        return new FunnelResource($funnel);
    }

    /**
     * Handles the creation of a new funnel, including uploading the thumbnail, associating a template,
     * and setting the funnel's status. If the environment is not local, the funnel is published after creation.
     *
     * @param  \App\Http\Requests\StoreFunnelRequest  $request  The request containing validated funnel data.
     * @return \App\Http\Resources\FunnelResource The resource representation of the newly created funnel.
     *
     * @throws \App\Exceptions\ServerErrorException If there is an error while uploading the thumbnail or publishing the funnel.
     */
    public function store(StoreFunnelRequest $request)
    {
        $user = Auth::user();

        $payload = $request->validated();

        // $template = $payload['template'];
        $thumbnail = $payload['thumbnail'];

        if (isset($payload['asset'])) {
            $asset = $payload['asset'];

            $payload['asset'] = $this->funnelRepository->uploadAsset($asset);
        }

        $payload['thumbnail'] = $this->funnelRepository->uploadThumbnail($thumbnail);

        $payload['user_id'] = $user->id;

        $funnel = $this->funnelRepository->create($payload);

        $this->funnelRepository->saveTemplate($funnel, $request->getParsedTemplate());

        CreateFunnelCampaignList::dispatch($funnel);

        if ($payload['status'] === ProductStatusEnum::Draft->value || env('APP_ENV') === 'local') {
            return new FunnelResource($funnel);
        }

        $this->funnelRepository->publish($funnel);

        return new FunnelResource($funnel);
    }

    /**
     * Update the details of a specific funnel.
     *
     * This method allows updating a funnel's attributes, including the thumbnail and template.
     * It also handles specific business logic for changing the funnel's status between 'Draft' and 'Published' states.
     * If the status is being changed, and the funnel is in 'Draft' or 'Published' state, additional operations like publishing or dropping the funnel are performed.
     *
     * @param  \App\Models\Funnel  $funnel  The funnel model to update.
     * @param  \App\Http\Requests\UpdateFunnelRequest  $request  The validated request data.
     * @return \App\Http\Resources\FunnelResource The resource representation of the updated funnel.
     *
     * @throws \App\Exceptions\ServerErrorException If any errors occur during the update or publishing process.
     */
    public function update(Funnel $funnel, UpdateFunnelRequest $request)
    {
        $payload = $request->validated();

        if (isset($payload['thumbnail'])) {
            $thumbnail = $payload['thumbnail'];
            $payload['thumbnail'] = $this->funnelRepository->uploadThumbnail($thumbnail);
        }

        if (isset($payload['template'])) {
            $this->funnelRepository->saveTemplate($funnel, $request->getParsedTemplate());
        }

        if (isset($payload['asset'])) {
            $asset = $payload['asset'];
            $payload['asset'] = $this->funnelRepository->uploadAsset($asset);
        }

        // user is updating the status and the current status is draft - so they want to publish or funnel is currently published but the template was changed
        if ((isset($payload['status']) && $payload['status'] !== $funnel->status && $funnel->status === ProductStatusEnum::Draft->value) || (isset($payload['template']) && $funnel->status === ProductStatusEnum::Published->value)) {
            $this->funnelRepository->publish($funnel); // publish the funnel

            // It is previously published and want to draft it
        } elseif (isset($payload['status']) && $payload['status'] !== $funnel->status && $funnel->status === ProductStatusEnum::Published->value) {
            // bring down the server and delete the subdomain
            $this->funnelRepository->drop($funnel);
        }

        // update the database
        $funnel = $this->funnelRepository->update($funnel, $payload);

        return new FunnelResource($funnel);
    }

    /**
     * Delete a specific funnel.
     *
     * This method performs the deletion of a funnel. If the funnel is currently published, it first brings down the server by calling the drop method on the repository to handle any necessary cleanup (such as removing the subdomain and server settings).
     * Then, the funnel's status is updated to 'draft', the funnel is soft deleted, and the updated funnel is returned as a resource.
     *
     * @param  \App\Models\Funnel  $funnel  The funnel model to delete.
     * @return \App\Http\Resources\FunnelResource The resource representation of the deleted funnel.
     *
     * @throws \App\Exceptions\ServerErrorException If any errors occur during the deletion process.
     */
    public function delete(Funnel $funnel)
    {
        // If it is currently published, bring down the server
        if ($funnel->status === ProductStatusEnum::Published->value) {
            // bring down server here
            $this->funnelRepository->drop($funnel);
        }

        $funnel->status = 'draft';
        $funnel->save();
        $funnel->delete();

        return new FunnelResource($funnel);
    }

    public function restore(Funnel $funnel)
    {
        $funnel->restore();

        return new FunnelResource($funnel);
    }

    public function sendFunnelAsset(GetPackageRequest $request, Funnel $funnel)
    {
        $email = $request->input('email');
        $first_name = $request->input('first_name');
        $last_name = $request->input('last_name');
        $maillist_permission = $request->input('maillist_permission');
        $validated = $request->validated();

        // Add to email list subscriber
        FunnelCampaignSubscriber::dispatchIf($maillist_permission, $funnel, [
            'email' => $email,
            'fullname' => [
                'first_name' => $first_name,
                'last_name' => $last_name,
            ],
        ]);

        // get the products attached to the funnel.
        $productId = null;
        if (!empty($funnel->products)) {
            $productId = trim($funnel->products[0], '[]');
        }

        // get the product purchase url of the funnel and send them with the email.
        $purchaseUrl = '';
        if ($productId) {
            $product = Product::find($productId);
            if ($product) {
                $purchaseUrl = config('app.client_url') . "/products/{$product->slug}";
            }
        }

        // Send email with the product URL
        Mail::to($validated['email'])
            ->send(new ProductReady($funnel, $purchaseUrl));

        return response()->json(['message' => 'Email sent successfully'], 200);
    }
}
