<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse {
        $query = Client::query();

        // Búsqueda rápida por nombre, teléfono o nif
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        $clients = $query->orderBy('name')->paginate(15);

        return response()->json($clients, 200);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
            'data' => $client,
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json([
            'data' => $client,
        ], 200);
    }

    public function update(StoreClientRequest $request, Client $client): JsonResponse
    {
        $client->update($request->validated());

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data' => $client,
        ], 200);
    }

    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ], 200);
    }
}
