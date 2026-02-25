<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('code',           'codigo');
            $table->renameColumn('name',           'nombre');
            $table->renameColumn('price',          'precio');
            $table->renameColumn('tax_percentage', 'porcentaje_impuesto');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('codigo',              'code');
            $table->renameColumn('nombre',              'name');
            $table->renameColumn('precio',              'price');
            $table->renameColumn('porcentaje_impuesto', 'tax_percentage');
        });
    }
};
