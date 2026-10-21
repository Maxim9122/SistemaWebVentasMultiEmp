<?php

namespace App\Services\ClaudeVision;

use App\Services\ClaudeVision\Exceptions\ExtraccionFotoException;
use App\Services\MapeoColumnasClienteService;
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
 *
 * Dos entradas públicas (productos/clientes) sobre el mismo núcleo HTTP —
 * cada una con su propio prompt, porque una foto de lista de precios y una
 * foto de lista de clientes se leen distinto (igual que MapeoColumnasService
 * y MapeoColumnasClienteService son dos esquemas separados, no uno genérico).
 */
class ClaudeVisionApiClient
{
    public function configurada(): bool
    {
        return filled(config('services.anthropic.api_key'));
    }

    /**
     * @return list<array<string, ?string>>
     *
     * @throws ExtraccionFotoException si falla la conexión, la API devuelve
     *                                 un error, la respuesta no cumple el
     *                                 schema esperado, o no se detectó ningún
     *                                 producto en la foto.
     */
    public function extraerProductosDeFoto(string $imagenBinaria, string $mimeType): array
    {
        return $this->extraerDeFoto(
            $imagenBinaria,
            $mimeType,
            claveLista: 'productos',
            campos: MapeoColumnasService::CAMPOS,
            prompt: <<<'TEXTO'
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
                TEXTO,
            mensajeSinResultados: 'No se detectó ningún producto en la foto. Probá con otra foto más clara, o cargalos manualmente.',
        );
    }

    /**
     * @return list<array<string, ?string>>
     *
     * @throws ExtraccionFotoException si falla la conexión, la API devuelve
     *                                 un error, la respuesta no cumple el
     *                                 schema esperado, o no se detectó ningún
     *                                 cliente en la foto.
     */
    public function extraerClientesDeFoto(string $imagenBinaria, string $mimeType): array
    {
        return $this->extraerDeFoto(
            $imagenBinaria,
            $mimeType,
            claveLista: 'clientes',
            campos: MapeoColumnasClienteService::CAMPOS,
            prompt: <<<'TEXTO'
                Esta imagen es una foto de una lista de clientes de un comercio (puede estar
                escrita a mano o impresa — por ejemplo una agenda, un cuaderno de fiado, o un
                listado de contactos). Extraé cada cliente que puedas leer con sus campos:
                nombre, cuit (CUIT o DNI, si aparece), telefono, email.

                Si un campo no se puede leer con confianza para un cliente, poné null para
                ese campo en vez de adivinar. No inventes clientes que no estén en la foto.
                Dejá el teléfono tal como aparece escrito (con espacios, guiones, código de
                área, etc.) — no lo reformatees.
                TEXTO,
            mensajeSinResultados: 'No se detectó ningún cliente en la foto. Probá con otra foto más clara, o cargalos manualmente.',
        );
    }

    /**
     * Lee una foto de un remito/factura de proveedor (lo que llegó de
     * mercadería) y devuelve una lista de {nombre, codigo, cantidad} — a
     * propósito NO pide precio/costo/etc acá: "reponer stock" solo suma
     * cantidad a productos que YA existen en el catálogo, nunca crea
     * productos nuevos (eso es tarea de "Importar productos", no de acá).
     *
     * @return list<array{nombre: ?string, codigo: ?string, cantidad: ?string}>
     *
     * @throws ExtraccionFotoException si falla la conexión, la API devuelve
     *                                 un error, la respuesta no cumple el
     *                                 schema esperado, o no se detectó nada
     *                                 en la foto.
     */
    public function extraerReposicionDeFoto(string $imagenBinaria, string $mimeType): array
    {
        return $this->extraerDeFoto(
            $imagenBinaria,
            $mimeType,
            claveLista: 'items',
            campos: ['nombre', 'codigo', 'cantidad'],
            prompt: <<<'TEXTO'
                Esta imagen es una foto de un remito o factura de un proveedor — el
                comprobante de mercadería que llegó a un comercio (puede estar escrito a
                mano o impreso). Extraé cada producto/artículo de la lista con sus campos:
                nombre (tal como está escrito), codigo (código de barras o SKU, si
                aparece), cantidad (la cantidad que llegó de ese producto).

                No extraigas precios ni montos — no hacen falta. Si un campo no se puede
                leer con confianza, poné null para ese campo en vez de adivinar (la
                cantidad es el dato más importante, prestale especial atención). No
                inventes productos que no estén en la foto. Ignorá totales, subtotales,
                impuestos y cualquier línea que no sea un producto/artículo concreto.
                TEXTO,
            mensajeSinResultados: 'No se detectó ningún producto en la foto. Probá con otra foto más clara.',
        );
    }

    /**
     * Núcleo HTTP compartido: arma el request con "structured outputs"
     * (output_config.format con JSON Schema) en vez de pedir JSON en texto
     * libre — la API valida el schema del lado de ellos, así que no hace
     * falta tolerar una respuesta mal formada.
     *
     * @param  list<string>  $campos
     * @return list<array<string, ?string>>
     */
    private function extraerDeFoto(
        string $imagenBinaria,
        string $mimeType,
        string $claveLista,
        array $campos,
        string $prompt,
        string $mensajeSinResultados,
    ): array {
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
                            ['type' => 'text', 'text' => $prompt],
                        ],
                    ]],
                    'output_config' => ['format' => $this->jsonSchema($claveLista, $campos)],
                ]);
        } catch (ConnectionException $e) {
            throw new ExtraccionFotoException(
                'No se pudo contactar al servicio de lectura de fotos. Probá de nuevo en unos minutos.',
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new ExtraccionFotoException(
                'El servicio de lectura de fotos devolvió un error. Probá de nuevo en unos minutos.'
            );
        }

        $texto = $this->primerBloqueDeTexto($response->json('content', []));
        $datos = $texto !== null ? json_decode($texto, true) : null;

        if (! is_array($datos) || ! isset($datos[$claveLista]) || ! is_array($datos[$claveLista])) {
            throw new ExtraccionFotoException(
                'No se pudo interpretar la respuesta del servicio de lectura de fotos. Probá con otra foto, con mejor luz y encuadre.'
            );
        }

        if ($datos[$claveLista] === []) {
            throw new ExtraccionFotoException($mensajeSinResultados);
        }

        return $datos[$claveLista];
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

    /**
     * @param  list<string>  $campos
     */
    private function jsonSchema(string $claveLista, array $campos): array
    {
        $propiedades = array_fill_keys($campos, ['type' => ['string', 'null']]);

        return [
            'type' => 'json_schema',
            'schema' => [
                'type' => 'object',
                'properties' => [
                    $claveLista => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => $propiedades,
                            'required' => $campos,
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => [$claveLista],
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
