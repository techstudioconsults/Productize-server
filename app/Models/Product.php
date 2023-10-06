<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Product extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

     /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'highlights' => AsArrayObject::class,
        'tags' => AsArrayObject::class,
        'cover_photos' => AsArrayObject::class,
        'data' => AsArrayObject::class,
    ];

    protected $guarded = [
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
