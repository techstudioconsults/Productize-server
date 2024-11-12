<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatusEnum;
use App\Http\Requests\StoreFunnelRequest;
use App\Http\Requests\UpdateFunnelRequest;
use App\Http\Resources\FunnelResource;
use App\Models\Funnel;
use App\Repositories\FunnelRepository;
use Auth;
use Illuminate\Http\Request;

class FunnelController extends Controller
{
    public function __construct(
        protected FunnelRepository $funnelRepository
    ) {}

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

    public function show(Funnel $funnel)
    {
        return new FunnelResource($funnel);
    }

    public function store(StoreFunnelRequest $request)
    {
        $user = Auth::user();

        $payload = $request->validated();

        $template = $payload['template'];
        $thumbnail = $payload['thumbnail'];

        unset($payload['template']);

        $payload['thumbnail'] = $this->funnelRepository->uploadThumbnail($thumbnail);

        $payload['user_id'] = $user->id;

        $funnel = $this->funnelRepository->create($payload);

        $this->funnelRepository->saveTemplate($funnel, $template);

        if ($payload['status'] === ProductStatusEnum::Draft->value || env('APP_ENV') === "local") {
            return new FunnelResource($funnel);
        }

        $url = $this->funnelRepository->publish($funnel);

        return (new FunnelResource($funnel))->additional([
            'url' => $url
        ]);
    }

    public function update(Funnel $funnel, UpdateFunnelRequest $request)
    {
        $payload = $request->validated();

        if (isset($payload['thumbnail'])) {
            $thumbnail = $payload['thumbnail'];
            $payload['thumbnail'] = $this->funnelRepository->uploadThumbnail($thumbnail);
        }

        if (isset($payload['template'])) {
            $template = $payload['template'];
            $this->funnelRepository->saveTemplate($funnel, $template);
            unset($payload['template']);
        }

        // user is updating the status and the current status is draft - so they want to publish or funnel is currently published but the template was changed
        if ((isset($payload['status']) && $payload['status'] !== $funnel->status && $funnel->status === ProductStatusEnum::Draft->value) || (isset($payload['template']) && $funnel->status === ProductStatusEnum::Published->value)) {
            $this->funnelRepository->publish($funnel); // publish the funnel

            // It is previously published and want to draft it
        } else if (isset($payload['status']) && $payload['status'] !== $funnel->status && $funnel->status === ProductStatusEnum::Published->value) {
            //bring down the server and delete the subdomain
            $this->funnelRepository->drop($funnel);
        }

        // update the database
        $funnel = $this->funnelRepository->update($funnel, $payload);

        return new FunnelResource($funnel);
    }

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
}
