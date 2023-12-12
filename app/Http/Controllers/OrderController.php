<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use Auth;
use Illuminate\Http\Request;
use Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $orders = $user->orders();

        if ($start_date && $end_date) {
            $validator = Validator::make([
                'start_date' => $start_date,
                'end_date' => $end_date
            ], [
                'start_date' => 'date',
                'end_date' => 'date'
            ]);

            if ($validator->fails()) {
                throw new UnprocessableException($validator->errors()->first());
            }

            $orders->whereBetween('orders.created_at', [$start_date, $end_date]);
        }

        $orders = $orders->paginate(10);

        return SaleResource::collection($orders);
    }

    public function show(Sale $order)
    {
        return new SaleResource($order);
    }
}
