<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/categories',
        summary: 'List categories (Paginated)',
        description: 'Mendapatkan daftar kategori produk dengan pagination',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'size', in: 'query', description: 'Jumlah item per halaman', required: false, schema: new OA\Schema(type: 'integer', example: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar kategori berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Minuman'),
                                new OA\Property(property: 'description', type: 'string', example: 'Aneka minuman dingin dan hangat'),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                                new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                            ]
                        )),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request)
    {
        $size = $request->input('size', 10);
        return response()->json(Category::paginate($size));
    }

    #[OA\Post(
        path: '/api/v1/categories',
        summary: 'Create a new category',
        description: 'Membuat kategori produk baru',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Makanan Ringan', description: 'Nama kategori (wajib, unik)'),
                    new OA\Property(property: 'description', type: 'string', example: 'Snack dan camilan untuk cemilan', description: 'Deskripsi kategori (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Kategori berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Category created successfully'),
                    new OA\Property(
                        property: 'category',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 5),
                            new OA\Property(property: 'name', type: 'string', example: 'Makanan Ringan'),
                            new OA\Property(property: 'description', type: 'string', example: 'Snack dan camilan untuk cemilan'),
                            new OA\Property(property: 'created_at', type: 'string', example: '2024-01-14T10:00:00.000000Z'),
                            new OA\Property(property: 'updated_at', type: 'string', example: '2024-01-14T10:00:00.000000Z'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 422, description: 'Validation Error - nama sudah digunakan atau kosong')
        ]
    )]
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories|max:255',
            'description' => 'nullable|string',
        ]);

        $category = Category::create($request->all());

        return response()->json([
            'message' => 'Kategori berhasil dibuat',
            'category' => $category
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/categories/{id}',
        summary: 'Get category detail',
        description: 'Mendapatkan detail kategori berdasarkan ID',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID kategori', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail kategori',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Minuman'),
                        new OA\Property(property: 'description', type: 'string', example: 'Aneka minuman dingin dan hangat'),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found')
        ]
    )]
    public function show($id)
    {
        return response()->json(Category::findOrFail($id));
    }

    #[OA\Put(
        path: '/api/v1/categories/{id}',
        summary: 'Update category',
        description: 'Mengupdate data kategori',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID kategori', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Minuman Segar', description: 'Nama kategori baru'),
                    new OA\Property(property: 'description', type: 'string', example: 'Aneka minuman segar dan menyehatkan', description: 'Deskripsi baru'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategori berhasil diupdate',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Category updated successfully'),
                    new OA\Property(
                        property: 'category',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Minuman Segar'),
                            new OA\Property(property: 'description', type: 'string', example: 'Aneka minuman segar dan menyehatkan'),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255|unique:categories,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $category->update($request->all());

        return response()->json([
            'message' => 'Kategori berhasil diperbarui',
            'category' => $category
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/categories/{id}',
        summary: 'Delete category',
        description: 'Menghapus kategori berdasarkan ID',
        security: [['sanctum' => []]],
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID kategori', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Kategori berhasil dihapus',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Category deleted successfully')
                ])
            ),
            new OA\Response(response: 404, description: 'Category not found')
        ]
    )]
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Kategori berhasil dihapus']);
    }
}
