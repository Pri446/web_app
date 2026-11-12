<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{

    /**
     * Login The User
     * @param Request $request
     * @return JsonResponse
     */

     public function login(Request $request)
     {
         try {
             // Validate the request
             $validateUser = Validator::make($request->all(), [
                 'email' => 'required|email',
                 'password' => 'required'
             ]);
     
             if ($validateUser->fails()) {
                 return response()->json([
                     'status' => false,
                     'message' => __('Validation Error!'),
                     'errors' => $validateUser->errors()
                 ], 401);
             }
     
             $email = mb_strtolower(trim($request->input('email')));
             $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

             if (
                 !$user
                 || (int) $user->status !== 1
                 || !Hash::check($request->input('password'), $user->password)
             ) {
                 return response()->json([
                     'status' => false,
                     'message' => __('Email & Password do not match our records.'),
                 ], 401);
             }
     
             // Define the roles to check
             $allowedRoles = [
                 'Super Admin',
                 'Municipality - Super Admin',
                 'Municipality - Building Surveyor',
                 'Municipality - Infrastructure Department',
                 'Service Provider - Emptying Operator',
                 'Treatment Plant - Admin'
             ];
     
             // Check if the user has any of the allowed roles
             if (!$user->hasAnyRole($allowedRoles)) {
                 return response()->json([
                     'status' => false,
                     'message' => __('Unauthorized: You do not have the required role to log in.'),
                 ], 403);
             }
     
             // Generate the token and return success response
             return response()->json([
                 'status' => true,
                 'message' => __('User Logged In Successfully.'),
                 'token' => $user->createToken("API TOKEN")->plainTextToken,
                 'data' => [
                     "name" => $user->name,
                     "gender" => $user->gender,
                     "treatment_plant" => $user->treatment_plant->name ?? null,
                     "help_desk" => $user->help_desk->name ?? null,
                     "service_provider" => $user->service_provider->company_name ?? null,
                     "permissions" => [
                         "building-survey" => (bool)$user->can('Access Building Survey API'),
                         "save-emptying-service" => (bool)$user->can('Access Emptying Service API'),
                         "sewer-connection" => (bool)$user->can('Access Sewer Connection API'),
                         "sludge-collection" => (bool)$user->can('Access Sludge Collection API'),
                     ],
                 ]
             ]);
         } catch (\Throwable $th) {
             return response()->json([
                 'status' => false,
                 'message' => $th->getMessage()
             ], 500);
         }
     }
     

    public function logout()
    {
        try {
            Auth::user()->tokens()->delete();
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
        return [
            'success' => true,
            'message' => __('Logged out successfully.'),
        ];
    }

    public function footer()
    {
        return response()->json([
           'Base IMIS © 2022-' . Carbon::now()->format('Y') .
                    ' by ISPL & GWSC-AIT (GWSC-AIT) is licensed under CC BY-NC-SA 4.0 | Developed by: Innovative Solution Pvt. Ltd.'

        ]);
    }
}
