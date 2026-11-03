<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    /** @param array<string, array<int, string>> $errors */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Stok tidak mencukupi');
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->errors, 409);
    }
}
