<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ReportController extends Controller
{
    #[OA\Get(
        path: '/api/v1/reports/dashboard',
        summary: 'Get Today\'s Dashboard Summary',
        description: 'Mendapatkan ringkasan dashboard untuk hari ini: total penjualan, jumlah transaksi, produk terjual, dan keuntungan',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Ringkasan dashboard hari ini',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'today_sales', type: 'number', example: 1500000, description: 'Total penjualan hari ini (dalam Rupiah)'),
                    new OA\Property(property: 'today_transactions', type: 'integer', example: 45, description: 'Jumlah transaksi hari ini'),
                    new OA\Property(property: 'total_products_sold', type: 'integer', example: 120, description: 'Total produk terjual hari ini'),
                    new OA\Property(property: 'today_profit', type: 'number', example: 500000, description: 'Total keuntungan hari ini'),
                ])
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function dashboard()
    {
        $today = now()->format('Y-m-d');

        $todaySales = Transaction::whereDate('created_at', $today)->sum('final_amount');
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();
        $totalProductsSold = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereDate('transactions.created_at', $today)
            ->sum('quantity');

        $todayCost = DB::table('transaction_items')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->whereDate('transactions.created_at', $today)
            ->sum(DB::raw('transaction_items.cost_price * transaction_items.quantity'));

        $todayProfit = $todaySales - $todayCost;

        return response()->json([
            'today_sales' => (float) $todaySales,
            'today_transactions' => $todayTransactions,
            'total_products_sold' => (int) $totalProductsSold,
            'today_profit' => (float) $todayProfit
        ]);
    }

    #[OA\Get(
        path: '/api/v1/reports/sales',
        summary: 'Get Sales Report (Daily/Monthly)',
        description: 'Mendapatkan laporan penjualan harian atau bulanan. Untuk daily: gunakan start_date dan end_date. Untuk monthly: gunakan month (format YYYY-MM).',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                in: 'query',
                description: 'Tipe laporan: daily (harian) atau monthly (bulanan)',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['daily', 'monthly'], example: 'daily')
            ),
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                description: 'Tanggal mulai (wajib jika type=daily, format: YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                description: 'Tanggal akhir (wajib jika type=daily, format: YYYY-MM-DD)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')
            ),
            new OA\Parameter(
                name: 'month',
                in: 'query',
                description: 'Bulan laporan (wajib jika type=monthly, format: YYYY-MM)',
                required: false,
                schema: new OA\Schema(type: 'string', example: '2024-01')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Laporan penjualan',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'date', type: 'string', example: '2024-01-14', description: 'Tanggal'),
                            new OA\Property(property: 'total_sales', type: 'number', example: 1500000, description: 'Total penjualan'),
                            new OA\Property(property: 'total_transactions', type: 'integer', example: 45, description: 'Jumlah transaksi'),
                            new OA\Property(property: 'total_profit', type: 'number', example: 500000, description: 'Total keuntungan'),
                        ]
                    ),
                    example: [
                        ['date' => '2024-01-01', 'total_sales' => 1200000, 'total_transactions' => 35, 'total_profit' => 400000],
                        ['date' => '2024-01-02', 'total_sales' => 1500000, 'total_transactions' => 42, 'total_profit' => 500000],
                        ['date' => '2024-01-03', 'total_sales' => 980000, 'total_transactions' => 28, 'total_profit' => 300000]
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation Error - parameter tidak lengkap')
        ]
    )]
    public function sales(Request $request)
    {
        $request->validate([
            'type' => 'required|in:daily,monthly',
            'start_date' => 'required_if:type,daily|date',
            'end_date' => 'required_if:type,daily|date|after_or_equal:start_date',
            'month' => 'required_if:type,monthly|date_format:Y-m',
        ]);

        if ($request->type === 'daily') {
            $report = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_amount) as total_sales'),
                DB::raw('COUNT(*) as total_transactions')
            )
                ->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59'])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $costs = DB::table('transaction_items')
                ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->select(DB::raw('DATE(transactions.created_at) as date'), DB::raw('SUM(transaction_items.cost_price * transaction_items.quantity) as total_cost'))
                ->whereBetween('transactions.created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59'])
                ->groupBy('date')
                ->pluck('total_cost', 'date');
        } else {
            $year = substr($request->month, 0, 4);
            $month = substr($request->month, 5, 2);

            $report = Transaction::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(final_amount) as total_sales'),
                DB::raw('COUNT(*) as total_transactions')
            )
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $costs = DB::table('transaction_items')
                ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
                ->select(DB::raw('DATE(transactions.created_at) as date'), DB::raw('SUM(transaction_items.cost_price * transaction_items.quantity) as total_cost'))
                ->whereYear('transactions.created_at', $year)
                ->whereMonth('transactions.created_at', $month)
                ->groupBy('date')
                ->pluck('total_cost', 'date');
        }

        $report->transform(function ($item) use ($costs) {
            $cost = $costs[$item->date] ?? 0;
            $item->total_profit = (float) $item->total_sales - $cost;
            return $item;
        });

        return response()->json($report);
    }

    #[OA\Get(
        path: '/api/v1/reports/transactions/export',
        summary: 'Export Transaction Report (Excel)',
        description: 'Mengunduh laporan transaksi dalam format Excel (.xlsx) berdasarkan rentang tanggal.',
        security: [['sanctum' => []]],
        tags: ['Reports'],
        parameters: [
            new OA\Parameter(
                name: 'start_date',
                in: 'query',
                description: 'Tanggal mulai (wajib, format: YYYY-MM-DD)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')
            ),
            new OA\Parameter(
                name: 'end_date',
                in: 'query',
                description: 'Tanggal akhir (wajib, format: YYYY-MM-DD)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'File Excel berhasil diunduh',
                content: new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function exportTransactions(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $fileName = "transactions_{$startDate}_{$endDate}.xlsx";

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\TransactionsExport($startDate, $endDate), $fileName);
    }
}
