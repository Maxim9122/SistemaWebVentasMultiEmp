<?php

namespace App\Services\Facturacion\Exceptions;

/**
 * El CUIT que mandamos en POST /api/platform/v1/empresas ya pertenece a una
 * empresa registrada por otra integración o cargada a mano en el panel de
 * la API de facturación (HTTP 409). No se tocó nada de esa empresa del
 * otro lado — esta excepción solo informa que no se pudo continuar.
 */
class CuitYaRegistradoException extends \RuntimeException {}
