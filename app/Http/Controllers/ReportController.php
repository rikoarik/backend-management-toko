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
        description: 'Mendapatkan ringkasan dashboard untuk hari ini: total penjualan, jumlah transaksi, dan produk terjual',
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

        return response()->json([
            'today_sales' => (float) $todaySales,
            'today_transactions' => $todayTransactions,
            'total_products_sold' => (int) $totalProductsSold
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
                        ]
                    ),
                    example: [
                        ['date' => '2024-01-01', 'total_sales' => 1200000, 'total_transactions' => 35],
                        ['date' => '2024-01-02', 'total_sales' => 1500000, 'total_transactions' => 42],
                        ['date' => '2024-01-03', 'total_sales' => 980000, 'total_transactions' => 28]
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
        } else {
            // Monthly means daily breakdown for that month? Or just one row? 
            // Usually "Monthly Report" implies seeing daily performance IN that month.
            // Or it could mean 'Yearly Report' broken down by month.
            // Let's assume Daily breakdown FOR the selected month.
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
        }

        return response()->json($report);
    }

    #[OA\Get(
        path: '/api/v1/reports/transactions/export',
        summary: 'Export Transaction Report (CSV)',
        description: 'Mengunduh laporan transaksi dalam format CSV berdasarkan rentang tanggal.',
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
                description: 'File CSV berhasil diunduh',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
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
        $fileName = "transactions_{$startDate}_{$endDate}.csv";

        $items = DB::table('transaction_items')
            ->select([
                'transactions.created_at as transaction_date',
                'transactions.transaction_code',
                'users.name as customer_name',
                'products.name as product_name',
                'categories.name as category_name',
                'transaction_items.quantity',
                'transaction_items.unit_price',
                'transaction_items.subtotal',
                'transactions.payment_method',
                'transactions.status as transaction_status',
                'transactions.notes as transaction_notes',
            ])
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->whereBetween('transactions.created_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->orderBy('transactions.created_at')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');

            // Header row
            fputcsv($file, [
                'Date',
                'Transaction Code',
                'Customer Name',
                'Product Name',
                'Category',
                'Quantity',
                'Unit Price',
                'Subtotal',
                'Payment Method',
                'Status',
                'Notes'
            ]);

            // Data rows
            foreach ($items as $item) {
                fputcsv($file, [
                    $item->transaction_date,
                    $item->transaction_code,
                    $item->customer_name ?? 'Guest',
                    $item->product_name,
                    $item->category_name ?? '-',
                    $item->quantity,
                    $item->unit_price,
                    $item->subtotal,
                    $item->payment_method,
                    $item->transaction_status,
                    $item->transaction_notes,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
