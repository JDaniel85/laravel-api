<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generar username automáticamente si no se proporciona
        static::creating(function (Model $model) {
            if (!$model->username && $model->name) {
                // Generar username desde el nombre
                $username = strtolower(str_replace(' ', '.', $model->name));
                $username = preg_replace('/[^a-z0-9.]/', '', $username);
                
                // Asegurar que sea único agregando un sufijo si es necesario
                $originalUsername = $username;
                $counter = 1;
                while (static::where('username', $username)->exists()) {
                    $username = $originalUsername . $counter;
                    $counter++;
                }
                
                $model->username = $username;
            }
        });
    }
}
