<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Verificar si la columna existe antes de renombrar
            if (Schema::hasColumn('products', 'code')) {
                $table->renameColumn('code', 'codigo');
            }
            if (Schema::hasColumn('products', 'name')) {
                $table->renameColumn('name', 'nombre');
            }
            if (Schema::hasColumn('products', 'price')) {
                $table->renameColumn('price', 'precio');
            }
            if (Schema::hasColumn('products', 'tax_percentage')) {
                $table->renameColumn('tax_percentage', 'porcentaje_impuesto');
            }
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
