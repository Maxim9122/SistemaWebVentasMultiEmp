<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportarProductosDesdeFotoTest extends TestCase
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

    private function configurarApiFalsa(array $productosDetectados): void
    {
        config(['services.anthropic.api_key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => json_encode(['productos' => $productosDetectados])]],
            ], 200),
        ]);
    }

    public function test_sin_configurar_las_rutas_devuelven_404(): void
    {
        config(['services.anthropic.api_key' => null]);
        $admin = $this->crearAdmin($this->crearEmpresa());

        $this->actingAs($admin)->get(route('productos.importarFoto.subir'))->assertNotFound();
    }

    public function test_flujo_completo_crea_actualiza_e_ignora_segun_el_modo_elegido(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        // Ya existe en el catálogo — la foto trae el mismo código con otro precio.
        $existente = Producto::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Fideos viejos',
            'codigo' => '7790001000001',
            'precio' => 500,
            'stock' => 0,
        ]);

        $this->configurarApiFalsa([
            ['nombre' => 'Fideos', 'codigo' => '7790001000001', 'precio' => '800', 'costo' => null, 'stock' => null, 'categoria' => null, 'marca' => null, 'unidad' => null],
            ['nombre' => 'Arroz 1kg', 'codigo' => null, 'precio' => '1.200,50', 'costo' => null, 'stock' => null, 'categoria' => null, 'marca' => null, 'unidad' => 'kg'],
        ]);

        // Pantalla 1: subir foto.
        $this->actingAs($admin)->get(route('productos.importarFoto.subir'))->assertOk();

        // Pantalla 2: extraer — usa un archivo de imagen fake real (UploadedFile::fake()->image()).
        $respuestaExtraer = $this->actingAs($admin)->post(route('productos.importarFoto.extraer'), [
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('lista.jpg'),
        ]);

        $respuestaExtraer->assertOk();
        $respuestaExtraer->assertSee('Fideos');
        $respuestaExtraer->assertSee('Arroz 1kg');

        // Pantalla 3: confirmar extracción (ya editada) -> cae en la pantalla
        // de mapeo/duplicados que ya existe para Excel, sin tocarla.
        $respuestaConfirmarExtraccion = $this->actingAs($admin)->post(route('productos.importarFoto.confirmar'), [
            'productos_json' => json_encode([
                ['nombre' => 'Fideos', 'codigo' => '7790001000001', 'precio' => '800', 'costo' => '', 'stock' => '', 'categoria' => '', 'marca' => '', 'unidad' => ''],
                ['nombre' => 'Arroz 1kg', 'codigo' => '', 'precio' => '1.200,50', 'costo' => '', 'stock' => '', 'categoria' => '', 'marca' => '', 'unidad' => 'kg'],
            ]),
        ]);

        $respuestaConfirmarExtraccion->assertOk();
        $respuestaConfirmarExtraccion->assertViewIs('productos.importar.confirmar');
        $token = $respuestaConfirmarExtraccion->viewData('token');

        // Mapeo automático: con encabezados = MapeoColumnasService::CAMPOS,
        // nombre y precio tienen que haberse resuelto solos.
        $sugerencias = $respuestaConfirmarExtraccion->viewData('sugerencias');
        $campos = $respuestaConfirmarExtraccion->viewData('campos');
        $this->assertSame($campos, array_values($sugerencias));

        // Pantalla real que YA existe (sin tocar) — modo "actualizar".
        $respuestaFinal = $this->actingAs($admin)->post(route('productos.importar.confirmar'), [
            'token' => $token,
            'mapeo' => $sugerencias,
            'modo_duplicados' => 'actualizar',
        ]);

        $respuestaFinal->assertOk()->assertViewHas('creados', 1)->assertViewHas('actualizados', 1);

        $existente->refresh();
        $this->assertSame(800.0, (float) $existente->precio);
        $this->assertDatabaseHas('productos', ['empresa_id' => $empresa->id, 'nombre' => 'Arroz 1kg', 'unidad' => 'kg']);

        // La importación quedó registrada con sus cambios — deshacer() tiene
        // que funcionar sin ningún cambio, porque opera sobre
        // ImportacionProductoCambio, igual que si hubiera venido de un Excel.
        $importacion = \App\Models\ImportacionProductos::where('empresa_id', $empresa->id)->latest()->first();
        $this->assertNotNull($importacion);
        $this->assertSame(2, $importacion->cambios()->count());

        $respuestaDeshacer = $this->actingAs($admin)->post(route('productos.importaciones.deshacer', $importacion));
        $respuestaDeshacer->assertRedirect();

        $existente->refresh();
        $this->assertSame(500.0, (float) $existente->precio, 'deshacer() debería restaurar el precio original.');
        $this->assertDatabaseMissing('productos', ['empresa_id' => $empresa->id, 'nombre' => 'Arroz 1kg']);
    }

    public function test_modo_ignorar_no_toca_el_producto_existente(): void
    {
        $empresa = $this->crearEmpresa();
        $admin = $this->crearAdmin($empresa);

        $existente = Producto::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Fideos viejos',
            'codigo' => '7790001000001',
            'precio' => 500,
            'stock' => 0,
        ]);

        $this->configurarApiFalsa([
            ['nombre' => 'Fideos', 'codigo' => '7790001000001', 'precio' => '800', 'costo' => null, 'stock' => null, 'categoria' => null, 'marca' => null, 'unidad' => null],
        ]);

        $respuestaExtraer = $this->actingAs($admin)->post(route('productos.importarFoto.extraer'), [
            'foto' => \Illuminate\Http\UploadedFile::fake()->image('lista.jpg'),
        ]);

        $respuestaConfirmarExtraccion = $this->actingAs($admin)->post(route('productos.importarFoto.confirmar'), [
            'productos_json' => json_encode([
                ['nombre' => 'Fideos', 'codigo' => '7790001000001', 'precio' => '800'],
            ]),
        ]);

        $token = $respuestaConfirmarExtraccion->viewData('token');
        $sugerencias = $respuestaConfirmarExtraccion->viewData('sugerencias');

        $this->actingAs($admin)->post(route('productos.importar.confirmar'), [
            'token' => $token,
            'mapeo' => $sugerencias,
            'modo_duplicados' => 'ignorar',
        ])->assertOk()->assertViewHas('ignorados', 1);

        $existente->refresh();
        $this->assertSame(500.0, (float) $existente->precio, 'modo ignorar no debería tocar el producto existente.');
    }
}
