<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $status = 200,
        ?array $meta = null,
    ): JsonResponse {
        $body = ['success' => true, 'message' => $message, 'data' => $data];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $status);
    }

    public static function error(string $message, ?array $errors = null, int $status = 400): JsonResponse
    {
        $body = ['success' => false, 'message' => $message];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
        string $message = 'OK',
    ): JsonResponse {
        return self::success(
            $resourceClass::collection($paginator->items())->resolve(),
            $message,
            200,
            [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'total_pages' => $paginator->lastPage(),
            ],
        );
    }
}
