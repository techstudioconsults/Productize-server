<?php

namespace App\Http\Controllers;

use App\Http\Resources\PayoutResource;
use App\Repositories\PayoutRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Storage;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutRepository $payoutRepository
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $payouts = $this->payoutRepository->queryRelation($user->payouts(), $filter)->paginate(5);

        return PayoutResource::collection($payouts);
    }

    public function download(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date
        ];

        $payouts = $this->payoutRepository->queryRelation($user->payouts(), $filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');

        $columns = array('Price', 'BankName', 'BankAccountNumber', 'Period', 'Status');

        $data = [];

        $data[] = $columns;

        $fileName = "payouts_$now.csv";

        foreach ($payouts as $payout) {
            $row['Price']  = $payout->amount;
            $row['BankName']  = $payout->account->bank_name;
            $row['BankAccountNumber']  = $payout->account->account_number;
            $row['Period']  = $payout->created_at;
            $row['Status']  = $payout->status;

            $data[] = array($row['Price'], $row['BankName'], $row['BankAccountNumber'], $row['Period']);
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
