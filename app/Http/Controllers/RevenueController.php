<?php

namespace App\Http\Controllers;

use App\Enums\RevenueActivity;
use App\Http\Resources\RevenueResource;
use App\Repositories\RevenueRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @author @Intuneteq Tobi Olanitori
 *
 * @version 1.0
 *
 * @since 22-06-2024
 *
 * Route handler methods for Revenue resource
 */
class RevenueController extends Controller
{
    public function __construct(protected RevenueRepository $revenueRepository) {}

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve a listing of the revenues.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $revenues = $this->revenueRepository->query($filter)->paginate(10);

        return RevenueResource::collection($revenues);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Retrieve revenue statistics.
     *
     * @return JsonResource
     */
    public function stats()
    {
        $total_revenues = $this->revenueRepository->query([])->sum('amount');

        $total_sale_revenue = $this->revenueRepository->query(['activity' => RevenueActivity::PURCHASE->value])->sum('amount');

        $total_subscription_revenue = $this->revenueRepository->query(['product' => 'Subscription'])->sum('amount');

        $total_commission = $this->revenueRepository->query([])->selectRaw('SUM(amount * commission / 100) as total_commission')->value('total_commission');

        return new JsonResource([
            'total_revenues' => $total_revenues,
            'total_sale_revenue' => $total_sale_revenue,
            'total_subscription_revenue' => $total_subscription_revenue,
            'total_commission' => $total_commission,
        ]);
    }

    /**
     * @author @Intuneteq Tobi Olanitori
     *
     * Download the revenue data as a CSV file.
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request)
    {
        $filter = [
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ];

        $revenues = $this->revenueRepository->find($filter);

        $now = Carbon::today()->isoFormat('DD_MMMM_YYYY');
        $fileName = "revenues_$now.csv";

        $columns = ['activity', 'product', 'amount', 'date'];
        $data = [$columns];

        foreach ($revenues as $revenue) {
            $data[] = [
                $revenue->activity,
                $revenue->product,
                $revenue->amount,
                $revenue->created_at,
            ];
        }

        $filePath = $this->generateCsv($fileName, $data);

        return $this->streamFile($filePath, $fileName, 'text/csv');
    }
}
