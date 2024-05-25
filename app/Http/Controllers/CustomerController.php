<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 25-05-2024
 */

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use App\Repositories\CustomerRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Route handler methods for Cart resource
 */
class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository
    ) {
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieves a paginated list of a user's customers.
     *
     * @return \App\Http\Resources\CustomerResource Returns a paginated collection of a user customers.
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

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrive the specified customer.
     *
     * @param  \App\Models\Customer  $customer The customer to display.
     * @return \App\Http\Resources\CustomerResource Returns a resource representing the queried customer.
     */
    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Download a CSV file containing a list of a user customers based on the specified date range.
     *
     * This method retrieves customers within a given date range and creates a CSV file
     * containing customer information such as name, email, latest purchase, price, and date.
     * The CSV file is then stored in the local storage and returned as a response for download.
     *
     * @param Request $request The request object containing the start and end dates.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The response containing the CSV file for download.
     *
     * @throws \Illuminate\Validation\ValidationException If the start_date or end_date are not valid dates.
     * @throws \App\Exceptions\UnprocessableException If the start_date or end_date are invalid according to custom validation rules.
     */
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
            
            // Delete the file after reading
            Storage::disk('local')->delete($filePath);
        }, 200, $headers);
    }
}
