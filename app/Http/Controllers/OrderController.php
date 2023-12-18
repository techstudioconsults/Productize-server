<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Http\Resources\SalesResource;
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
        $product_name = $request->product_name;

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

            $orders->whereBetween('created_at', [$start_date, $end_date]);
        }

        if ($product_name) {
            $orders->whereHas('products', function ($query) use ($product_name) {
                $query->where('name', 'like', '%' . $product_name . '%');
            });
        }

        $orders = $orders->paginate(10);

        return SalesResource::collection($orders);
    }

    public function show(Sale $order)
    {
        return new SalesResource($order);
    }
}
