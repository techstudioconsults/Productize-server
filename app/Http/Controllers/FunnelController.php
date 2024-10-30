<?php

namespace App\Http\Controllers;

use Artisan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Storage;

class FunnelController extends Controller
{
    public function store(Request $request)
    {
        $payload = $request->only('page_name', 'content');

        $page_name = $payload['page_name'];

        // check it page name exist

        $content = $payload['content'];

        // Step 1: create the html
        $html = view('funnels.template', ['data' => $content])->render();

        // Step 2: copy it into  storage
        Storage::disk('local')->put('funnels/'.$page_name.'.html', $html);

        // run artisan scripts
        Artisan::call('deploy:funnel', ['page' => $page_name]);

        $url = "https://{$page_name}.trybytealley.com";

        return new JsonResource(['url' => $url]);
    }
}
