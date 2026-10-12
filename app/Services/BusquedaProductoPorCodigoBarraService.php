<?php

namespace App\Services;

use App\Models\Producto;
use App\Services\OpenFacts\OpenFactsApiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Orquesta el alta rápida de productos por código de barras: primero
 * mira si el código ya es un producto de la empresa actual (nunca pisa
 * ninguna API externa en ese caso), y si no, busca nombre/marca/
 * categoría encadenando las 3 bases gratuitas de Open Food Facts —
 * alimentos, cosmética/higiene, y el resto — en ese orden, quedándose
 * con la primera que lo encuentre. El precio nunca se autocompleta,
 * queda siempre manual (se había evaluado estimarlo con la búsqueda
 * pública de Mercado Libre, pero esa búsqueda empezó a exigir OAuth y
 * quedó fuera de alcance, ver nota en el plan de esta rama).
 *
 * Ninguna de las 3 bases es sensible por empresa (no expone precio/
 * stock de nadie), por eso el resultado se cachea compartido por
 * código, sin importar qué empresa escaneó primero.
 */
class BusquedaProductoPorCodigoBarraService
{
    /**
     * Orden de búsqueda: de la base más específica (alimentos) a la más
     * genérica (todo lo demás) — así un código de alimento no termina
     * resuelto por la base "catch-all" con datos de peor calidad.
     */
    private const FUENTES = ['openfoodfacts', 'openbeautyfacts', 'openproductsfacts'];

    public function __construct(
        private readonly OpenFactsApiClient $cliente,
    ) {}

    /**
     * @return array{
     *     existe_en_mi_empresa: bool,
     *     producto_existente: array<string, mixed>|null,
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
            ->first([
                'id', 'nombre', 'codigo', 'marca', 'categoria', 'precio', 'costo', 'stock', 'unidad', 'proveedor_id',
                'cantidad_minima_1', 'precio_cantidad_1', 'cantidad_minima_2', 'precio_cantidad_2',
                'cantidad_minima_3', 'precio_cantidad_3',
            ]);

        if ($productoExistente) {
            // Se manda el producto completo (no solo id/nombre) para que, si
            // el usuario elige "Actualizarlo" en vez de crear uno nuevo, el
            // formulario se pueda precargar con sus datos reales en vez de
            // arriesgarse a pisarlos con lo que haya quedado del escaneo
            // (ver aplicarResultado() en create.blade.php).
            return [
                'existe_en_mi_empresa' => true,
                'producto_existente' => $productoExistente->toArray(),
                'nombre' => null,
                'marca' => null,
                'categoria' => null,
                'fuente_nombre' => null,
            ];
        }

        $cacheKey = "busqueda_codigo_barra:{$codigo}";
        $datosWeb = Cache::get($cacheKey);

        if ($datosWeb === null) {
            ['datos' => $datosWeb, 'encontrado' => $encontrado, 'hubo_error' => $huboError] = $this->buscarEnLasFuentesWeb($codigo);

            // Solo se cachea un "no encontrado" cuando las 3 fuentes
            // respondieron limpio que no lo tienen — si alguna falló
            // (timeout, caída pasajera, rate limit), ese resultado negativo
            // NO se cachea, para no dejar "no encontrado" pegado 6 horas por
            // un problema transitorio en vez de una ausencia real del
            // catálogo (si se encontró, siempre se cachea igual).
            if ($encontrado || ! $huboError) {
                Cache::put($cacheKey, $datosWeb, now()->addHours(6));
            }
        }

        return [
            'existe_en_mi_empresa' => false,
            'producto_existente' => null,
            ...$datosWeb,
        ];
    }

    /**
     * @return array{datos: array{nombre: ?string, marca: ?string, categoria: ?string, fuente_nombre: ?string}, encontrado: bool, hubo_error: bool}
     */
    private function buscarEnLasFuentesWeb(string $codigo): array
    {
        $huboError = false;

        foreach (self::FUENTES as $fuente) {
            [$datos, $error] = $this->consultarFuente($fuente, $codigo);
            $huboError = $huboError || $error;

            if ($datos !== null && $datos['nombre']) {
                return ['datos' => [...$datos, 'fuente_nombre' => $fuente], 'encontrado' => true, 'hubo_error' => $huboError];
            }
        }

        return [
            'datos' => ['nombre' => null, 'marca' => null, 'categoria' => null, 'fuente_nombre' => null],
            'encontrado' => false,
            'hubo_error' => $huboError,
        ];
    }

    /**
     * @return array{0: array{nombre: ?string, marca: ?string, categoria: ?string}|null, 1: bool} [datos, hubo_error]
     */
    private function consultarFuente(string $fuente, string $codigo): array
    {
        try {
            return [$this->cliente->buscarPorCodigo(config("services.{$fuente}.url"), $codigo), false];
        } catch (\Throwable $e) {
            Log::warning("No se pudo consultar {$fuente} para el código de barra.", [
                'codigo' => $codigo,
                'exception' => $e,
            ]);

            return [null, true];
        }
    }
}
