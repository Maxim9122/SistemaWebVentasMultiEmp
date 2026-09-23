const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

/**
 * Wrapper chico sobre fetch: manda el token CSRF de sesión (mismo mecanismo
 * que cualquier form Blade con @csrf, no Sanctum/tokens — esta app vive en
 * el mismo dominio y usa la misma cookie de sesión) y normaliza los
 * errores de validación 422 de Laravel a una forma fácil de mostrar.
 */
async function llamar(url, opciones = {}) {
    const respuesta = await fetch(url, {
        ...opciones,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': token,
            ...(opciones.headers || {}),
        },
    });

    const datos = await respuesta.json().catch(() => ({}));

    if (!respuesta.ok) {
        const error = new Error(datos.message || 'Ocurrió un error inesperado.');
        error.status = respuesta.status;
        error.errores = datos.errors || {};
        throw error;
    }

    return datos;
}

export function obtenerEstado() {
    return llamar('/venta-react/estado');
}

export function agregarItem(body) {
    return llamar('/venta-react/items', { method: 'POST', body: JSON.stringify(body) });
}

export function actualizarItem(itemId, body) {
    return llamar(`/venta-react/items/${itemId}`, { method: 'PUT', body: JSON.stringify(body) });
}

export function quitarItem(itemId) {
    return llamar(`/venta-react/items/${itemId}`, { method: 'DELETE' });
}

export function renombrarCliente(clienteNombre) {
    return llamar('/venta-react/cliente', { method: 'PUT', body: JSON.stringify({ cliente_nombre: clienteNombre }) });
}

export function cerrarCarrito(body) {
    return llamar('/venta-react/cerrar', { method: 'POST', body: JSON.stringify(body) });
}
