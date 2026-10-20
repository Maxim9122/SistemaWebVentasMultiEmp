<?php

namespace Tests\Feature;

use App\Services\ClaudeVision\ClaudeVisionApiClient;
use App\Services\ClaudeVision\Exceptions\ExtraccionFotoProductosException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeVisionApiClientTest extends TestCase
{
    private function cliente(): ClaudeVisionApiClient
    {
        config(['services.anthropic.api_key' => 'test-key']);

        return app(ClaudeVisionApiClient::class);
    }

    public function test_respuesta_valida_devuelve_los_productos(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => json_encode([
                        'productos' => [
                            ['nombre' => 'Coca Cola 1.5L', 'codigo' => '7790001000001', 'precio' => '1500', 'costo' => null, 'stock' => null, 'categoria' => 'Bebidas', 'marca' => 'Coca Cola', 'unidad' => null],
                        ],
                    ])],
                ],
            ], 200),
        ]);

        $productos = $this->cliente()->extraerProductosDeFoto('contenido-binario-falso', 'image/jpeg');

        $this->assertCount(1, $productos);
        $this->assertSame('Coca Cola 1.5L', $productos[0]['nombre']);
        $this->assertSame('1500', $productos[0]['precio']);
    }

    public function test_usa_el_modelo_configurado_y_los_headers_correctos(): void
    {
        config(['services.anthropic.vision_model' => 'claude-haiku-5-5']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['productos' => [['nombre' => 'X', 'codigo' => null, 'precio' => '1', 'costo' => null, 'stock' => null, 'categoria' => null, 'marca' => null, 'unidad' => null]]])]],
            ], 200),
        ]);

        $this->cliente()->extraerProductosDeFoto('contenido', 'image/png');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request['model'] === 'claude-haiku-5-5'
                && $request->header('x-api-key') === ['test-key']
                && $request->header('anthropic-version') === ['2023-06-01'];
        });
    }

    public function test_texto_no_json_tira_excepcion_clara(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'esto no es json']],
            ], 200),
        ]);

        $this->expectException(ExtraccionFotoProductosException::class);
        $this->expectExceptionMessage('No se pudo interpretar la respuesta');

        $this->cliente()->extraerProductosDeFoto('contenido', 'image/jpeg');
    }

    public function test_sin_productos_detectados_tira_excepcion_clara(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['productos' => []])]],
            ], 200),
        ]);

        $this->expectException(ExtraccionFotoProductosException::class);
        $this->expectExceptionMessage('No se detectó ningún producto');

        $this->cliente()->extraerProductosDeFoto('contenido', 'image/jpeg');
    }

    public function test_error_http_nunca_deja_escapar_una_excepcion_sin_controlar(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'algo salió mal'], 500),
        ]);

        $this->expectException(ExtraccionFotoProductosException::class);

        $this->cliente()->extraerProductosDeFoto('contenido', 'image/jpeg');
    }

    public function test_falla_de_conexion_tira_excepcion_clara(): void
    {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        });

        $this->expectException(ExtraccionFotoProductosException::class);
        $this->expectExceptionMessage('No se pudo contactar');

        $this->cliente()->extraerProductosDeFoto('contenido', 'image/jpeg');
    }

    public function test_sin_configurar_tira_runtime_exception(): void
    {
        config(['services.anthropic.api_key' => null]);

        $this->expectException(\RuntimeException::class);

        app(ClaudeVisionApiClient::class)->extraerProductosDeFoto('contenido', 'image/jpeg');
    }
}
