<?php

namespace App\Http\Controllers;

use App\Enums\ProductStatusEnum;
use App\Http\Requests\StoreFunnelRequest;
use App\Http\Resources\FunnelResource;
use App\Repositories\FunnelRepository;
use Auth;
use Storage;

class FunnelController extends Controller
{
    public function __construct(
        protected FunnelRepository $funnelRepository
    ) {}

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

        // Create the template html file
        $html = view('funnels.template', ['template' => $template])->render();

        // Save the template locally
        Storage::disk('local')->put('funnels/' . $funnel->slug . '.html', $html);

        if ($payload['status'] === ProductStatusEnum::Draft->value || env('APP_ENV') === "local") {
            return new FunnelResource($funnel);
        }

        $url = $this->funnelRepository->publish($funnel);

        return (new FunnelResource($funnel))->additional([
            'url' => $url
        ]);
    }
}
