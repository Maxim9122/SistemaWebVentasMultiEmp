<?php

namespace App\Services\ClaudeVision\Exceptions;

/**
 * Cualquier motivo por el que no se pudo extraer una lista (de productos o
 * de clientes) de una foto — falla de red/HTTP, respuesta sin el schema
 * esperado, o nada detectado. El mensaje ya viene listo para mostrarle al
 * usuario tal cual, nunca expone detalle técnico.
 */
class ExtraccionFotoException extends \RuntimeException {}
