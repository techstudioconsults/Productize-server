<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;
    use HasUuids;

    /**
     * All three have to be included in other to use uuids as ids
     */
    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['user_id', 'email', 'subject', 'message'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
