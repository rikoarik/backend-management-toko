<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/v1/products',
        summary: 'List products (Paginated)',
        description: 'Mendapatkan daftar produk dengan pagination, bisa filter berdasarkan kategori atau pencarian',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'size', in: 'query', description: 'Jumlah item per halaman', required: false, schema: new OA\Schema(type: 'integer', example: 10)),
            new OA\Parameter(name: 'category_id', in: 'query', description: 'Filter berdasarkan ID kategori', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'search', in: 'query', description: 'Cari berdasarkan nama produk atau barcode', required: false, schema: new OA\Schema(type: 'string', example: 'Teh Botol')),
            new OA\Parameter(name: 'filter', in: 'query', description: 'Filter sorting: NEWEST (Terbaru), OLDEST (Terlama), STOCK_HIGH (Stok Terbanyak), STOCK_LOW (Stok Tersedikit), NAME_ASC (Nama A-z), NAME_DESC (Nama z-A)', required: false, schema: new OA\Schema(type: 'string', enum: ['NEWEST', 'OLDEST', 'STOCK_HIGH', 'STOCK_LOW', 'NAME_ASC', 'NAME_DESC'], example: 'NEWEST')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar produk berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'category_id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Teh Botol Sosro'),
                                new OA\Property(property: 'description', type: 'string', example: 'Minuman teh dalam kemasan botol 450ml'),
                                new OA\Property(property: 'price', type: 'integer', example: 5000),
                                new OA\Property(property: 'wholesale_price', type: 'integer', example: 4500),
                                new OA\Property(property: 'retail_price', type: 'integer', example: 5500),
                                new OA\Property(property: 'stock', type: 'integer', example: 100),
                                new OA\Property(property: 'barcode', type: 'string', example: '8992761100018'),
                                new OA\Property(property: 'image', type: 'string', example: 'products/teh-botol.jpg'),
                                new OA\Property(
                                    property: 'category',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 1),
                                        new OA\Property(property: 'name', type: 'string', example: 'Minuman'),
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
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('barcode', $search);
            });
        }

        // Apply sorting filter
        if ($request->filled('filter')) {
            switch ($request->filter) {
                case 'NEWEST':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'OLDEST':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'STOCK_HIGH':
                    $query->orderBy('stock', 'desc');
                    break;
                case 'STOCK_LOW':
                    $query->orderBy('stock', 'asc');
                    break;
                case 'NAME_ASC':
                    $query->orderBy('name', 'asc');
                    break;
                case 'NAME_DESC':
                    $query->orderBy('name', 'desc');
                    break;
            }
        } else {
            // Default sorting by newest
            $query->orderBy('created_at', 'desc');
        }

        $size = $request->input('size', 10);
        return response()->json($query->paginate($size));
    }

    #[OA\Post(
        path: '/api/v1/products',
        summary: 'Create a new product',
        description: 'Membuat produk baru dengan upload gambar (multipart/form-data)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['category_id', 'name', 'price'],
                    properties: [
                        new OA\Property(property: 'category_id', type: 'integer', example: 1, description: 'ID kategori produk (wajib)'),
                        new OA\Property(property: 'name', type: 'string', example: 'Aqua 600ml', description: 'Nama produk (wajib)'),
                        new OA\Property(property: 'description', type: 'string', example: 'Air mineral kemasan 600ml', description: 'Deskripsi produk'),
                        new OA\Property(property: 'price', type: 'integer', example: 4000, description: 'Harga jual (wajib)'),
                        new OA\Property(property: 'cost_price', type: 'integer', example: 3500, description: 'Harga beli/modal per unit'),
                        new OA\Property(property: 'wholesale_price', type: 'integer', example: 3800, description: 'Harga grosir'),
                        new OA\Property(property: 'retail_price', type: 'integer', example: 4500, description: 'Harga ecer'),
                        new OA\Property(property: 'stock', type: 'integer', example: 50, description: 'Stok awal (default: 0)'),
                        new OA\Property(property: 'barcode', type: 'string', example: '8992761100025', description: 'Barcode produk (unik)'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'File gambar produk (max 2MB, format: jpg/png)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Produk berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Product created successfully'),
                    new OA\Property(
                        property: 'product',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 10),
                            new OA\Property(property: 'category_id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Aqua 600ml'),
                            new OA\Property(property: 'price', type: 'integer', example: 4000),
                            new OA\Property(property: 'cost_price', type: 'integer', example: 3500),
                            new OA\Property(property: 'wholesale_price', type: 'integer', example: 3800),
                            new OA\Property(property: 'retail_price', type: 'integer', example: 4500),
                            new OA\Property(property: 'stock', type: 'integer', example: 50),
                            new OA\Property(property: 'barcode', type: 'string', example: '8992761100025'),
                            new OA\Property(property: 'image', type: 'string', example: 'products/abc123.jpg'),
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
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|integer|min:0',
            'cost_price' => 'nullable|integer|min:0',
            'wholesale_price' => 'nullable|integer|min:0',
            'retail_price' => 'nullable|integer|min:0',
            'stock' => 'integer|min:0',
            'barcode' => 'nullable|string|unique:products',
            'image' => 'nullable|image|max:2048', // Max 2MB
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store('products', 'public');
            $data['image'] = $path;
        }

        $product = Product::create($data);

        return response()->json([
            'message' => 'Produk berhasil dibuat',
            'product' => $product
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/products/{id}',
        summary: 'Get product detail',
        description: 'Mendapatkan detail produk berdasarkan ID termasuk data kategori',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID produk', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail produk',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'category_id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Teh Botol Sosro'),
                        new OA\Property(property: 'description', type: 'string', example: 'Minuman teh dalam kemasan botol 450ml'),
                        new OA\Property(property: 'price', type: 'integer', example: 5000),
                        new OA\Property(property: 'wholesale_price', type: 'integer', example: 4500),
                        new OA\Property(property: 'retail_price', type: 'integer', example: 5500),
                        new OA\Property(property: 'stock', type: 'integer', example: 100),
                        new OA\Property(property: 'barcode', type: 'string', example: '8992761100018'),
                        new OA\Property(property: 'image', type: 'string', example: 'products/teh-botol.jpg'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                        new OA\Property(
                            property: 'category',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Minuman'),
                                new OA\Property(property: 'description', type: 'string', example: 'Aneka minuman'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found')
        ]
    )]
    public function show($id)
    {
        return response()->json(Product::with('category')->findOrFail($id));
    }

    #[OA\Post(
        path: '/api/v1/products/{id}',
        summary: 'Update product',
        description: 'Update data produk. Gunakan POST dengan _method=PUT untuk upload gambar (multipart/form-data). Jika stok bertambah dan purchase_price disertakan, expense akan otomatis tercatat.',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID produk', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: '_method', type: 'string', example: 'PUT', description: 'Gunakan PUT untuk method spoofing'),
                        new OA\Property(property: 'category_id', type: 'integer', example: 2, description: 'ID kategori baru'),
                        new OA\Property(property: 'name', type: 'string', example: 'Teh Botol Sosro 500ml', description: 'Nama produk baru'),
                        new OA\Property(property: 'description', type: 'string', example: 'Teh botol kemasan besar', description: 'Deskripsi baru'),
                        new OA\Property(property: 'price', type: 'integer', example: 6000, description: 'Harga baru'),
                        new OA\Property(property: 'wholesale_price', type: 'integer', example: 5500, description: 'Harga grosir baru'),
                        new OA\Property(property: 'retail_price', type: 'integer', example: 6500, description: 'Harga ecer baru'),
                        new OA\Property(property: 'stock', type: 'integer', example: 150, description: 'Stok baru'),
                        new OA\Property(property: 'barcode', type: 'string', example: '8992761100099', description: 'Barcode baru'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Gambar baru (opsional)'),
                        new OA\Property(property: 'cost_price', type: 'integer', example: 5000, description: 'Harga beli/modal per unit baru'),
                        new OA\Property(property: 'purchase_price', type: 'integer', example: 500000, description: 'Total harga beli jika restock (opsional, untuk otomatis catat expense)'),
                        new OA\Property(property: 'supplier', type: 'string', example: 'Supplier ABC', description: 'Nama supplier (opsional)'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produk berhasil diupdate',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Product updated successfully'),
                    new OA\Property(property: 'product', type: 'object'),
                    new OA\Property(property: 'expense', type: 'object', nullable: true, description: 'Expense yang tercatat jika ada restock'),
                ])
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $oldStock = $product->stock;

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|integer|min:0',
            'wholesale_price' => 'nullable|integer|min:0',
            'retail_price' => 'nullable|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'barcode' => 'nullable|string|unique:products,barcode,' . $id,
            'image' => 'nullable|image|max:2048',
            'cost_price' => 'nullable|integer|min:0',
            'purchase_price' => 'nullable|integer|min:1',
            'supplier' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['image', '_method', 'purchase_price', 'supplier']);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $file = $request->file('image');
            $path = $file->store('products', 'public');
            $data['image'] = $path;
        }

        $product->update($data);

        // Auto create expense if stock increased and purchase_price is provided
        $expense = null;
        $newStock = $product->stock;
        $stockAdded = $newStock - $oldStock;

        if ($stockAdded > 0 && $request->filled('purchase_price')) {
            $description = "Restock {$product->name} (+{$stockAdded} pcs)";
            if ($request->supplier) {
                $description .= " dari {$request->supplier}";
            }

            $expense = \App\Models\Expense::create([
                'user_id' => auth()->id(),
                'category' => \App\Models\Expense::CATEGORY_STOCK,
                'amount' => $request->purchase_price,
                'description' => $description,
                'expense_date' => now()->toDateString(),
            ]);

            // Auto update cost_price based on purchase_price / added stock
            $newCostPrice = (int) ($request->purchase_price / $stockAdded);
            $product->update(['cost_price' => $newCostPrice]);
        }

        $response = [
            'message' => 'Produk berhasil diperbarui',
            'product' => $product
        ];

        if ($expense) {
            $response['expense'] = $expense;
            $response['message'] = 'Produk berhasil diperbarui dan pengeluaran tercatat';
        }

        return response()->json($response);
    }

    #[OA\Delete(
        path: '/api/v1/products/{id}',
        summary: 'Delete product',
        description: 'Menghapus produk dan gambar terkait',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID produk', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produk berhasil dihapus',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Product deleted successfully')
                ])
            ),
            new OA\Response(response: 404, description: 'Product not found')
        ]
    )]
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Produk berhasil dihapus']);
    }

    #[OA\Post(
        path: '/api/v1/products/{id}/restock',
        summary: 'Restock product',
        description: 'Menambah stok produk dan otomatis mencatat pengeluaran (expense) dengan kategori beli_stok',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID produk', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity', 'purchase_price'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', description: 'Jumlah stok yang ditambahkan', example: 50),
                    new OA\Property(property: 'purchase_price', type: 'integer', description: 'Total harga beli (dalam Rupiah)', example: 500000),
                    new OA\Property(property: 'supplier', type: 'string', description: 'Nama supplier (opsional)', example: 'Supplier ABC'),
                    new OA\Property(property: 'notes', type: 'string', description: 'Catatan tambahan (opsional)', example: 'Beli stok untuk bulan Februari'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Stok berhasil ditambahkan dan expense tercatat',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Stock added and expense recorded successfully'),
                        new OA\Property(property: 'product', type: 'object'),
                        new OA\Property(property: 'expense', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function restock(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|integer|min:1',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update product stock
        $oldStock = $product->stock;
        $product->stock += $request->quantity;
        $product->save();

        // Build expense description
        $description = "Restock {$product->name} (+{$request->quantity} pcs)";
        if ($request->supplier) {
            $description .= " dari {$request->supplier}";
        }
        if ($request->notes) {
            $description .= ". {$request->notes}";
        }

        // Create expense automatically
        $expense = \App\Models\Expense::create([
            'user_id' => auth()->id(),
            'category' => \App\Models\Expense::CATEGORY_STOCK,
            'amount' => $request->purchase_price,
            'description' => $description,
            'expense_date' => now()->toDateString(),
        ]);

        // Auto update cost_price
        $newCostPrice = (int) ($request->purchase_price / $request->quantity);
        $product->update(['cost_price' => $newCostPrice]);

        return response()->json([
            'message' => 'Stok berhasil ditambahkan dan pengeluaran tercatat',
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'old_stock' => $oldStock,
                'added_quantity' => $request->quantity,
                'new_stock' => $product->stock,
            ],
            'expense' => $expense,
        ]);
    }
}

