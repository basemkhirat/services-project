<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    /**
     * @OA\Post(
     *     tags={"Authentication"},
     *     path="/api/auth/login",
     *     summary="Authenticate with email and password",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email","password"},
     *                 @OA\Property( property="email", default="", description="user email"),
     *                 @OA\Property( property="password", default="", description="user password"),
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     @OA\Response(response="401", description="invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized',
                'code' => 401,
                'status' => false
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful',
            'code' => 200,
            'status' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     tags={"Authentication"},
     *     path="/api/auth/register",
     *     summary="Create new superuser",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email","password","name"},
     *                 @OA\Property( property="email", default="", description="user email"),
     *                 @OA\Property( property="password", default="", description="user password"),
     *                 @OA\Property( property="name", default="", description="user name")
     *             ),
     *         ),
     *     ),
     *     @OA\Response(response="200", description="success"),
     *     @OA\Response(response="422", description="validation error"),
     *     @OA\Response(response="401", description="invalid credentials")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name' => 'required|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation Error",
                "code" => 422,
                "errors" => $validator->errors()->all()
            ]);
        }

        $user = new User();

        $user->email = $request->get("email");
        $user->password = app('hash')->make($request->get("password"));
        $user->name = $request->get("name");

        $user->save();

        $token = auth("api")->attempt([
            "email" => $request->get("email"),
            "password" => $request->get("password")
        ]);

        return response()->json([
            'message' => 'Registered successfully',
            'code' => 200,
            'status' => true,
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
                'user' => $user,
            ]
        ]);
    }
}
