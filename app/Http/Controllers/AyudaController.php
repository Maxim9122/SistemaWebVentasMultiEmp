<?php

namespace App\Http\Controllers;

use App\Support\Manual\ContenidoAyuda;
use Illuminate\View\View;

/**
 * Centro de ayuda: manual navegable por secciones y temas, con contenido
 * estático (ver ContenidoAyuda) — no es un agente de IA a propósito, así
 * las respuestas nunca se inventan ni tienen costo por consulta.
 */
class AyudaController extends Controller
{
    public function index(): View
    {
        return view('ayuda.index', ['secciones' => ContenidoAyuda::secciones()]);
    }

    public function seccion(string $seccion): View
    {
        $datos = ContenidoAyuda::seccion($seccion);
        abort_if(! $datos, 404);

        return view('ayuda.seccion', ['slug' => $seccion, 'seccion' => $datos]);
    }

    public function tema(string $seccion, string $tema): View
    {
        $datosSeccion = ContenidoAyuda::seccion($seccion);
        abort_if(! $datosSeccion, 404);

        $datosTema = ContenidoAyuda::tema($seccion, $tema);
        abort_if(! $datosTema, 404);

        return view('ayuda.tema', [
            'seccionSlug' => $seccion,
            'seccion' => $datosSeccion,
            'tema' => $datosTema,
        ]);
    }
}
