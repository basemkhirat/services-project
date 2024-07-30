<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{

    /**
     * @OA\Get(
     *     tags={"Customers"},
     *     path="/api/customers",
     *     summary="List all customers",
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
        $cache_tag = "customers";
        $cache_key = $this->getRequestKey($request);

        if (Cache::tags($cache_tag)->has($cache_key)) {
            $customers =  Cache::tags($cache_tag)->get($cache_key);
        } else {

            $limit = $request->get("limit", 10);
            $order = $request->get("order", "latest");

            if ($limit > 100) $limit = 100;

            $query = Customer::query();

            if ($order == "latest") {
                $query->orderBy("created_at", "desc");
            }

            if ($order == "oldest") {
                $query->orderBy("created_at", "asc");
            }

            $customers = $query->paginate($limit);

            Cache::tags($cache_tag)->forever($cache_key, $customers);
        }

        return response()->json([
            "message" => "Success",
            "code" => 200,
            "status" => true,
            "data" => $customers
        ], 200);
    }

    /**
     * @OA\Get(
     *     tags={"Customers"},
     *     path="/api/customers/{customer_id}",
     *     summary="List all customers",
     *     @OA\Parameter(
     *         description="show single customer",
     *         in="path",
     *         name="customer_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     @OA\Response(response="404", description="not found"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function show(int $customer_id)
    {
        $cache_tag = "customers";
        $cache_key = "customer-" . $customer_id;

        if (Cache::tags($cache_tag)->has($cache_key)) {
            $customer = Cache::tags($cache_tag)->get($cache_key);
        } else {

            $customer = Customer::where("id", $customer_id)->first();

            if (!$customer) {
                return response()->json([
                    "message" => "Customer not found",
                    "code" => 404,
                    "status" => false
                ], 404);
            }

            Cache::tags($cache_tag)->forever($cache_key, $customer);
        }

        return response()->json([
            "message" => "Success",
            "code" => 200,
            "status" => true,
            "data" => $customer
        ], 200);
    }

    /**
     * @OA\Post(
     *     tags={"Customers"},
     *     path="/api/customers",
     *     summary="create new customer",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property( property="name", default="", description="customer name"),
     *                 @OA\Property( property="email", default="", description="customer email"),
     *                 @OA\Property( property="phone", default="", description="customer phone"),
     *                 @OA\Property( property="address", default="", description="customer address")
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation Error",
                "code" => 422,
                "errors" => $validator->errors()->all()
            ]);
        }

        $customer = new Customer();

        $customer->name = $request->name;
        $customer->email = $request->email;
        $customer->phone = $request->phone;
        $customer->address = $request->address;

        $customer->save();

        Cache::tags('customers')->flush();

        return response()->json([
            "message" => "Customer created successfully",
            "code" => 201,
            "status" => true,
            "data" => $customer
        ], 201);
    }

    /**
     * @OA\Put(
     *     tags={"Customers"},
     *     path="/api/customers/{customer_id}",
     *     summary="update customer",
     *     @OA\Parameter(
     *         description="customer id",
     *         in="path",
     *         name="customer_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property( property="name", default="", description="customer name"),
     *                 @OA\Property( property="email", default="", description="customer email"),
     *                 @OA\Property( property="phone", default="", description="customer phone"),
     *                 @OA\Property( property="address", default="", description="customer address")
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     @OA\Response(response="422", description="validation error"),
     *     @OA\Response(response="404", description="not found"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function update($customer_id, Request $request)
    {
        $customer = Customer::where("id", $customer_id)->first();

        if (!$customer) {
            return response()->json([
                "message" => "Customer not found",
                "code" => 404,
                "status" => false
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'email',
                Rule::unique('customers')->ignore($customer->id),
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation Error",
                "code" => 422,
                "errors" => $validator->errors()->all()
            ]);
        }

        if ($request->get("name")) {
            $customer->name = $request->name;
        }

        if ($request->get("email")) {
            $customer->email = $request->email;
        }

        if ($request->get("phone")) {
            $customer->phone = $request->phone;
        }

        if ($request->get("address")) {
            $customer->address = $request->address;
        }

        $customer->save();

        Cache::tags('customers')->forget("customer-" . $customer_id);

        return response()->json([
            "message" => "Customer updated successfully",
            "code" => 200,
            "status" => true,
            "data" => $customer
        ], 200);
    }

    /**
     * @OA\Delete(
     *     tags={"Customers"},
     *     path="/api/customers/{customer_id}",
     *     summary="delete customer",
     *     @OA\Parameter(
     *         description="customer id",
     *         in="path",
     *         name="customer_id",
     *         @OA\Schema(type="number"),
     *     ),
     *     @OA\Response(response="204", description="deleted"),
     *     @OA\Response(response="404", description="not found"),
     *     security={ {"bearer": {}} },
     * )
     */
    public function destroy(int $customer_id)
    {
        $customer = Customer::where("id", $customer_id)->first();

        if (!$customer) {
            return response()->json([
                "message" => "Customer not found",
                "code" => 404,
                "status" => false
            ], 404);
        }

        $customer->delete();

        Cache::tags('customers')->flush();
        Cache::tags('services')->flush();

        return response()->json([
            "message" => "Customer deleted successfully",
            "code" => 204,
            "status" => true,
            "data" => null
        ], 204);
    }
}
