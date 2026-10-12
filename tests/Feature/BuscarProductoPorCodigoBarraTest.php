<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BuscarProductoPorCodigoBarraTest extends TestCase
{
    use RefreshDatabase;

    private function crearAdmin(Empresa $empresa): User
    {
        return User::create([
            'empresa_id' => $empresa->id,
            'role' => 'admin',
            'name' => 'Admin de prueba',
            'email' => 'admin'.$empresa->id.'@ejemplo.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function crearEmpresa(string $cuit = '20111111112'): Empresa
    {
        return Empresa::create([
            'razon_social' => 'Empresa de prueba',
            'cuit' => $cuit,
            'email_contacto' => 'contacto@ejemplo.com',
            'estado' => 'activa',
        ]);
    }

    /**
     * Esta familia de APIs responde HTTP 404 (no 200) tanto para "no está en
     * esta base" como para "no existe en ninguna" — es su comportamiento
     * normal documentado, no un error.
     */
    private function fakeSinDatos(): array
    {
        return [
            'world.openfoodfacts.org/*' => Http::response(['status' => 0], 404),
            'world.openbeautyfacts.org/*' => Http::response(['status' => 0], 404),
            'world.openproductsfacts.org/*' => Http::response(['status' => 0], 404),
        ];
    }

    public function test_codigo_ya_existente_en_mi_empresa_no_consulta_ninguna_api_externa(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Producto::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Fideos ya cargados',
            'codigo' => '7790001000001',
            'precio' => 500,
            'stock' => 0,
        ]);

        Http::fake();

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790001000001']);

        $respuesta->assertOk()->assertJson([
            'existe_en_mi_empresa' => true,
            'producto_existente' => ['nombre' => 'Fideos ya cargados'],
        ]);

        Http::assertNothingSent();
    }

    public function test_codigo_de_otra_empresa_no_cuenta_como_existente_para_la_mia(): void
    {
        $empresaAjena = $this->crearEmpresa('20222222223');
        Producto::create([
            'empresa_id' => $empresaAjena->id,
            'nombre' => 'Producto de otro comercio',
            'codigo' => '7790002000002',
            'precio' => 300,
            'stock' => 0,
        ]);

        $miEmpresa = $this->crearEmpresa('20333333334');
        $miAdmin = $this->crearAdmin($miEmpresa);

        Http::fake($this->fakeSinDatos());

        $respuesta = $this->actingAs($miAdmin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790002000002']);

        $respuesta->assertOk()->assertJson(['existe_en_mi_empresa' => false]);
    }

    public function test_autocompleta_desde_open_food_facts_cuando_encuentra_ahi(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'Yerba Mate 1kg', 'brands' => 'Marca Linda', 'categories' => 'Yerbas'],
            ], 200),
        ]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790003000003']);

        $respuesta->assertOk()->assertJson([
            'nombre' => 'Yerba Mate 1kg',
            'marca' => 'Marca Linda',
            'fuente_nombre' => 'openfoodfacts',
        ]);

        Http::assertSentCount(1);
    }

    public function test_si_no_esta_en_alimentos_busca_en_cosmetica_e_higiene(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(['status' => 0], 404),
            'world.openbeautyfacts.org/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'Rexona Men', 'brands' => 'Unilever, Rexona', 'categories' => ''],
            ], 200),
        ]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '8790004000004']);

        $respuesta->assertOk()->assertJson([
            'nombre' => 'Rexona Men',
            'fuente_nombre' => 'openbeautyfacts',
        ]);
    }

    public function test_si_no_esta_en_las_primeras_dos_busca_en_la_tercera(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response(['status' => 0], 404),
            'world.openbeautyfacts.org/*' => Http::response(['status' => 0], 404),
            'world.openproductsfacts.org/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'Esponja limpia vajillas', 'brands' => 'Mercadona', 'categories' => ''],
            ], 200),
        ]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '8480000713421']);

        $respuesta->assertOk()->assertJson([
            'nombre' => 'Esponja limpia vajillas',
            'fuente_nombre' => 'openproductsfacts',
        ]);
    }

    public function test_responde_vacio_sin_romper_si_ninguna_de_las_3_fuentes_encuentra_nada(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake($this->fakeSinDatos());

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '0000000000000']);

        $respuesta->assertOk()->assertJson([
            'existe_en_mi_empresa' => false,
            'nombre' => null,
            'fuente_nombre' => null,
        ]);

        Http::assertSentCount(3);
    }

    public function test_un_error_de_red_en_una_fuente_no_impide_seguir_con_la_siguiente(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([], 500),
            'world.openbeautyfacts.org/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'Talco classico', 'brands' => 'Felce azzurra', 'categories' => ''],
            ], 200),
        ]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '8001280026225']);

        $respuesta->assertOk()->assertJson(['nombre' => 'Talco classico', 'fuente_nombre' => 'openbeautyfacts']);
    }

    public function test_un_resultado_encontrado_queda_cacheado_entre_pedidos_del_mismo_codigo(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([
                'status' => 1,
                'product' => ['product_name' => 'Yerba Mate 1kg', 'brands' => '', 'categories' => ''],
            ], 200),
        ]);

        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790006000006']);
        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790006000006']);

        // La segunda vez sale del cache — nunca vuelve a pegarle a la fuente.
        Http::assertSentCount(1);
    }

    public function test_un_no_encontrado_limpio_tambien_queda_cacheado(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake($this->fakeSinDatos());

        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790007000007']);
        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790007000007']);

        Http::assertSentCount(3);
    }

    /**
     * Si alguna fuente falla de verdad (timeout, 5xx) en vez de responder
     * "no encontrado" limpio, ese resultado negativo NO se cachea — si se
     * cacheara, un problema de red pasajero dejaría el código sin
     * autocompletar por 6 horas aunque el producto sí exista.
     */
    public function test_un_no_encontrado_por_error_de_red_no_queda_cacheado(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake([
            'world.openfoodfacts.org/*' => Http::response([], 500),
            'world.openbeautyfacts.org/*' => Http::response([], 500),
            'world.openproductsfacts.org/*' => Http::response([], 500),
        ]);

        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790008000008']);
        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790008000008']);

        // Ninguna de las dos veces se cacheó el fallo — las 2 vueltas a
        // intentar las 3 fuentes de nuevo (3 + 3 = 6).
        Http::assertSentCount(6);
    }
}
