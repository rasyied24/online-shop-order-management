<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerIndexRequest;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(CustomerIndexRequest $request): JsonResponse
    {
        $customers = Customer::query()
            ->search($request->validated('search'))
            ->orderBy('customer_name')
            ->orderBy('id')
            ->paginate((int) $request->validated('per_page', 10));

        return ApiResponse::paginated($customers, CustomerResource::class);
    }

    public function store(CustomerRequest $request): JsonResponse
    {
        $customer = Customer::create($request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Pelanggan berhasil ditambahkan', 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return ApiResponse::success(new CustomerResource($customer));
    }

    public function update(CustomerRequest $request, Customer $customer): JsonResponse
    {
        $customer->update($request->validated());

        return ApiResponse::success(new CustomerResource($customer), 'Pelanggan berhasil diperbarui');
    }

    public function destroy(Customer $customer): Response
    {
        $customer->delete();

        return response()->noContent();
    }
}
