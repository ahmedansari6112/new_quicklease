<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApisFiles\UserController;
use App\Http\Controllers\ApisFiles\RolePermissionController;
use App\Http\Controllers\ApisFiles\MenuController;
use App\Http\Controllers\ApisFiles\WebContentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//  Route::get('/clear-cache', function() {
//   $exitCode = Artisan::call('cache:clear');
//   $exitCode = Artisan::call('route:clear');
//   $exitCode = Artisan::call('view:clear');
//   $exitCode = Artisan::call('config:clear');
//   dd('success');
// });

// Define your login API route
Route::post('login', [UserController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {
    
    // Define your registration API route
    Route::post('register', [UserController::class, 'register']);

    // Get a list of all users
    Route::get('/allUsers', [UserController::class, 'listUsers']);

    // Get a user profile data
    Route::get('/getUserProfile', [UserController::class, 'getProfile']);

    // Get a specific role with permissions by role ID
    Route::get('/userEdit/{id?}', [UserController::class, 'editUser']);

    // Update a user by ID
    Route::post('/userUpdate/{id}', [UserController::class, 'updateUser']);

    // Delete a user by ID
    Route::delete('/userDelete/{id}', [UserController::class, 'deleteUser']);

    // User Logout
    Route::post('logout', [UserController::class, 'logout']);
    
    // Define a route to roles
    Route::group(['prefix' => 'roles'], function() {  
        // Get all roles
        Route::get('/', [RolePermissionController::class, 'getAllRoles']);

        // Get all permissions
        Route::get('/allPermissions', [RolePermissionController::class, 'getAllPermissions']);

        // Create a role with permissions
        Route::post('/create', [RolePermissionController::class, 'createRoleWithPermissions']);

        // Get a specific role with permissions by role ID
        Route::get('/edit/{roleId}', [RolePermissionController::class, 'editRoleWithPermissions']);

        // Update a specific role with permissions by role ID
        Route::post('/update/{roleId}', [RolePermissionController::class, 'updateRoleWithPermissions']);

        //Delete role with permissions by role ID
        Route::delete('/remove/{roleId}', [RolePermissionController::class, 'deleteRole']);
        
        // Create Permissions
        Route::post('/add-permissions', [RolePermissionController::class, 'addPermissions']);
        
        //Delete Group with inner permission
        Route::post('/delete-permission-group', [RolePermissionController::class, 'deletePermissionGroup']);

    });

    // Define a route to Menus
    Route::group(['prefix' => 'menus', 'middleware' => 'validateLang'], function() {

        // Get all menus
        Route::get('/getAll/{lang?}/{type?}', [MenuController::class, 'getAllMenus']);

        // Get all menus
        Route::get('/formList/{lang?}/{type?}', [MenuController::class, 'formMenuList']);


        // Create a menu
        Route::post('/create/{lang?}', [MenuController::class, 'createMenu']);

        // Get single menu data
        Route::get('/edit/{lang}/{id?}', [MenuController::class, 'editMenu']);

        // Update a menu
        Route::post('/update/{lang}/{id}', [MenuController::class, 'updateMenu']);
        

        // Get all Object related menus
        Route::get('/objectPages/{id}', [MenuController::class, 'objectPages']);

    });

    // Web Content Resource
    Route::group(['prefix' => 'webContents', 'middleware' => 'validateLang'], function() {
        
        //Home content
        Route::post('/home/{lang}', [WebContentController::class,'createOrUpdateWebHome']);
        
        // Contact us content
        Route::post('/contact/{lang}', [WebContentController::class,'createOrUpdateWebContact']);
        
        // About Us Routes
        Route::post('/aboutUs/{lang?}', [WebContentController::class, 'saveOrUpdateWebAboutUsContent']);
        
        // Privacy policy 
        Route::post('/privacy_policy/{lang}', [WebContentController::class,'createOrUpdatePrivacyPolicy']);
        
        /* T&C content controller */
        Route::post('/terms_conditions/{lang}', [WebContentController::class,'createOrUpdateTermsConditions']);
        
    });
});
