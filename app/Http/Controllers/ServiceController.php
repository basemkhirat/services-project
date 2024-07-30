<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{

    /**
     * @OA\Get(
     *     tags={"Services"},
     *     path="/api/services",
     *     summary="List all services",
     *     @OA\Parameter(
     *         description="Limit",
     *         in="query",
     *         name="limit",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         description="Current Page",
     *         in="query",
     *         name="page",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         description="Order: latest or oldest",
     *         in="query",
     *         name="order",
     *         @OA\Schema(default="latest"),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function index(Request $request)
    {
        $cache_tag = "services";
        $cache_key = $this->getRequestKey($request);

        if (Cache::tags($cache_tag)->has($cache_key)) {
            $services =  Cache::tags($cache_tag)->get($cache_key);
        } else {

            $limit = $request->get("limit", 10);
            $order = $request->get("order", "latest");

            if ($limit > 100) $limit = 100;

            $query = Service::query();

            if ($order == "latest") {
                $query->orderBy("created_at", "desc");
            }

            if ($order == "oldest") {
                $query->orderBy("created_at", "asc");
            }

            $services = $query->paginate($limit);

            Cache::tags($cache_tag)->forever($cache_key, $services);
        }

        return response()->json([
            "message" => "Success",
            "code" => 200,
            "status" => true,
            "data" => $services
        ], 200);
    }


    /**
     * @OA\Get(
     *     tags={"Customers"},
     *     path="/api/customers/{customer_id}/services",
     *     summary="List all services of a single customer",
     *     @OA\Parameter(
     *         description="customer_id",
     *         in="path",
     *         name="customer_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         description="Limit",
     *         in="query",
     *         name="limit",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         description="Current Page",
     *         in="query",
     *         name="page",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Parameter(
     *         description="Order: latest or oldest",
     *         in="query",
     *         name="order",
     *        @OA\Schema(default="latest"),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function getCustomerServices(int $customer_id, Request $request)
    {
        $cache_tag = "services";
        $cache_key = "customer-" . $customer_id . "-services";

        if (Cache::tags($cache_tag)->has($cache_key)) {
            $services =  Cache::tags($cache_tag)->get($cache_key);
        } else {

            $customer = Customer::where("id", $customer_id)->first();

            if (!$customer) {
                return response()->json([
                    "message" => "Customer not found",
                    "code" => 404,
                    "status" => false
                ], 404);
            }

            $limit = $request->get("limit", 10);
            $order = $request->get("order", "latest");

            if ($limit > 100) $limit = 100;

            $query = $customer->services();

            if ($order == "latest") {
                $query->orderBy("created_at", "desc");
            }

            if ($order == "oldest") {
                $query->orderBy("created_at", "asc");
            }

            $services = $query->paginate($limit);

            Cache::tags($cache_tag)->forever($cache_key, $services);
        }

        return response()->json([
            "message" => "Success",
            "code" => 200,
            "status" => true,
            "data" => $services
        ], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Services"},
     *     path="/api/services",
     *     summary="create a new service",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"customer_id"},
     *                 @OA\Property( property="customer_id", default="", description="customer id"),
     *                 @OA\Property( property="name", default="", description="service name"),
     *                 @OA\Property( property="description", default="", description="service description"),
     *                 @OA\Property( property="price", default="", description="service price")
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response="201", description="created"),
     *     @OA\Response(response="422", description="validation error"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation Error",
                "code" => 422,
                "errors" => $validator->errors()->all()
            ]);
        }

        $service = new Service();

        $service->customer_id = $request->customer_id;
        $service->name = $request->name;
        $service->description = $request->description;
        $service->price = $request->price;

        $service->save();

        Cache::tags('services')->flush();

        return response()->json([
            "message" => "Service created successfully",
            "code" => 201,
            "status" => true,
            "data" => $service
        ], 201);
    }


    /**
     * @OA\Put(
     *     tags={"Services"},
     *     path="/api/services/{service_id}",
     *     summary="update service",
     *     @OA\Parameter(
     *         description="service id",
     *         in="path",
     *         name="service_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"customer_id"},
     *                 @OA\Property( property="name", default="", description="service name"),
     *                 @OA\Property( property="description", default="", description="service description"),
     *                 @OA\Property( property="price", default="", description="service price")
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     @OA\Response(response="422", description="validation error"),
     *     @OA\Response(response="404", description="not found"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function update($service_id, Request $request)
    {
        $service = Service::where("id", $service_id)->first();

        if (!$service) {
            return response()->json([
                "message" => "Service not found",
                "code" => 404,
                "status" => false
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation Error",
                "code" => 422,
                "errors" => $validator->errors()->all()
            ]);
        }

        if ($request->filled("name")) {
            $service->name = $request->name;
        }

        if ($request->filled("description")) {
            $service->description = $request->description;
        }

        if ($request->filled("price")) {
            $service->price = $request->price;
        }

        $service->save();

        Cache::tags('services')->flush();

        return response()->json([
            "message" => "Service updated successfully",
            "code" => 200,
            "status" => true,
            "data" => $service
        ], 200);
    }


    /**
     * @OA\Delete(
     *     tags={"Services"},
     *     path="/api/services/{service_id}",
     *     summary="delete service",
     *     @OA\Parameter(
     *         description="service id",
     *         in="path",
     *         name="service_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(response="204", description="deleted"),
     *     @OA\Response(response="404", description="not found"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function destroy(int $service_id)
    {
        $service = Service::where("id", $service_id)->first();

        if (!$service) {
            return response()->json([
                "message" => "Service not found",
                "code" => 404,
                "status" => false
            ], 404);
        }

        $service->delete();

        Cache::tags('services')->flush();
        Cache::tags('customers')->flush();

        return response()->json([
            "message" => "Service deleted successfully",
            "code" => 204,
            "status" => true,
            "data" => null
        ], 204);
    }
}
