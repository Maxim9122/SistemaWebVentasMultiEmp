<?php

namespace App\Http\Controllers;

use App\Support\Manual\ContenidoAyuda;
use Illuminate\View\View;

/**
 * Centro de ayuda: manual navegable por secciones y temas, con contenido
 * estático (ver ContenidoAyuda) — no es un agente de IA a propósito, así
 * las respuestas nunca se inventan ni tienen costo por consulta.
 *
 * Cada pantalla existe en dos versiones que comparten el mismo contenido:
 * la normal (layouts.app, página completa) y la "widget" (layouts.ayuda-widget,
 * sin sidebar/header — pensada para cargarse en el <iframe> del globo
 * flotante de ayuda, ver partials.ayuda-flotante).
 */
class AyudaController extends Controller
{
    public function index(): View
    {
        return view('ayuda.index', ['secciones' => ContenidoAyuda::secciones()]);
    }

    public function seccion(string $seccion): View
    {
        return view('ayuda.seccion', $this->datosSeccion($seccion));
    }

    public function tema(string $seccion, string $tema): View
    {
        return view('ayuda.tema', $this->datosTema($seccion, $tema));
    }

    public function indexWidget(): View
    {
        return view('ayuda.widget.index', ['secciones' => ContenidoAyuda::secciones()]);
    }

    public function seccionWidget(string $seccion): View
    {
        return view('ayuda.widget.seccion', $this->datosSeccion($seccion));
    }

    public function temaWidget(string $seccion, string $tema): View
    {
        return view('ayuda.widget.tema', $this->datosTema($seccion, $tema));
    }

    private function datosSeccion(string $seccion): array
    {
        $datos = ContenidoAyuda::seccion($seccion);
        abort_if(! $datos, 404);

        return ['slug' => $seccion, 'seccion' => $datos];
    }

    private function datosTema(string $seccion, string $tema): array
    {
        $datosSeccion = ContenidoAyuda::seccion($seccion);
        abort_if(! $datosSeccion, 404);

        $datosTema = ContenidoAyuda::tema($seccion, $tema);
        abort_if(! $datosTema, 404);

        return [
            'seccionSlug' => $seccion,
            'seccion' => $datosSeccion,
            'tema' => $datosTema,
        ];
    }
}
