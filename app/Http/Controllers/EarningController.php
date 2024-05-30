<?php

namespace App\Http\Controllers;

use App\Models\Earning;
use App\Http\Requests\StoreEarningRequest;
use App\Http\Requests\UpdateEarningRequest;

class EarningController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEarningRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Earning $earning)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEarningRequest $request, Earning $earning)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Earning $earning)
    {
        //
    }
}
