<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class TransactionController extends Controller
{
    #[OA\Post(
        path: '/api/v1/transactions',
        summary: 'Create new transaction (Checkout)',
        description: 'Membuat transaksi baru (checkout). Stok produk akan otomatis dikurangi.',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items', 'payment_method'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        description: 'Daftar produk yang dibeli (wajib)',
                        items: new OA\Items(
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer', example: 1, description: 'ID produk'),
                                new OA\Property(property: 'quantity', type: 'integer', example: 2, description: 'Jumlah yang dibeli'),
                                new OA\Property(property: 'price_type', type: 'string', enum: ['standard', 'wholesale', 'retail'], example: 'standard', description: 'Tipe harga (opsional, default: standard)'),
                            ]
                        ),
                        example: [
                            ['product_id' => 1, 'quantity' => 2],
                            ['product_id' => 3, 'quantity' => 1]
                        ]
                    ),
                    new OA\Property(property: 'discount_amount', type: 'integer', example: 5000, description: 'Diskon dalam rupiah (opsional, default: 0)'),
                    new OA\Property(property: 'payment_method', type: 'string', enum: ['cash', 'qris', 'transfer'], example: 'cash', description: 'Metode pembayaran (wajib)'),
                    new OA\Property(property: 'notes', type: 'string', example: 'Pelanggan minta kantong plastik', description: 'Catatan transaksi (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Transaksi berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Transaction created successfully'),
                    new OA\Property(
                        property: 'transaction',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'transaction_code', type: 'string', example: 'TRX-1705234567-123'),
                            new OA\Property(property: 'user_id', type: 'integer', example: 1),
                            new OA\Property(property: 'total_amount', type: 'integer', example: 25000),
                            new OA\Property(property: 'discount_amount', type: 'integer', example: 5000),
                            new OA\Property(property: 'final_amount', type: 'integer', example: 20000),
                            new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
                            new OA\Property(property: 'status', type: 'string', example: 'completed'),
                            new OA\Property(property: 'notes', type: 'string', example: 'Pelanggan minta kantong plastik'),
                            new OA\Property(property: 'created_at', type: 'string', example: '2024-01-14T10:00:00.000000Z'),
                            new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'product_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'product_name', type: 'string', example: 'Teh Botol Sosro'),
                                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'unit_price', type: 'integer', example: 5000),
                                    new OA\Property(property: 'cost_price', type: 'integer', example: 3500),
                                    new OA\Property(property: 'subtotal', type: 'integer', example: 10000),
                                ]
                            )),
                        ]
                    ),
                ])
            ),
            new OA\Response(
                response: 400,
                description: 'Stok tidak mencukupi',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Stock verification failed for Teh Botol Sosro. Requested: 10, Available: 5')
                ])
            ),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_type' => 'nullable|string|in:standard,wholesale,retail',
            'discount_amount' => 'integer|min:0',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request) {
            $totalAmount = 0;
            $transactionItems = [];

            // 1. Validate Stock & Calculate Total
            foreach ($request->items as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Stok tidak mencukupi untuk {$product->name}. Diminta: {$item['quantity']}, Tersedia: {$product->stock}"
                    ], 400);
                }

                $product->stock -= $item['quantity'];
                $product->save();

                // Determine price based on type
                $priceType = $item['price_type'] ?? 'standard';
                $unitPrice = match ($priceType) {
                    'wholesale' => $product->wholesale_price ?? $product->price,
                    'retail' => $product->retail_price ?? $product->price,
                    default => $product->price,
                };

                $subtotal = $unitPrice * $item['quantity'];
                $totalAmount += $subtotal;

                $transactionItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'cost_price' => $product->cost_price ?? 0,
                    'subtotal' => $subtotal,
                ];
            }

            // 2. Create Transaction Header
            $finalAmount = $totalAmount - ($request->discount_amount ?? 0);

            $transaction = Transaction::create([
                'transaction_code' => 'TRX-' . time() . '-' . mt_rand(100, 999),
                'user_id' => auth()->id(),
                'total_amount' => $totalAmount,
                'discount_amount' => $request->discount_amount ?? 0,
                'final_amount' => max(0, $finalAmount),
                'payment_method' => $request->payment_method,
                'status' => 'completed',
                'notes' => $request->notes,
            ]);

            // 3. Create Transaction Items
            foreach ($transactionItems as $item) {
                $transaction->items()->create($item);
            }

            $transaction->load('items');

            return response()->json([
                'message' => 'Transaksi berhasil dibuat',
                'transaction' => $transaction
            ], 201);
        });
    }

    #[OA\Get(
        path: '/api/v1/transactions',
        summary: 'List transactions (Paginated)',
        description: 'Mendapatkan daftar transaksi dengan pagination, bisa filter berdasarkan tanggal',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'size', in: 'query', description: 'Jumlah item per halaman', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Filter dari tanggal (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Filter sampai tanggal (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar transaksi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'transaction_code', type: 'string', example: 'TRX-1705234567-123'),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'total_amount', type: 'integer', example: 25000),
                                new OA\Property(property: 'discount_amount', type: 'integer', example: 5000),
                                new OA\Property(property: 'final_amount', type: 'integer', example: 20000),
                                new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
                                new OA\Property(property: 'status', type: 'string', example: 'completed'),
                                new OA\Property(property: 'created_at', type: 'string', example: '2024-01-14T10:00:00.000000Z'),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                                    ]
                                ),
                            ]
                        )),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                    ]
                )
            )
        ]
    )]
    public function index(Request $request)
    {
        $query = Transaction::with('user');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $size = $request->input('size', 10);
        return response()->json($query->latest()->paginate($size));
    }

    #[OA\Get(
        path: '/api/v1/transactions/{id}',
        summary: 'Get transaction detail',
        description: 'Mendapatkan detail transaksi beserta daftar item yang dibeli',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID transaksi', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail transaksi',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'transaction_code', type: 'string', example: 'TRX-1705234567-123'),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'total_amount', type: 'integer', example: 25000),
                        new OA\Property(property: 'discount_amount', type: 'integer', example: 5000),
                        new OA\Property(property: 'final_amount', type: 'integer', example: 20000),
                        new OA\Property(property: 'payment_method', type: 'string', example: 'cash'),
                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                        new OA\Property(property: 'notes', type: 'string', example: 'Pelanggan minta kantong plastik'),
                        new OA\Property(property: 'created_at', type: 'string', example: '2024-01-14T10:00:00.000000Z'),
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'product_id', type: 'integer', example: 1),
                                new OA\Property(property: 'product_name', type: 'string', example: 'Teh Botol Sosro'),
                                new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                new OA\Property(property: 'unit_price', type: 'integer', example: 5000),
                                new OA\Property(property: 'subtotal', type: 'integer', example: 10000),
                            ]
                        )),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'email', type: 'string', example: 'budi@example.com'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Transaction not found')
        ]
    )]
    public function show($id)
    {
        return response()->json(Transaction::with(['items', 'user'])->findOrFail($id));
    }
}
