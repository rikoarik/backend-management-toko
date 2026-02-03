<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/v1/dashboard/chart',
        summary: 'Get chart data for dashboard',
        description: 'Mendapatkan data grafik pemasukan dan pengeluaran per hari untuk dashboard. Default: 7 hari terakhir.',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        parameters: [
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Tanggal awal (YYYY-MM-DD). Default: 7 hari yang lalu', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Tanggal akhir (YYYY-MM-DD). Default: hari ini', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-07')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Data grafik berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'chart_data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'date', type: 'string', example: '2024-01-01'),
                                    new OA\Property(property: 'label', type: 'string', example: '1 Jan'),
                                    new OA\Property(property: 'pemasukan', type: 'integer', example: 500000),
                                    new OA\Property(property: 'pengeluaran', type: 'integer', example: 200000),
                                ]
                            )
                        ),
                        new OA\Property(
                            property: 'summary',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_pemasukan', type: 'integer', example: 3500000),
                                new OA\Property(property: 'total_pengeluaran', type: 'integer', example: 1400000),
                                new OA\Property(property: 'profit', type: 'integer', example: 2100000),
                            ]
                        ),
                        new OA\Property(property: 'period', type: 'object', properties: [
                            new OA\Property(property: 'start_date', type: 'string', example: '2024-01-01'),
                            new OA\Property(property: 'end_date', type: 'string', example: '2024-01-07'),
                        ])
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function chart(Request $request)
    {
        // Default: 7 hari terakhir
        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->end_date)
            : Carbon::today();

        $startDate = $request->filled('start_date')
            ? Carbon::parse($request->start_date)
            : $endDate->copy()->subDays(6);

        // Get income (pemasukan) from transactions
        $income = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('SUM(final_amount) as total')
        )
            ->where('status', 'completed')
            ->whereBetween('created_at', [
                $startDate->startOfDay(),
                $endDate->copy()->endOfDay()
            ])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('total', 'date')
            ->toArray();

        // Get expenses (pengeluaran)
        $expenses = Expense::select(
            'expense_date as date',
            DB::raw('SUM(amount) as total')
        )
            ->whereBetween('expense_date', [
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            ])
            ->groupBy('expense_date')
            ->pluck('total', 'date')
            ->toArray();

        // Build chart data for each day
        $chartData = [];
        $totalPemasukan = 0;
        $totalPengeluaran = 0;

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');

            $pemasukan = $income[$dateKey] ?? 0;
            $pengeluaran = $expenses[$dateKey] ?? 0;

            $totalPemasukan += $pemasukan;
            $totalPengeluaran += $pengeluaran;

            $chartData[] = [
                'date' => $dateKey,
                'label' => $currentDate->format('j M'), // "1 Jan", "2 Jan", etc.
                'pemasukan' => (int) $pemasukan,
                'pengeluaran' => (int) $pengeluaran,
            ];

            $currentDate->addDay();
        }

        return response()->json([
            'chart_data' => $chartData,
            'summary' => [
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                'profit' => $totalPemasukan - $totalPengeluaran,
            ],
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    #[OA\Get(
        path: '/api/v1/dashboard/summary',
        summary: 'Get dashboard summary',
        description: 'Mendapatkan ringkasan dashboard: total produk, transaksi hari ini, pemasukan hari ini, pengeluaran hari ini',
        security: [['sanctum' => []]],
        tags: ['Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Summary berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_products', type: 'integer', example: 150),
                        new OA\Property(property: 'total_categories', type: 'integer', example: 10),
                        new OA\Property(property: 'transactions_today', type: 'integer', example: 25),
                        new OA\Property(property: 'income_today', type: 'integer', example: 1500000),
                        new OA\Property(property: 'expense_today', type: 'integer', example: 500000),
                        new OA\Property(property: 'profit_today', type: 'integer', example: 1000000),
                        new OA\Property(property: 'low_stock_products', type: 'integer', example: 5, description: 'Jumlah produk dengan stok <= 10'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function summary()
    {
        $today = Carbon::today();

        $totalProducts = \App\Models\Product::count();
        $totalCategories = \App\Models\Category::count();

        $transactionsToday = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->count();

        $incomeToday = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->sum('final_amount');

        $expenseToday = Expense::whereDate('expense_date', $today)
            ->sum('amount');

        $lowStockProducts = \App\Models\Product::where('stock', '<=', 10)->count();

        return response()->json([
            'total_products' => $totalProducts,
            'total_categories' => $totalCategories,
            'transactions_today' => $transactionsToday,
            'income_today' => (int) $incomeToday,
            'expense_today' => (int) $expenseToday,
            'profit_today' => (int) ($incomeToday - $expenseToday),
            'low_stock_products' => $lowStockProducts,
        ]);
    }
}
