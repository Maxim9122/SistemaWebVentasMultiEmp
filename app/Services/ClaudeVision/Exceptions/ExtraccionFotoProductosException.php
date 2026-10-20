<?php

namespace App\Services\ClaudeVision\Exceptions;

/**
 * Cualquier motivo por el que no se pudo extraer una lista de productos de
 * una foto (falla de red/HTTP, respuesta sin el schema esperado, o ningún
 * producto detectado) — el mensaje ya viene listo para mostrarle al usuario
 * tal cual, nunca expone detalle técnico.
 */
class ExtraccionFotoProductosException extends \RuntimeException {}
