<?php

namespace App\Http\Controllers;

use App\Models\GrupoProducto;
use App\Services\ExportadorProductosExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GrupoProductoController extends Controller
{
    public function __construct(private readonly ExportadorProductosExcel $exportador) {}

    public function index(Request $request): View
    {
        $grupos = GrupoProducto::where('empresa_id', $request->user()->empresa_id)
            ->withCount('productos')
            ->orderBy('nombre')
            ->get();

        return view('productos.grupos.index', ['grupos' => $grupos]);
    }

    public function store(Request $request): RedirectResponse
    {
        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'max:255',
                Rule::unique('grupos_productos', 'nombre')->where('empresa_id', $empresaId),
            ],
            'productos_ids' => ['required', 'array', 'min:1'],
            'productos_ids.*' => [
                'integer',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId),
            ],
        ]);

        $grupo = GrupoProducto::create([
            'empresa_id' => $empresaId,
            'nombre' => $datos['nombre'],
        ]);

        $grupo->productos()->sync($datos['productos_ids']);

        return redirect()->route('productos.grupos.show', $grupo)->with('status', 'Grupo creado.');
    }

    public function agregarProductos(Request $request, GrupoProducto $grupo): RedirectResponse
    {
        $this->autorizar($request, $grupo);

        $empresaId = $request->user()->empresa_id;

        $datos = $request->validate([
            'productos_ids' => ['required', 'array', 'min:1'],
            'productos_ids.*' => [
                'integer',
                Rule::exists('productos', 'id')->where('empresa_id', $empresaId),
            ],
        ]);

        $yaEstaban = $grupo->productos()->whereIn('productos.id', $datos['productos_ids'])->count();
        $grupo->productos()->syncWithoutDetaching($datos['productos_ids']);
        $agregados = count($datos['productos_ids']) - $yaEstaban;

        $mensaje = $agregados > 0 ? "{$agregados} producto(s) agregado(s) al grupo." : 'No había productos nuevos para agregar.';

        if ($yaEstaban > 0) {
            $mensaje .= " {$yaEstaban} ya estaba(n) en el grupo.";
        }

        return redirect()->route('productos.grupos.show', $grupo)->with('status', $mensaje);
    }

    public function show(Request $request, GrupoProducto $grupo): View
    {
        $this->autorizar($request, $grupo);

        return view('productos.grupos.show', [
            'grupo' => $grupo,
            'productos' => $grupo->productos()->orderBy('nombre')->get(),
        ]);
    }

    public function exportar(Request $request, GrupoProducto $grupo): StreamedResponse
    {
        $this->autorizar($request, $grupo);

        $productos = $grupo->productos()->with('proveedor')->orderBy('nombre')->get();
        $spreadsheet = $this->exportador->generar($productos, $request->user()->empresa->controla_stock);
        $nombreArchivo = Str::slug($grupo->nombre).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $nombreArchivo, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function destroy(Request $request, GrupoProducto $grupo): RedirectResponse
    {
        $this->autorizar($request, $grupo);

        $grupo->delete();

        return redirect()->route('productos.grupos.index')->with('status', 'Grupo eliminado.');
    }

    public function ajustarPrecio(Request $request, GrupoProducto $grupo): RedirectResponse
    {
        $this->autorizar($request, $grupo);

        $datos = $request->validate([
            'porcentaje' => ['required', 'numeric', 'between:-100,1000'],
            'alcance' => ['required', Rule::in(['todos', 'seleccionados'])],
            'productos_ids' => ['required_if:alcance,seleccionados', 'array'],
            'productos_ids.*' => ['integer'],
        ]);

        $productosQuery = $grupo->productos();

        if ($datos['alcance'] === 'seleccionados') {
            $idsDelGrupo = $grupo->productos()->pluck('productos.id');
            $idsSolicitados = array_map('intval', $datos['productos_ids'] ?? []);
            $idsValidos = $idsDelGrupo->intersect($idsSolicitados);

            if ($idsValidos->isEmpty()) {
                return back()->withErrors(['productos_ids' => 'Tildá al menos un producto del grupo.']);
            }

            $productosQuery->whereIn('productos.id', $idsValidos);
        }

        $productos = $productosQuery->get();

        foreach ($productos as $producto) {
            $nuevoPrecio = round((float) $producto->precio * (1 + $datos['porcentaje'] / 100), 2);
            $producto->update(['precio' => max(0, $nuevoPrecio)]);
        }

        return redirect()->route('productos.grupos.show', $grupo)
            ->with('status', count($productos).' producto(s) actualizados.');
    }

    private function autorizar(Request $request, GrupoProducto $grupo): void
    {
        if ($grupo->empresa_id !== $request->user()->empresa_id) {
            abort(404);
        }
    }
}
