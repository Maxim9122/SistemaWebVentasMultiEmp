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

    public function test_codigo_ya_existente_en_mi_empresa_no_consulta_la_api_externa(): void
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

        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 200)]);

        $respuesta = $this->actingAs($miAdmin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790002000002']);

        $respuesta->assertOk()->assertJson(['existe_en_mi_empresa' => false]);
    }

    public function test_autocompleta_nombre_marca_y_categoria_desde_open_food_facts(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake(['world.openfoodfacts.org/*' => Http::response([
            'status' => 1,
            'product' => [
                'product_name' => 'Yerba Mate 1kg',
                'brands' => 'Marca Linda, Otra Presentación',
                'categories' => 'Yerbas, Infusiones',
            ],
        ], 200)]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790003000003']);

        $respuesta->assertOk()->assertJson([
            'existe_en_mi_empresa' => false,
            'nombre' => 'Yerba Mate 1kg',
            'marca' => 'Marca Linda',
            'categoria' => 'Yerbas',
            'fuente_nombre' => 'openfoodfacts',
        ]);
    }

    public function test_responde_vacio_sin_romper_si_open_food_facts_no_encuentra_nada(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 200)]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '0000000000000']);

        $respuesta->assertOk()->assertJson([
            'existe_en_mi_empresa' => false,
            'nombre' => null,
            'fuente_nombre' => null,
        ]);
    }

    public function test_un_error_de_red_no_rompe_el_endpoint(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake(['world.openfoodfacts.org/*' => Http::response([], 500)]);

        $respuesta = $this->actingAs($admin)
            ->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790004000004']);

        $respuesta->assertOk()->assertJson(['nombre' => null]);
    }

    public function test_el_resultado_de_open_food_facts_queda_cacheado_entre_pedidos_del_mismo_codigo(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        Http::fake(['world.openfoodfacts.org/*' => Http::response(['status' => 0], 200)]);

        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790006000006']);
        $this->actingAs($admin)->postJson(route('productos.buscarCodigoBarra'), ['codigo' => '7790006000006']);

        Http::assertSentCount(1);
    }
}
