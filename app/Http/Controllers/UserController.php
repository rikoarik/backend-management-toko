<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Get(
        path: '/api/v1/users',
        summary: 'List all users',
        description: 'Mendapatkan daftar semua user/kasir yang terdaftar',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', description: 'Nomor halaman', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'size', in: 'query', description: 'Jumlah item per halaman', required: false, schema: new OA\Schema(type: 'integer', example: 10))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar user berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                                new OA\Property(property: 'email', type: 'string', example: 'budi@example.com'),
                                new OA\Property(property: 'username', type: 'string', example: 'budi123'),
                                new OA\Property(property: 'role', type: 'string', example: 'kasir'),
                                new OA\Property(property: 'is_active', type: 'boolean', example: true),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                            ]
                        )),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 3),
                        new OA\Property(property: 'per_page', type: 'integer', example: 10),
                        new OA\Property(property: 'total', type: 'integer', example: 25),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated')
        ]
    )]
    public function index(Request $request)
    {
        $size = $request->input('size', 10);
        return response()->json(User::paginate($size));
    }

    #[OA\Get(
        path: '/api/v1/users/{id}',
        summary: 'Get user detail',
        description: 'Mendapatkan detail user berdasarkan ID',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID user', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail user',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso'),
                        new OA\Property(property: 'email', type: 'string', example: 'budi@example.com'),
                        new OA\Property(property: 'username', type: 'string', example: 'budi123'),
                        new OA\Property(property: 'role', type: 'string', example: 'kasir'),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-14T10:00:00.000000Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function show($id)
    {
        return response()->json(User::findOrFail($id));
    }

    #[OA\Put(
        path: '/api/v1/users/{id}',
        summary: 'Update user',
        description: 'Mengupdate data user (nama, password, status aktif). User hanya bisa update profile sendiri.',
        security: [['sanctum' => []]],
        tags: ['Users'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', description: 'ID user', required: true, schema: new OA\Schema(type: 'integer', example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso Update', description: 'Nama baru (opsional)'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'newpassword123', description: 'Password baru minimal 8 karakter (opsional)'),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'Status aktif user (opsional)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'User berhasil diupdate',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
                    new OA\Property(
                        property: 'user',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'name', type: 'string', example: 'Budi Santoso Update'),
                            new OA\Property(property: 'email', type: 'string', example: 'budi@example.com'),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        ]
                    ),
                ])
            ),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation Error')
        ]
    )]
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'password' => 'nullable|string|min:8',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->has('name'))
            $user->name = $request->name;
        if ($request->has('is_active'))
            $user->is_active = $request->is_active;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Pengguna berhasil diperbarui',
            'user' => $user
        ]);
    }

}
