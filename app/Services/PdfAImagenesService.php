<?php

namespace App\Services;

use Imagick;
use ImagickPixel;

/**
 * Convierte cada página de un PDF en una imagen JPEG separada — para poder
 * mandarlas por el mismo camino que ya usa "Importar desde una foto"
 * (Claude Vision), en vez de parsear texto/tablas de PDF a mano (poco
 * confiable: la mayoría no trae una estructura de tabla limpia para extraer
 * columnas). Usa Imagick con el delegado de Ghostscript, ya instalados en el
 * servidor — sin agregar ninguna dependencia nueva.
 *
 * A propósito accede a cada página por índice explícito (setIteratorIndex +
 * getImage), nunca con un `foreach ($imagick as $pagina)` reasignando la
 * variable del loop adentro — eso corrompe el cursor interno del iterador de
 * Imagick y lo deja recorriendo de más sin parar (probado: un PDF de 2
 * páginas así generó miles de imágenes antes de cortarlo a mano).
 */
class PdfAImagenesService
{
    private const LIMITE_PAGINAS = 30;

    /**
     * @return list<string> rutas absolutas de las imágenes generadas (una
     *                       por página, en orden) — el caller es
     *                       responsable de borrarlas después de usarlas.
     *
     * @throws \RuntimeException si el PDF tiene más páginas que el límite.
     */
    public function convertir(string $rutaPdf, string $directorioDestino, int $dpi = 150): array
    {
        // Paso barato (pingImage no decodifica los píxeles) para frenar
        // antes de cargar un PDF gigante entero en memoria.
        $ping = new Imagick();
        $ping->pingImage($rutaPdf);
        $cantidadPaginas = $ping->getNumberImages();
        $ping->clear();

        if ($cantidadPaginas > self::LIMITE_PAGINAS) {
            throw new \RuntimeException(
                "El PDF tiene {$cantidadPaginas} páginas — el máximo admitido es ".self::LIMITE_PAGINAS.'. Probá separándolo en partes más chicas.'
            );
        }

        $imagick = new Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($rutaPdf);

        $totalPaginas = $imagick->getNumberImages();
        $rutas = [];

        for ($indice = 0; $indice < $totalPaginas; $indice++) {
            $imagick->setIteratorIndex($indice);
            $pagina = $imagick->getImage();

            $pagina->setImageFormat('jpeg');
            $pagina->setImageCompressionQuality(85);
            $pagina->setImageBackgroundColor(new ImagickPixel('white'));
            $fusionada = $pagina->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);

            $ruta = rtrim($directorioDestino, '/')."/pagina-{$indice}.jpg";
            $fusionada->writeImage($ruta);
            $rutas[] = $ruta;

            $fusionada->clear();
            $pagina->clear();
        }

        $imagick->clear();

        return $rutas;
    }
}
