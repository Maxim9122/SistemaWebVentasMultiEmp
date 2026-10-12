<?php

namespace App\Services\OpenFoodFacts;

use Illuminate\Support\Facades\Http;

/**
 * Capa HTTP pura sobre la API pública y gratuita de Open Food Facts
 * (sin API key). Solo arma y manda el request — no decide qué hacer si
 * el producto no se encuentra ni atrapa errores de red, eso es
 * responsabilidad del caller (BusquedaProductoPorCodigoBarraService).
 */
class OpenFoodFactsApiClient
{
    /**
     * @return array{nombre: ?string, marca: ?string, categoria: ?string}|null
     *         null si Open Food Facts no tiene este código.
     */
    public function buscarPorCodigo(string $codigo): ?array
    {
        $response = Http::acceptJson()
            ->timeout(5)
            ->get($this->url("/api/v2/product/{$codigo}.json"), [
                'fields' => 'status,product_name,brands,categories',
            ])
            ->throw();

        $datos = $response->json();

        if ((int) ($datos['status'] ?? 0) !== 1) {
            return null;
        }

        $producto = $datos['product'] ?? [];

        return [
            'nombre' => $this->primerValorNoVacio($producto['product_name'] ?? null),
            'marca' => $this->primerSegmento($producto['brands'] ?? null),
            'categoria' => $this->primerSegmento($producto['categories'] ?? null),
        ];
    }

    private function primerValorNoVacio(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : null;
    }

    /**
     * Open Food Facts devuelve `brands`/`categories` como texto separado por
     * comas (ej: "Marca Tal, Otra Marca") — nos quedamos solo con el primero.
     */
    private function primerSegmento(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return trim(explode(',', $valor)[0]);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.openfoodfacts.url'), '/').$path;
    }
}
