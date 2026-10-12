<?php

namespace App\Services;

use App\Models\Producto;
use App\Services\OpenFoodFacts\OpenFoodFactsApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta el alta rápida de productos por código de barras: primero
 * mira si el código ya es un producto de la empresa actual (nunca pisa
 * la API externa en ese caso), y si no, busca nombre/marca/categoría en
 * Open Food Facts. El precio nunca se autocompleta — queda siempre
 * manual (se había evaluado estimarlo con la búsqueda pública de
 * Mercado Libre, pero esa búsqueda empezó a exigir OAuth y quedó fuera
 * de alcance, ver nota en el plan de esta rama).
 *
 * Open Food Facts no es sensible por empresa (no expone precio/stock de
 * nadie), por eso el resultado se cachea compartido por código, sin
 * importar qué empresa escaneó primero.
 */
class BusquedaProductoPorCodigoBarraService
{
    public function __construct(
        private readonly OpenFoodFactsApiClient $openFoodFacts,
    ) {}

    /**
     * @return array{
     *     existe_en_mi_empresa: bool,
     *     producto_existente: array{id: int, nombre: string}|null,
     *     nombre: ?string,
     *     marca: ?string,
     *     categoria: ?string,
     *     fuente_nombre: ?string,
     * }
     */
    public function buscar(string $codigo, int $empresaId): array
    {
        $productoExistente = Producto::where('empresa_id', $empresaId)
            ->where('codigo', $codigo)
            ->first(['id', 'nombre']);

        if ($productoExistente) {
            return [
                'existe_en_mi_empresa' => true,
                'producto_existente' => ['id' => $productoExistente->id, 'nombre' => $productoExistente->nombre],
                'nombre' => null,
                'marca' => null,
                'categoria' => null,
                'fuente_nombre' => null,
            ];
        }

        $datosWeb = Cache::remember(
            "busqueda_codigo_barra:{$codigo}",
            now()->addHours(6),
            fn () => $this->buscarEnOpenFoodFacts($codigo)
        );

        $nombre = $datosWeb['nombre'] ?? null;

        return [
            'existe_en_mi_empresa' => false,
            'producto_existente' => null,
            'nombre' => $nombre,
            'marca' => $datosWeb['marca'] ?? null,
            'categoria' => $datosWeb['categoria'] ?? null,
            'fuente_nombre' => $nombre ? 'openfoodfacts' : null,
        ];
    }

    /**
     * @return array{nombre: ?string, marca: ?string, categoria: ?string}
     */
    private function buscarEnOpenFoodFacts(string $codigo): array
    {
        try {
            return $this->openFoodFacts->buscarPorCodigo($codigo) ?? ['nombre' => null, 'marca' => null, 'categoria' => null];
        } catch (\Throwable $e) {
            Log::warning('No se pudo consultar Open Food Facts para el código de barra.', [
                'codigo' => $codigo,
                'exception' => $e,
            ]);

            return ['nombre' => null, 'marca' => null, 'categoria' => null];
        }
    }
}
