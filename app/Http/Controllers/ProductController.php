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
            'message' => 'Product created successfully',
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
        description: 'Update data produk. Gunakan POST dengan _method=PUT untuk upload gambar (multipart/form-data)',
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
                        new OA\Property(property: 'stock', type: 'integer', example: 150, description: 'Stok baru'),
                        new OA\Property(property: 'barcode', type: 'string', example: '8992761100099', description: 'Barcode baru'),
                        new OA\Property(property: 'image', type: 'string', format: 'binary', description: 'Gambar baru (opsional)'),
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
                    new OA\Property(
                        property: 'product',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Teh Botol Sosro 500ml'),
                            new OA\Property(property: 'price', type: 'integer', example: 6000),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|integer|min:0',
            'stock' => 'sometimes|integer|min:0',
            'barcode' => 'nullable|string|unique:products,barcode,' . $id,
            'image' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['image', '_method']);

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

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
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

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
