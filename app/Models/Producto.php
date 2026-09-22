<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'codigo',
        'precio',
        'costo',
        'stock',
        'categoria',
        'marca',
        'unidad',
        'activo',
        'proveedor_id',
        'es_promocion',
        'cantidad_minima_1',
        'precio_cantidad_1',
        'cantidad_minima_2',
        'precio_cantidad_2',
        'cantidad_minima_3',
        'precio_cantidad_3',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'costo' => 'decimal:2',
            'stock' => 'integer',
            'activo' => 'boolean',
            'es_promocion' => 'boolean',
            'cantidad_minima_1' => 'integer',
            'precio_cantidad_1' => 'decimal:2',
            'cantidad_minima_2' => 'integer',
            'precio_cantidad_2' => 'decimal:2',
            'cantidad_minima_3' => 'integer',
            'precio_cantidad_3' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(GrupoProducto::class, 'grupo_producto', 'producto_id', 'grupo_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /**
     * Cuando este producto es una promoción (es_promocion=true), los productos
     * reales que la componen y en qué cantidad cada uno.
     */
    public function componentes(): HasMany
    {
        return $this->hasMany(PromocionItem::class, 'promocion_id');
    }

    /**
     * Los hasta 3 tramos de precio por cantidad configurados, ordenados de
     * menor a mayor cantidad mínima. Solo incluye los tramos completos
     * (cantidad mínima y precio cargados).
     *
     * @return list<array{cantidad_minima: int, precio: float}>
     */
    public function tramosDePrecio(): array
    {
        $tramos = [];

        foreach ([1, 2, 3] as $n) {
            $cantidadMinima = $this->{"cantidad_minima_{$n}"};
            $precio = $this->{"precio_cantidad_{$n}"};

            if ($cantidadMinima === null || $precio === null) {
                continue;
            }

            $tramos[] = ['cantidad_minima' => (int) $cantidadMinima, 'precio' => (float) $precio];
        }

        usort($tramos, fn ($a, $b) => $a['cantidad_minima'] <=> $b['cantidad_minima']);

        return $tramos;
    }

    /**
     * El precio que corresponde según la cantidad: el tramo con la cantidad
     * mínima más alta que la cantidad alcanza a cubrir, o el precio de
     * catálogo si no llega a ningún tramo o no tiene ninguno configurado.
     *
     * @return array{precio: float, cantidad_minima: int|null}
     */
    public function precioParaCantidad(int $cantidad): array
    {
        $mejor = null;

        foreach ($this->tramosDePrecio() as $tramo) {
            if ($cantidad >= $tramo['cantidad_minima']) {
                $mejor = $tramo;
            }
        }

        if ($mejor === null) {
            return ['precio' => (float) $this->precio, 'cantidad_minima' => null];
        }

        return $mejor;
    }
}
