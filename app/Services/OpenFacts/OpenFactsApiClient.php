<?php

namespace App\Services\OpenFacts;

use Illuminate\Support\Facades\Http;

/**
 * Capa HTTP pura sobre la familia de APIs públicas y gratuitas de Open
 * Food Facts: Open Food Facts (alimentos), Open Beauty Facts (cosmética
 * e higiene) y Open Products Facts (el resto) — mismo formato de API,
 * bases de datos separadas por rubro. El caller decide con qué base
 * consultar (ver BusquedaProductoPorCodigoBarraService) y en qué orden.
 */
class OpenFactsApiClient
{
    /**
     * @return array{nombre: ?string, marca: ?string, categoria: ?string}|null
     *         null si esa base no tiene este código.
     */
    public function buscarPorCodigo(string $baseUrl, string $codigo): ?array
    {
        $response = Http::acceptJson()
            ->timeout(5)
            ->get(rtrim($baseUrl, '/')."/api/v2/product/{$codigo}.json", [
                'fields' => 'status,product_name,brands,categories',
            ]);

        // Esta familia de APIs devuelve HTTP 404 tanto para "no está en esta
        // base" como respuesta NORMAL (con body JSON y `status: 0`), no es
        // un error nuestro — la fuente de verdad es el campo `status` del
        // body, nunca el código HTTP. Solo un 5xx real es un error de
        // verdad acá (timeouts/DNS ya tiran su propia excepción antes de
        // llegar a este punto, sin necesidad de ->throw()).
        if ($response->serverError()) {
            $response->throw();
        }

        if ((int) $response->json('status', 0) !== 1) {
            return null;
        }

        $producto = $response->json('product', []);

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
     * Esta familia de APIs devuelve `brands`/`categories` como texto
     * separado por comas (ej: "Marca Tal, Otra Marca") — nos quedamos solo
     * con el primero.
     */
    private function primerSegmento(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return null;
        }

        return trim(explode(',', $valor)[0]);
    }
}
