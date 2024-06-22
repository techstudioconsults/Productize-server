<?php

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 08-06-2024
 */

namespace App\Http\Controllers;

use App\Helpers\Services\HasFileGenerator;
use App\Http\Resources\PayoutResource;
use App\Repositories\PayoutRepository;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Route handler methods for Payout resource
 */
class PayoutController extends Controller
{
    use HasFileGenerator;

    public function __construct(
        protected PayoutRepository $payoutRepository,
    ) {
    }

    public function index(Request $request)
    {
        $filter = [
            'start_date' =>  $request->start_date,
            'end_date' => $request->end_date
        ];

        $payouts = $this->payoutRepository->find($filter);

        return PayoutResource::collection($payouts);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Display a paginated list of payouts for the authenticated user,
     * filtered by the given start and end dates.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function user(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $payouts = $this->payoutRepository->queryRelation($user->payouts(), $filter)->paginate(5);

        return PayoutResource::collection($payouts);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Download a CSV file of the user's payouts filtered by the given start and end dates.
     *
     * @return \Illuminate\Http\Response
     */
    public function download(Request $request)
    {
        $user = Auth::user();

        $start_date = $request->start_date;

        $end_date = $request->end_date;

        $filter = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];

        $payouts = $this->payoutRepository->queryRelation($user->payouts(), $filter)->get();

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "payouts_$now.csv";

        $columns = ['Price', 'BankName', 'BankAccountNumber', 'Period', 'Status'];
        $data = [$columns];

        foreach ($payouts as $payout) {
            $data[] = [
                $payout->amount,
                $payout->account->bank_name,
                $payout->account->account_number,
                $payout->created_at,
                $payout->status,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }
}
