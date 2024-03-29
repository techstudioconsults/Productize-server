<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayOutAccount extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'account_number',
        'paystack_recipient_code',
        'name',
        'bank_code',
        'bank_name',
        'active'
    ];

    protected $casts = [
        'account_number' => 'string', // Prevent eloquent from taking off leading zeros from strings
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($payoutaccount) {
            if ($payoutaccount->active) {
                $user = $payoutaccount->user;
                if ($user) {
                    // Set All other payout accounts to false
                    $user->payOutAccounts()->where('id', '!=', $payoutaccount->id)->update(['active' => 0]);
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
