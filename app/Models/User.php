<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

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
        'youtube_account',
        'alt_email',
        'product_creation_notification',
        'purchase_notification',
        'news_and_update_notification',
        'payout_notification',
        'country',
        'document_type',
        'document_image',
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

    public function isPremium()
    {
        return ($this->account_type === 'premium' || $this->account_type === 'free_trial') ? true : false;
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasManyThrough(Order::class, Product::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'merchant_id')
            ->latestOfMany('user_id');
    }

    public function purchases()
    {
        return $this->hasMany(Order::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'user_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function payouts()
    {
        return $this->hasManyThrough(Payout::class, Account::class, 'user_id', 'account_id');
    }

    public function isSubscribed()
    {
        return $this->account_type === 'premium' ? true : false;
    }

    public function firstSale()
    {
        return Order::whereHas('product', function ($query) {
            $query->where('user_id', $this->id);
        })->exists();
    }

    public function hasPayoutSetup()
    {
        return $this->accounts()->exists();
    }

    /**
     * @author obajide028 Odesanya Babajide
     *
     * Get the reviews for the user.
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * The channels the user receives notification broadcasts on.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'users.'.$this->id;
    }

    /**
     * @author obajide028 Odesanya Babajide
     *
     * check if all fields are added and returns a boolean value
     */
    public function kycComplete()
    {
        return ! is_null($this->country) && ! is_null($this->document_type) && ! is_null($this->document_image);
    }
}
