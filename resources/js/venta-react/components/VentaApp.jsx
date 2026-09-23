import { useEffect, useState } from 'react';
import BuscadorProducto from './BuscadorProducto';
import TablaCarrito from './TablaCarrito';
import PanelCobro from './PanelCobro';
import * as api from '../api';

export default function VentaApp({ nombreVendedor, urlCarritos }) {
    const [estado, setEstado] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [operando, setOperando] = useState(false);
    const [error, setError] = useState(null);
    const [mensajeExito, setMensajeExito] = useState(null);

    useEffect(() => {
        api.obtenerEstado()
            .then(setEstado)
            .catch((e) => setError(e.message))
            .finally(() => setCargando(false));
    }, []);

    async function ejecutar(promesa) {
        setOperando(true);
        setError(null);

        try {
            const resultado = await promesa;
            setEstado((prev) => ({ ...prev, ...resultado }));
            return resultado;
        } catch (e) {
            setError(e.message);
            throw e;
        } finally {
            setOperando(false);
        }
    }

    function agregarProducto(producto) {
        ejecutar(api.agregarItem({ producto_id: producto.id, cantidad: 1 })).catch(() => {});
    }

    function cambiarCantidad(item, nuevaCantidad) {
        if (nuevaCantidad < 1) return;
        ejecutar(api.actualizarItem(item.id, { cantidad: nuevaCantidad })).catch(() => {});
    }

    function quitar(item) {
        ejecutar(api.quitarItem(item.id)).catch(() => {});
    }

    async function cerrar(body) {
        const resultado = await ejecutar(api.cerrarCarrito(body));
        setMensajeExito('¡Venta cobrada! Redirigiendo...');
        setTimeout(() => { window.location.href = resultado.redirect; }, 900);
        return resultado;
    }

    if (cargando) {
        return <div className="p-6 text-sm text-slate-500">Cargando...</div>;
    }

    if (!estado) {
        return <div className="p-6 text-sm text-red-600">No se pudo cargar el carrito: {error}</div>;
    }

    return (
        <div className="min-h-screen flex flex-col">
            <header className="bg-slate-900 text-white px-6 py-3 flex items-center justify-between">
                <div>
                    <span className="font-semibold">Venta</span>
                    <span className="text-slate-400 text-sm ml-2">(experimento React — solo local)</span>
                </div>
                <div className="text-sm text-slate-300 flex items-center gap-4">
                    <span>{nombreVendedor}</span>
                    <a href={urlCarritos} className="hover:underline">Volver al carrito normal</a>
                </div>
            </header>

            {mensajeExito && (
                <div className="bg-emerald-50 border-b border-emerald-200 text-emerald-800 text-sm px-6 py-2">{mensajeExito}</div>
            )}
            {error && !mensajeExito && (
                <div className="bg-red-50 border-b border-red-200 text-red-800 text-sm px-6 py-2">{error}</div>
            )}

            <main className="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4 p-6">
                <div className="lg:col-span-2 space-y-4">
                    <div className="bg-white rounded-lg shadow p-4">
                        <BuscadorProducto productos={estado.productos} onAgregar={agregarProducto} deshabilitado={operando} />
                    </div>

                    <TablaCarrito
                        items={estado.items}
                        total={estado.pedido.total}
                        cargando={operando}
                        onCambiarCantidad={cambiarCantidad}
                        onQuitar={quitar}
                    />
                </div>

                <div>
                    <PanelCobro
                        total={estado.pedido.total}
                        config={estado.config}
                        clientes={estado.clientes}
                        deshabilitado={operando || estado.items.length === 0}
                        onCerrar={cerrar}
                    />
                </div>
            </main>
        </div>
    );
}
