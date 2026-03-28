<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FcmToken extends Model
{
    protected $table = 'fcm_tokens';

    protected $fillable = [
        'user_id',
        'token',
        'device_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
