<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Revenue extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = ['activity', 'product', 'amount', 'user_id', 'commission', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
