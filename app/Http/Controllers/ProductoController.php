<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuardarProductoRequest;
use App\Models\AjustePrecioBusqueda;
use App\Models\GrupoProducto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\ReposicionStock;
use App\Services\ExportadorProductosExcel;
use App\Services\ReportePdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductoController extends Controller
{
    public function __construct(private readonly ExportadorProductosExcel $exportador) {}

    public function index(Request $request): View
    {
        $porPagina = (int) $request->input('por_pagina', 10);

        if (! in_array($porPagina, [10, 50, 100], true)) {
            $porPagina = 10;
        }

        $ordenables = ['nombre', 'categoria', 'marca'];
        $orden = in_array($request->input('orden'), $ordenables, true) ? $request->input('orden') : 'nombre';
        $direccion = $request->input('dir') === 'desc' ? 'desc' : 'asc';
        $buscar = trim((string) $request->input('buscar', ''));
        $marca = trim((string) $request->input('marca', ''));
        $categoria = trim((string) $request->input('categoria', ''));
        $proveedorId = $request->filled('proveedor_id') ? (int) $request->input('proveedor_id') : null;

        $empresaId = $request->user()->empresa_id;

        $productos = $this->productosFiltrados($request, $buscar, $marca, $categoria, $proveedorId)
            ->orderBy($orden, $direccion)
            ->paginate($porPagina)
            ->withQueryString();

        $grupos = GrupoProducto::where('empresa_id', $empresaId)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('productos.index', [
            'productos' => $productos,
            'orden' => $orden,
            'direccion' => $direccion,
            'porPagina' => $porPagina,
            'buscar' => $buscar,
            'marca' => $marca,
            'categoria' => $categoria,
            'proveedorId' => $proveedorId,
            'grupos' => $grupos,
            'marcasDisponibles' => Producto::where('empresa_id', $empresaId)->whereNotNull('marca')->where('marca', '!=', '')->distinct()->orderBy('marca')->pluck('marca'),
            'categoriasDisponibles' => Producto::where('empresa_id', $empresaId)->whereNotNull('categoria')->where('categoria', '!=', '')->distinct()->orderBy('categoria')->pluck('categoria'),
            'proveedoresDisponibles' => Proveedor::where('empresa_id', $empresaId)->orderBy('nombre')->get(['id', 'nombre']),
            // Lista completa (no paginada) para el buscador del modal de
            // "Reponer stock" — a diferencia de $productos de arriba, acá
            // hace falta poder encontrar cualquier producto sin importar en
            // qué página/filtro esté parado el listado.
            'productosParaReponer' => Producto::where('empresa_id', $empresaId)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo', 'stock']),
            // Si se viene desde "Editar reposición" (botón en
            // productos.reposiciones.show), el modal de reponer stock se
            // abre solo, ya cargado con las líneas de ese lote.
            'reposicionParaEditar' => $this->reposicionParaEditar($request, $empresaId),
            'descripcionFiltro' => $this->descripcionFiltroProductos($buscar, $marca, $categoria, $proveedorId),
            // Ids de TODO el resultado del filtro actual (no solo la página
            // que se ve), sin promos — se lo pasamos al JS para que el check
            // "seleccionar todo el resultado de la búsqueda" pueda tildar
            // productos de páginas que ni siquiera están renderizadas todavía.
            'idsAjustablesBusqueda' => $this->productosFiltrados($request, $buscar, $marca, $categoria, $proveedorId)
                ->where('es_promocion', false)
                ->pluck('id'),
        ]);
    }

    /**
     * Ajuste de precio por % aplicado solo a los productos tildados (a mano,
     * o con el check "seleccionar todo el resultado de la búsqueda", que en
     * el navegador tilda uno por uno todos los ids de todas las páginas del
     * filtro actual — ver `idsAjustablesBusqueda` en index()).
     *
     * Igual, nunca se confía a ciegas en los ids que mande el navegador: acá
     * se vuelven a filtrar contra `productosFiltrados()` (mismo filtro que
     * armó el listado, tomado del request) — así, si quedaron ids viejos
     * tildados de una búsqueda anterior (la selección persiste en
     * localStorage entre búsquedas), solo pasan los que además siguen
     * coincidiendo con el filtro de ahora. Mismo criterio de intersección
     * que ya usa GrupoProductoController::ajustarPrecio.
     */
    public function ajustarPrecioBusqueda(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'porcentaje' => ['required', 'numeric', 'between:-100,1000'],
            'productos_ids' => ['required', 'array', 'min:1'],
            'productos_ids.*' => ['integer'],
        ]);

        $buscar = trim((string) $request->input('buscar', ''));
        $marca = trim((string) $request->input('marca', ''));
        $categoria = trim((string) $request->input('categoria', ''));
        $proveedorId = $request->filled('proveedor_id') ? (int) $request->input('proveedor_id') : null;

        $idsSolicitados = array_map('intval', $datos['productos_ids']);

        // Las promos quedan afuera a propósito — su precio es un valor final
        // armado a mano por el admin (no un precio de catálogo), mismo
        // criterio que ya las excluye de entrar a un grupo (ni siquiera
        // tienen checkbox en la fila).
        $productos = $this->productosFiltrados($request, $buscar, $marca, $categoria, $proveedorId)
            ->where('es_promocion', false)
            ->whereIn('productos.id', $idsSolicitados)
            ->get();

        if ($productos->isEmpty()) {
            return back()->withErrors(['porcentaje' => 'Ninguno de los productos seleccionados coincide con la búsqueda actual — revisá el filtro o la selección.']);
        }

        $descripcionFiltro = $this->descripcionFiltroProductos($buscar, $marca, $categoria, $proveedorId);

        DB::transaction(function () use ($productos, $datos, $request, $descripcionFiltro) {
            $ajuste = AjustePrecioBusqueda::create([
                'empresa_id' => $request->user()->empresa_id,
                'user_id' => $request->user()->id,
                'porcentaje' => $datos['porcentaje'],
                'descripcion_filtro' => $descripcionFiltro,
                'productos_count' => $productos->count(),
            ]);

            foreach ($productos as $producto) {
                $precioAnterior = (float) $producto->precio;
                $nuevoPrecio = max(0, round($precioAnterior * (1 + $datos['porcentaje'] / 100), 2));

                $producto->update(['precio' => $nuevoPrecio]);

                $ajuste->cambios()->create([
                    'producto_id' => $producto->id,
                    'precio_anterior' => $precioAnterior,
                    'precio_nuevo' => $nuevoPrecio,
                ]);
            }
        });

        return redirect()->route('productos.index', $request->only(['buscar', 'marca', 'categoria', 'proveedor_id', 'orden', 'dir', 'por_pagina']))
            ->with('status', count($productos).' producto(s) actualizados con el ajuste de precio. Podés deshacerlo desde "Historial de ajustes de precio".');
    }

    private function reposicionParaEditar(Request $request, int $empresaId): ?array
    {
        if (! $request->filled('editar_reposicion')) {
            return null;
        }

        $reposicion = ReposicionStock::where('id', $request->input('editar_reposicion'))
            ->where('empresa_id', $empresaId)
            ->with('items')
            ->first();

        if (! $reposicion || $reposicion->fueRevertida()) {
            return null;
        }

        return [
            'id' => $reposicion->id,
            'proveedor_id' => $reposicion->proveedor_id,
            'nota' => $reposicion->nota,
            'items' => $reposicion->items->map(fn ($item) => [
                'producto_id' => $item->producto_id,
                'nombre' => $item->nombre_producto,
                'cantidad' => $item->cantidad_agregada,
            ]),
        ];
    }

    public function exportar(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $marca = trim((string) $request->input('marca', ''));
        $categoria = trim((string) $request->input('categoria', ''));
        $proveedorId = $request->filled('proveedor_id') ? (int) $request->input('proveedor_id') : null;

        $productos = $this->productosFiltrados($request, $buscar, $marca, $categoria, $proveedorId)
            ->with('proveedor')
            ->orderBy('nombre')
            ->get();

        $spreadsheet = $this->exportador->generar($productos, $request->user()->empresa->controla_stock);

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'productos.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * PDF apaisado para imprimir — o bien el listado filtrado actual
     * (búsqueda + marca/categoría/proveedor), o bien exactamente los
     * productos que el usuario tildó a mano en la lista (mismo mecanismo de
     * selección por checkbox + localStorage que ya usan Grupos/Promos:
     * `productos_ids[]` llega por POST).
     */
    public function exportarPdf(Request $request, ReportePdfService $reportes): Response
    {
        $empresaId = $request->user()->empresa_id;
        $idsSeleccionados = $request->input('productos_ids', []);

        if (! empty($idsSeleccionados)) {
            $productos = Producto::where('empresa_id', $empresaId)
                ->whereIn('id', $idsSeleccionados)
                ->with('proveedor')
                ->orderBy('nombre')
                ->get();

            $descripcionFiltro = 'Seleccionados a mano ('.$productos->count().')';
        } else {
            $buscar = trim((string) $request->input('buscar', ''));
            $marca = trim((string) $request->input('marca', ''));
            $categoria = trim((string) $request->input('categoria', ''));
            $proveedorId = $request->filled('proveedor_id') ? (int) $request->input('proveedor_id') : null;

            $productos = $this->productosFiltrados($request, $buscar, $marca, $categoria, $proveedorId)
                ->with('proveedor')
                ->orderBy('nombre')
                ->get();

            $descripcionFiltro = $this->descripcionFiltroProductos($buscar, $marca, $categoria, $proveedorId);
        }

        $pdf = $reportes->generarProductos($productos, $request->user()->empresa, $request->user()->empresa->controla_stock, $descripcionFiltro);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="productos.pdf"',
        ]);
    }

    private function descripcionFiltroProductos(string $buscar, string $marca, string $categoria, ?int $proveedorId): string
    {
        $partes = [];

        if ($buscar !== '') {
            $partes[] = 'Búsqueda: "'.$buscar.'"';
        }

        if ($marca !== '') {
            $partes[] = 'Marca: '.$marca;
        }

        if ($categoria !== '') {
            $partes[] = 'Categoría: '.$categoria;
        }

        if ($proveedorId) {
            $partes[] = 'Proveedor: '.(Proveedor::find($proveedorId)?->nombre ?? '—');
        }

        return $partes === [] ? 'Todos los productos' : implode(' — ', $partes);
    }

    private function productosFiltrados(Request $request, string $buscar, string $marca = '', string $categoria = '', ?int $proveedorId = null): Builder
    {
        return Producto::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('categoria', 'like', "%{$buscar}%")
                        ->orWhere('marca', 'like', "%{$buscar}%");
                });
            })
            ->when($marca !== '', fn ($q) => $q->where('marca', $marca))
            ->when($categoria !== '', fn ($q) => $q->where('categoria', $categoria))
            ->when($proveedorId, fn ($q) => $q->where('proveedor_id', $proveedorId));
    }

    public function create(Request $request): View
    {
        return view('productos.create', [
            'proveedores' => $this->proveedoresParaSelect($request),
        ]);
    }

    public function store(GuardarProductoRequest $request): RedirectResponse
    {
        Producto::create([
            ...$request->validated(),
            'empresa_id' => $request->user()->empresa_id,
            'activo' => true,
        ]);

        return redirect()->route('productos.index')->with('status', 'Producto creado.');
    }

    public function edit(Request $request, Producto $producto): View
    {
        $this->autorizar($request, $producto);
        $this->rechazarSiEsPromocion($producto);

        return view('productos.edit', [
            'producto' => $producto,
            'proveedores' => $this->proveedoresParaSelect($request, $producto),
        ]);
    }

    public function update(GuardarProductoRequest $request, Producto $producto): RedirectResponse
    {
        $this->autorizar($request, $producto);
        $this->rechazarSiEsPromocion($producto);

        $producto->update($request->validated());

        return redirect()->route('productos.index')->with('status', 'Producto actualizado.');
    }

    public function activar(Request $request, Producto $producto): RedirectResponse
    {
        $this->autorizar($request, $producto);

        $producto->update(['activo' => true]);

        return back()->with('status', 'Producto activado.');
    }

    public function desactivar(Request $request, Producto $producto): RedirectResponse
    {
        $this->autorizar($request, $producto);

        $producto->update(['activo' => false]);

        return back()->with('status', 'Producto desactivado.');
    }

    private function autorizar(Request $request, Producto $producto): void
    {
        if ($producto->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }

    private function rechazarSiEsPromocion(Producto $producto): void
    {
        if ($producto->es_promocion) {
            abort(404);
        }
    }

    private function proveedoresParaSelect(Request $request, ?Producto $producto = null)
    {
        return Proveedor::query()
            ->where('empresa_id', $request->user()->empresa_id)
            ->where(function ($query) use ($producto) {
                $query->where('activo', true);

                if ($producto?->proveedor_id) {
                    $query->orWhere('id', $producto->proveedor_id);
                }
            })
            ->orderBy('nombre')
            ->get();
    }
}
