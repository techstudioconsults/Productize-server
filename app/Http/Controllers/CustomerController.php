<?php

/**
 * @author Tobi Olanitori
 * @version 1.0
 * @since 25-05-2024
 */

namespace App\Http\Controllers;

use App\Helpers\Services\FileGenerator;
use App\Models\Customer;
use App\Http\Resources\CustomerResource;
use App\Repositories\CustomerRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Route handler methods for Customer resource
 */
class CustomerController extends Controller
{
    public function __construct(
        protected CustomerRepository $customerRepository,
        protected FileGenerator $fileGenerator
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
        $fileName = "customers_$now.csv";

        $columns = ['CustomerName', 'CustomerEmail', 'LatestPurchase', 'Price', 'Date'];
        $data = [$columns];

        foreach ($customers as $customer) {
            $data[] = [
                $customer->user->full_name,
                $customer->user->email,
                $customer->order->product->title,
                $customer->order->product->price,
                $customer->created_at
            ];
        }

        $filePath = $this->fileGenerator->generateCsv($fileName, $data);

        return $this->fileGenerator->streamFile($filePath, $fileName, 'text/csv');
    }
}
