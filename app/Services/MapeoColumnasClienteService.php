<?php

namespace App\Services;

/**
 * Mismo algoritmo de `MapeoColumnasService` (normalizar + sinónimo exacto +
 * similitud), aplicado al esquema de Clientes en vez de Productos.
 */
class MapeoColumnasClienteService extends MapeoColumnasService
{
    public const CAMPOS = ['nombre', 'cuit', 'telefono', 'email'];

    protected const SINONIMOS = [
        'nombre' => ['nombre', 'cliente', 'razon social', 'apellido y nombre', 'apellido nombre', 'denominacion', 'contacto'],
        'cuit' => ['cuit', 'dni', 'documento', 'cuit dni', 'nro documento', 'numero documento', 'cuit cuil'],
        'telefono' => ['telefono', 'tel', 'celular', 'whatsapp', 'nro telefono', 'numero telefono'],
        'email' => ['email', 'correo', 'mail', 'correo electronico', 'e mail'],
    ];
}
