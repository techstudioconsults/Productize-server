<?php

/**
 *  @author @obajide028 Odesanya Babajide
 *
 *  @version 1.0
 *
 *  @since 22-05-2024
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * All three have to be included in other to use uuids as ids
     */
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['rating', 'comment', 'product_id', 'user_id'];

    // user who gave the review
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // product the user gave a review on
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
