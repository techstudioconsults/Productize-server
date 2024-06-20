<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
    ];

    protected $hidden = [
    ];

    public function getAvailableEarnings()
    {
        return $this->total_earnings - $this->withdrawn_earnings;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
