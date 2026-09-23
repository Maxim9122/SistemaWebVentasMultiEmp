function formatearMoneda(n) {
    return Number(n).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export default function TablaCarrito({ items, total, cargando, onCambiarCantidad, onQuitar }) {
    if (items.length === 0) {
        return (
            <div className="bg-white rounded-lg shadow p-6 text-center text-sm text-slate-500">
                El carrito está vacío — buscá un producto arriba para empezar.
            </div>
        );
    }

    return (
        <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full text-sm">
                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th className="px-4 py-2">Producto</th>
                        <th className="px-4 py-2 w-36">Cantidad</th>
                        <th className="px-4 py-2 text-right">Precio</th>
                        <th className="px-4 py-2 text-right">Subtotal</th>
                        <th className="px-4 py-2 w-10"></th>
                    </tr>
                </thead>
                <tbody className="divide-y">
                    {items.map((item) => (
                        <tr key={item.id}>
                            <td className="px-4 py-2">
                                {item.nombre_producto}
                                {item.precio_manual && <span className="ml-1 text-xs text-amber-600">(precio manual)</span>}
                            </td>
                            <td className="px-4 py-2">
                                <div className="flex items-center gap-1">
                                    <button
                                        type="button"
                                        disabled={cargando || item.cantidad <= 1}
                                        onClick={() => onCambiarCantidad(item, item.cantidad - 1)}
                                        className="w-7 h-7 rounded border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                                    >−</button>
                                    <span className="w-8 text-center">{item.cantidad}</span>
                                    <button
                                        type="button"
                                        disabled={cargando}
                                        onClick={() => onCambiarCantidad(item, item.cantidad + 1)}
                                        className="w-7 h-7 rounded border border-slate-300 text-slate-600 hover:bg-slate-50 disabled:opacity-40"
                                    >+</button>
                                </div>
                            </td>
                            <td className="px-4 py-2 text-right">${formatearMoneda(item.precio_unitario)}</td>
                            <td className="px-4 py-2 text-right font-medium">${formatearMoneda(item.subtotal)}</td>
                            <td className="px-4 py-2 text-right">
                                <button
                                    type="button"
                                    disabled={cargando}
                                    onClick={() => onQuitar(item)}
                                    className="text-slate-400 hover:text-red-600 disabled:opacity-40"
                                    aria-label="Quitar"
                                >✕</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            <div className="px-4 py-3 bg-slate-50 border-t flex justify-between items-center">
                <span className="text-sm text-slate-500">Total</span>
                <span className="text-lg font-semibold">${formatearMoneda(total)}</span>
            </div>
        </div>
    );
}
