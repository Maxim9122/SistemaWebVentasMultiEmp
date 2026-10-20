<?php

namespace App\Services\ClaudeVision;

use App\Services\ClaudeVision\Exceptions\ExtraccionFotoProductosException;
use App\Services\MapeoColumnasService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Capa HTTP pura sobre la API de Mensajes de Anthropic (Claude) — mismo
 * criterio que FacturacionApiClient/OpenFactsApiClient/MercadoPagoApiClient:
 * sin lógica de negocio, sin SDK (el proyecto no tiene ninguna dependencia
 * de Anthropic instalada y prefiere mantenerse así — ver preferencia de
 * dependencias mínimas), credencial siempre como parámetro de config, nunca
 * hardcodeada.
 */
class ClaudeVisionApiClient
{
    public function configurada(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    /**
     * Le pide a Claude que lea una foto de una lista de precios y devuelva
     * los productos detectados, uno por fila, con las claves de
     * MapeoColumnasService::CAMPOS. Usa "structured outputs"
     * (output_config.format con JSON Schema) en vez de pedir JSON en texto
     * libre — la API valida el schema del lado de ellos, así que no hace
     * falta tolerar una respuesta mal formada.
     *
     * @return list<array<string, ?string>>
     *
     * @throws ExtraccionFotoProductosException si falla la conexión, la API
     *                                           devuelve un error, la respuesta no
     *                                           cumple el schema esperado, o no se
     *                                           detectó ningún producto en la foto.
     */
    public function extraerProductosDeFoto(string $imagenBinaria, string $mimeType): array
    {
        $this->asegurarConfigurada();

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.api_key'),
                'anthropic-version' => '2023-06-01',
            ])
                ->timeout(90)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('services.anthropic.vision_model'),
                    'max_tokens' => 8192,
                    'messages' => [[
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => base64_encode($imagenBinaria),
                                ],
                            ],
                            ['type' => 'text', 'text' => $this->prompt()],
                        ],
                    ]],
                    'output_config' => ['format' => $this->jsonSchema()],
                ]);
        } catch (ConnectionException $e) {
            throw new ExtraccionFotoProductosException(
                'No se pudo contactar al servicio de lectura de fotos. Probá de nuevo en unos minutos.',
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new ExtraccionFotoProductosException(
                'El servicio de lectura de fotos devolvió un error. Probá de nuevo en unos minutos.'
            );
        }

        $texto = $this->primerBloqueDeTexto($response->json('content', []));
        $datos = $texto !== null ? json_decode($texto, true) : null;

        if (! is_array($datos) || ! isset($datos['productos']) || ! is_array($datos['productos'])) {
            throw new ExtraccionFotoProductosException(
                'No se pudo interpretar la respuesta del servicio de lectura de fotos. Probá con otra foto, con mejor luz y encuadre.'
            );
        }

        if ($datos['productos'] === []) {
            throw new ExtraccionFotoProductosException(
                'No se detectó ningún producto en la foto. Probá con otra foto más clara, o cargalos manualmente.'
            );
        }

        return $datos['productos'];
    }

    /**
     * @param  list<array<string, mixed>>  $bloques
     */
    private function primerBloqueDeTexto(array $bloques): ?string
    {
        foreach ($bloques as $bloque) {
            if (($bloque['type'] ?? null) === 'text') {
                return $bloque['text'];
            }
        }

        return null;
    }

    private function prompt(): string
    {
        return <<<'TEXTO'
            Esta imagen es una foto de una lista de precios de un comercio (puede estar
            escrita a mano o impresa). Extraé cada producto que puedas leer con sus
            campos: nombre, codigo (código de barras o SKU, si aparece), precio (precio
            de venta), costo (precio de costo, si aparece), stock (cantidad, si aparece),
            categoria, marca, unidad (ej "kg", "unidad", "docena").

            Si un campo no se puede leer con confianza para un producto, poné null para
            ese campo en vez de adivinar. No inventes productos que no estén en la foto.
            Dejá los valores de precio/costo/stock tal como aparecen escritos (con
            separadores de miles o decimales, símbolo de moneda, etc.) — no los
            conviertas ni los redondees.
            TEXTO;
    }

    private function jsonSchema(): array
    {
        $propiedades = array_fill_keys(
            MapeoColumnasService::CAMPOS,
            ['type' => ['string', 'null']],
        );

        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'productos' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $propiedades,
                            'required' => MapeoColumnasService::CAMPOS,
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['productos'],
                'additionalProperties' => false,
            ],
        ];
    }

    private function asegurarConfigurada(): void
    {
        if (! $this->configurada()) {
            throw new \RuntimeException('ANTHROPIC_API_KEY no está configurada.');
        }
    }
}
