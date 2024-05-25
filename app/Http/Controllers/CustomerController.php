<?php

namespace App\Http\Controllers;

use App\Exceptions\UnprocessableException;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use App\Repositories\CustomerRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository
    ) {
    }
    /**
     * Display a listing of user customers.
     * @return CustomerResource
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        // Prepare the filter array
        $filter = [];
        if ($start_date && $end_date) {
            $filter['start_date'] = $start_date;
            $filter['end_date'] = $end_date;
        }

        $relations = $user->customers();

        // Get customers with filtering
        $customersQuery = $this->customerRepository->queryRelation($relations, $filter);

        // Paginate the results
        $customers = $customersQuery->paginate(10);

        return CustomerResource::collection($customers);
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    public function download(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $customers = $this->customerRepository->find([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'merchant_id' => $user->id
        ]);

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = array('CustomerName', 'CustomerEmail', 'LatestPurchase', 'Price', 'Date');

        $data = [];

        $data[] = $columns;

        $fileName = "customers_$now.csv";

        foreach ($customers as $customer) {
            $row['CustomerName']  = $customer->user->full_name;
            $row['CustomerEmail']  = $customer->user->email;
            $row['LatestPurchase']  = $customer->order->product->title;
            $row['Price']  = $customer->order->product->price;
            $row['Date']   = $customer->created_at;

            $data[] = array($row['CustomerName'], $row['CustomerEmail'], $row['LatestPurchase'], $row['Price'], $row['Date']);
        }

        $csvContent = '';
        foreach ($data as $csvRow) {
            $csvContent .= implode(',', $csvRow) . "\n";
        }

        $filePath = 'csv/' . $fileName;

        // Store the CSV content in the storage/app/csv directory
        Storage::disk('local')->put($filePath, $csvContent);

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        // Return the response with the file from storage
        return response()->stream(function () use ($filePath) {
            readfile(storage_path('app/' . $filePath));
        }, 200, $headers);
    }
}
