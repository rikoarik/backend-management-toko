<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ExpenseController extends Controller
{
    #[OA\Get(
        path: '/api/v1/expenses',
        summary: 'List expenses (Paginated)',
        description: 'Mendapatkan daftar pengeluaran dengan pagination dan filter',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'size', in: 'query', description: 'Jumlah item per halaman', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter berdasarkan kategori (beli_stok, operasional, gaji, lainnya)', required: false, schema: new OA\Schema(type: 'string', example: 'beli_stok')),
            new OA\Parameter(name: 'start_date', in: 'query', description: 'Filter dari tanggal (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-01')),
            new OA\Parameter(name: 'end_date', in: 'query', description: 'Filter sampai tanggal (YYYY-MM-DD)', required: false, schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-31')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar pengeluaran berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'user_id', type: 'integer', example: 1),
                                new OA\Property(property: 'category', type: 'string', example: 'beli_stok'),
                                new OA\Property(property: 'amount', type: 'integer', example: 500000),
                                new OA\Property(property: 'description', type: 'string', example: 'Beli stok minuman'),
                                new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2024-01-14'),
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
                        new OA\Property(property: 'last_page', type: 'integer', example: 10),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 100),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request)
    {
        $query = Expense::with('user');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('expense_date', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $size = $request->input('size', 10);
        return response()->json($query->latest('expense_date')->paginate($size));
    }

    #[OA\Post(
        path: '/api/v1/expenses',
        summary: 'Create a new expense',
        description: 'Membuat pengeluaran baru',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category', 'amount', 'expense_date'],
                properties: [
                    new OA\Property(property: 'category', type: 'string', enum: ['beli_stok', 'operasional', 'gaji', 'lainnya'], example: 'beli_stok', description: 'Kategori pengeluaran'),
                    new OA\Property(property: 'amount', type: 'integer', example: 500000, description: 'Jumlah pengeluaran (rupiah)'),
                    new OA\Property(property: 'description', type: 'string', example: 'Beli stok minuman dari supplier', description: 'Keterangan pengeluaran'),
                    new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2024-01-14', description: 'Tanggal pengeluaran (YYYY-MM-DD)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Pengeluaran berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Expense created successfully'),
                    new OA\Property(
                        property: 'expense',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'category', type: 'string', example: 'beli_stok'),
                            new OA\Property(property: 'amount', type: 'integer', example: 500000),
                            new OA\Property(property: 'description', type: 'string', example: 'Beli stok minuman'),
                            new OA\Property(property: 'expense_date', type: 'string', example: '2024-01-14'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|in:beli_stok,operasional,gaji,lainnya',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'expense_date' => 'required|date',
        ]);

        $expense = Expense::create([
            'user_id' => auth()->id(),
            'category' => $request->category,
            'amount' => $request->amount,
            'description' => $request->description,
            'expense_date' => $request->expense_date,
        ]);

        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/expenses/{id}',
        summary: 'Get expense detail',
        description: 'Mendapatkan detail pengeluaran berdasarkan ID',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID pengeluaran', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail pengeluaran',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(property: 'category', type: 'string', example: 'beli_stok'),
                        new OA\Property(property: 'amount', type: 'integer', example: 500000),
                        new OA\Property(property: 'description', type: 'string', example: 'Beli stok minuman'),
                        new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2024-01-14'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Expense not found')
        ]
    )]
    public function show($id)
    {
        return response()->json(Expense::with('user')->findOrFail($id));
    }

    #[OA\Put(
        path: '/api/v1/expenses/{id}',
        summary: 'Update expense',
        description: 'Update data pengeluaran',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID pengeluaran', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'category', type: 'string', enum: ['beli_stok', 'operasional', 'gaji', 'lainnya'], example: 'operasional'),
                    new OA\Property(property: 'amount', type: 'integer', example: 600000),
                    new OA\Property(property: 'description', type: 'string', example: 'Bayar listrik bulan ini'),
                    new OA\Property(property: 'expense_date', type: 'string', format: 'date', example: '2024-01-15'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengeluaran berhasil diupdate',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Expense updated successfully'),
                    new OA\Property(
                        property: 'expense',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'category', type: 'string', example: 'operasional'),
                            new OA\Property(property: 'amount', type: 'integer', example: 600000),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'Expense not found'),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $request->validate([
            'category' => 'sometimes|string|in:beli_stok,operasional,gaji,lainnya',
            'amount' => 'sometimes|integer|min:1',
            'description' => 'nullable|string|max:255',
            'expense_date' => 'sometimes|date',
        ]);

        $expense->update($request->only(['category', 'amount', 'description', 'expense_date']));

        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/expenses/{id}',
        summary: 'Delete expense',
        description: 'Menghapus pengeluaran',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID pengeluaran', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pengeluaran berhasil dihapus',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Expense deleted successfully')
                ])
            ),
            new OA\Response(response: 404, description: 'Expense not found')
        ]
    )]
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully']);
    }

    #[OA\Get(
        path: '/api/v1/expenses/categories',
        summary: 'Get expense categories',
        description: 'Mendapatkan daftar kategori pengeluaran yang tersedia',
        security: [['sanctum' => []]],
        tags: ['Expenses'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'categories',
                            type: 'object',
                            example: [
                                'beli_stok' => 'Beli Stok',
                                'operasional' => 'Operasional',
                                'gaji' => 'Gaji',
                                'lainnya' => 'Lainnya'
                            ]
                        )
                    ]
                )
            )
        ]
    )]
    public function categories()
    {
        return response()->json([
            'categories' => Expense::getCategories()
        ]);
    }
}
