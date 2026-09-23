import { useEffect, useState } from 'react';

/**
 * Fila de arriba del carrito: nombre del cliente del carrito ACTUAL
 * (editable, mismo campo que "renombrar" en el flujo Blade — no confundir
 * con el cliente_id que se elige recién al cobrar para fiado/factura), y
 * los demás carritos abiertos del mismo vendedor como "chips" para saltar
 * entre ellos sin perder ninguno — mismo criterio visual que
 * carritos/show.blade.php.
 */
export default function CarritosSwitcher({
    carritos,
    pedidoActualId,
    clienteNombre,
    permiteMultiplesCarritos,
    deshabilitado,
    onCambiarCarrito,
    onNuevoCarrito,
    onGuardarNombre,
}) {
    const [nombreEditado, setNombreEditado] = useState(clienteNombre || '');

    useEffect(() => { setNombreEditado(clienteNombre || ''); }, [clienteNombre, pedidoActualId]);

    const otros = carritos.filter((c) => c.id !== pedidoActualId);

    return (
        <div className="mb-4 flex gap-2 flex-wrap items-center">
            <div className="flex items-center gap-1 rounded bg-slate-900 pl-3 pr-1.5 py-1">
                <input
                    type="text"
                    value={nombreEditado}
                    onChange={(e) => setNombreEditado(e.target.value)}
                    onKeyDown={(e) => { if (e.key === 'Enter') onGuardarNombre(nombreEditado); }}
                    placeholder="Nombre del cliente"
                    maxLength={255}
                    className="bg-transparent text-white text-sm placeholder-slate-400 border-0 p-0 w-36 focus:ring-0 focus:outline-none"
                />
                <button
                    type="button"
                    onClick={() => onGuardarNombre(nombreEditado)}
                    title="Guardar nombre"
                    className="text-slate-300 hover:text-white text-sm leading-none px-1"
                >✓</button>
            </div>

            {otros.map((otro) => (
                <button
                    key={otro.id}
                    type="button"
                    onClick={() => onCambiarCarrito(otro.id)}
                    className="rounded px-3 py-1.5 text-sm bg-white border border-slate-200 text-slate-600 hover:border-slate-400"
                >
                    {otro.cliente_nombre && otro.cliente_nombre !== 'Consumidor Final' ? otro.cliente_nombre : `Carrito #${otro.id}`}
                    <span className="text-slate-400 ml-1">({otro.items_count})</span>
                </button>
            ))}

            {permiteMultiplesCarritos && (
                <button
                    type="button"
                    disabled={deshabilitado}
                    onClick={onNuevoCarrito}
                    title="Agregar otro carrito"
                    className="w-8 h-8 flex items-center justify-center rounded text-base font-semibold bg-white border border-slate-200 text-slate-600 hover:border-slate-400 disabled:opacity-40"
                >+</button>
            )}
        </div>
    );
}
