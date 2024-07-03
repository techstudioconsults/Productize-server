<?php

use App\Http\Controllers\UserController;
use App\Models\Account;
use App\Models\Product;
use App\Models\User;
use App\Notifications\PayoutCardAdded;
use App\Notifications\ProductPublished;
// use App\Notifications\FirstProductCreated;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;

Route::group([
    'as' => 'users.',
    'namespace' => "\App\Http\Controllers",
    'prefix' => 'users',
    'middleware' => ['auth:sanctum', 'can:verified,App\Models\User'],
], function () {
    Route::get('/', [UserController::class, 'index'])->middleware('abilities:role:super_admin')->name('index');

    Route::get('/stats/admin', [UserController::class, 'stats'])->middleware('abilities:role:super_admin')->name('stats.admin');

    Route::get('/me', [UserController::class, 'show'])->withoutMiddleware('can:verified,App\Models\User');

    Route::get('/download', [UserController::class, 'download'])->middleware('abilities:role:super_admin')->name('download');

    Route::post('/', [UserController::class, 'store'])->middleware('abilities:role:super_admin')->name('store');

    Route::post('/me', [UserController::class, 'update']);

    Route::post('/change-password', [UserController::class, 'changePassword']);

    Route::patch('/{user}/revoke-admin-role', [UserController::class, 'revokeAdminRole'])
        ->middleware('abilities:role:super_admin')
        ->name('users.revoke-admin-role');

    Route::post('/kyc', [UserController::class, 'updateKyc'])->name('kyc');

    // Route::get("/test", function() {
    //     $user = User::find('9c5edc2c-28b3-4441-a4e7-8a9178eb4361');
    //     // $product = Product::where("user_id", $user->id)->first();
    //     $account = Account::where("user_id", $user->id)->first();

    //     // Notification::send($user, new WelcomeNotification($user));

    //     // $user->notify(new ProductPublished($product));

    //     $user->notify(new PayoutCardAdded($account));

    //     return new JsonResource($user->notifications);
    // });
});
