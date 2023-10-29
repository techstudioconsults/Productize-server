<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of user customers.
     * @return CustomerResource
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $customers = $user->customers();

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

            $customers->whereBetween('customers.created_at', [$start_date, $end_date]);
        }

        $customers = $customers->paginate(10);

        return CustomerResource::collection($customers);
    }

    public function store(StoreCustomerRequest $request)
    {
        //
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }
}
