<?php

namespace App\Services\Facturacion\Exceptions;

/**
 * La empresa ya tiene un certificado AFIP validado del otro lado — corregir
 * razón social/CUIT/email a esta altura es un cambio de identidad de una
 * empresa ya verificada, no algo que se pueda resolver con un PATCH desde
 * acá. No es un error transitorio: no tiene sentido reintentar.
 */
class CertificadoYaValidadoException extends \RuntimeException {}
