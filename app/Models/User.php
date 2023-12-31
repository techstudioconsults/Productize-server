<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasUuids; // I am using this so User primary ids are uuids

    /**
     * All three have to be included in other to use uuids as ids
     */
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Customizing Reset Password Notification
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'full_name',
        'username',
        'phone_number',
        'bio',
        'logo',
        'twitter_account',
        'facebook_account',
        'youtube_account'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function isPremium()
    {
        return $this->account_type === 'premium' ? true : false;
    }

    public function subaccounts(): HasMany
    {
        return $this->hasMany(Subaccounts::class);
    }

    public function hasSubaccount(): bool
    {
        return $this->subaccounts()->exists();
    }

    public function activeSubaccount()
    {
        return $this->subaccounts()->where('active', 1)->first();
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Sale::class, Product::class);
    }

    public function purchases()
    {
        return $this->hasManyThrough(Sale::class, Order::class, 'buyer_id', 'order_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'product_owner_id');
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Sale::class, 'customer_id');
    }
}
