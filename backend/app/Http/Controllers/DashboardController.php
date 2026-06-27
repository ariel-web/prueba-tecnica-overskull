<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    #[OA\Get(
        path: '/dashboard',
        summary: 'Métricas del dashboard',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Métricas', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'products', type: 'integer', example: 10000),
                    new OA\Property(property: 'categories', type: 'integer', example: 100),
                    new OA\Property(property: 'low_stock', type: 'array', items: new OA\Items(ref: '#/components/schemas/Product')),
                    new OA\Property(property: 'last_movements', type: 'array', items: new OA\Items(ref: '#/components/schemas/StockMovement')),
                ]
            )),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function index(): JsonResponse
    {
        return response()->json($this->dashboardService->metrics());
    }

    #[OA\Get(
        path: '/health',
        summary: 'Health check',
        tags: ['Dashboard'],
        responses: [
            new OA\Response(response: 200, description: 'OK', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'ok'),
                    new OA\Property(property: 'database', type: 'string', example: 'connected'),
                ]
            )),
        ]
    )]
    public function health(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return response()->json(['status' => 'ok', 'database' => 'connected']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'fail', 'error' => $e->getMessage()], 500);
        }
    }
}
