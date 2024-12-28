<?php

namespace App\Http\Controllers\ApisFiles;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
        $this->middleware('permission:User View', ['only' => ['listUsers']]);
        $this->middleware('permission:User Add', ['only' => ['register']]);
        $this->middleware('permission:User Edit', ['only' => ['editUser','updateUser']]);
        $this->middleware('permission:User Delete', ['only' => ['deleteUser']]);
    }

    public function login(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        // Attempt to log the user in
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            // Authentication passed, get the authenticated user
            $user = Auth::user();
            
            // Get user roles
            $role = $user->getRoleNames()->first();
            $profile_image = $user->profile_image ? $this->getImageUrl($user->profile_image) : null;

            // Generate a token (you can use Laravel Sanctum or JWT here)
            $token = $user->createToken('authToken')->plainTextToken; // For Sanctum

            return response()->json([
                'status' => true,
                "message" => "You have logged in successfully",
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'profile_image' => $profile_image,
                    'user_enabled' => $user->user_enabled,
                    'role' => $role,
                    'api_token' => $token
                ],
            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'Credentials do not match!'
            ], 200);
        }        
    }

    public function register(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|confirmed', // Ensure password confirmation
            'role_id' => 'required|exists:roles,id', 
            'user_enabled' => 'required|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        // Handle profile image upload
        $profile_image = null;
        if ($request->hasFile('profile_image')) {    
            // Store the new profile image
            $file = $request->file('profile_image');
            $path = $file->store('profile_images', 'public');
            $profile_image = $path;
        }
      
        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), 
            'user_enabled' => $request->user_enabled,
            'profile_image' => $profile_image
        ]);

        // Fetch the role by role_id
        $role = Role::find($request->role_id);

        // Assign the role to the user
        if ($role) {
            $user->assignRole($role);
        }

        // Generate an API token (if using Sanctum)
        $token = $user->createToken('authToken')->plainTextToken;

        // Return a response
        return response()->json([
            'status' => true,
            'message' => 'User registered successfully!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role ? $role->name : null,
                'user_enabled' => (int) $user->user_enabled, 
                'api_token' => $token,
                'profile_image' => $profile_image
            ],
        ], 200); // HTTP 201 Created
    }


    public function getProfile(Request $request)
    {
        // Get the authenticated user
        $user = $request->user(); 

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated!',
            ], 200);
        }

        // Get user roles and profile image
        $role = $user->roles->first(); // Access the roles relationship
        $role_id = $role ? $role->id : null;
        $profile_image = $user->profile_image ? $this->getImageUrl($user->profile_image) : null;

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $profile_image,
                'user_enabled' => $user->user_enabled,
                'role_id' => $role_id,
            ],
        ], 200);
    }


    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => true,
            'message' => 'Successfully logged out'
        ], 200);
    }

    public function listUsers($per_page = 10)
    {
        $users = User::with('roles')
                ->where('id','!=',1)
                ->orderBy('created_at','DESC')
                ->get(); // Assuming a User has a 'roles' relationship

        // Check if users exist
        if ($users->isEmpty()) {
            return response()->json([
                "status" => false,
                'message' => 'No users found.'
            ], 200); // Return 404 Not Found if no permissions exist
        }

        $userList = $users->map(function ($user) {
            $profile_image = $user->profile_image ? $this->getImageUrl($user->profile_image) : null;
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $profile_image,
                'role' => $user->roles->pluck('name')->first(), // Fetch first role name
                'user_enabled' => (int) $user->user_enabled,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'User list fetched successfully!',
            'data' => $userList
        ], 200);
    }

    public function editUser($id)
    {
        // Find the user by ID
        $user = User::with('roles')
                ->where('id','!=',1)
                ->find($id);
        
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 200);
        }

        $profile_image = $user->profile_image ? $this->getImageUrl($user->profile_image) : null;
        
        return response()->json([
            'status' => true,
            'message' => 'User details fetched successfully!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_image' => $profile_image,
                'role' => $user->roles->pluck('id')->first(), // Fetch first role name
                'user_enabled' => (int) $user->user_enabled,
            ]
        ], 200);
    }


    public function updateUser(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|string|email|unique:users,email,' . $id, // Exclude current user email from unique check
            'password' => 'sometimes|nullable|string|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'user_enabled' => 'sometimes|numeric',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 200);
        }

        // Find the user by ID
        $user = User::find($id);
        
        if (!$user) {
            return response()->json(["status" => false, 'message' => 'User not found'], 200);
        }

        // Update user details
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }
        if ($request->has('user_enabled')) {
            $user->user_enabled = $request->user_enabled;
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete the old profile image if it exists
            if ($user->profile_image && Storage::exists($user->profile_image)) {
                Storage::delete($user->profile_image);
            }
    
            // Store the new profile image
            $file = $request->file('profile_image');
            $path = $file->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        $user->save();

        // Update user role if provided
        if ($request->has('role_id')) {
            $role = Role::find($request->role_id);
            if ($role) {
                $user->syncRoles($role); // Syncing new role
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'User updated successfully!',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first(),
                'user_enabled' => (int) $user->user_enabled
            ]
        ], 200);
    }
    
    public function deleteUser($id)
    {
        $user = User::where('id','!=',1)
                ->find($id);

        if (!$user) {
            return response()->json(["status" => false, 'message' => 'User not found!'], 200);
        }

        // Delete the old profile image if it exists
        if ($user->profile_image && Storage::exists($user->profile_image)) {
            Storage::delete($user->profile_image);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully!'
        ], 200);
    }

    public function getImageUrl($image_path)
    {
        $image_path = Storage::url($image_path);
        $image_url = asset("public/".$image_path);
        return $image_url;
    }
}
