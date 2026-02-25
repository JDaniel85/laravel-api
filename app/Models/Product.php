<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'precio',
        'porcentaje_impuesto',
    ];

    protected $casts = [
        'precio'              => 'float',
        'porcentaje_impuesto' => 'float',
    ];

    
    public function getPrecioFinalAttribute(): float
    {
        return round($this->precio + ($this->precio * $this->porcentaje_impuesto / 100), 2);
    }

    protected $appends = ['precio_final'];
}
