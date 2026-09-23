import { useEffect, useState } from 'react';
import BuscadorProducto from './BuscadorProducto';
import TablaCarrito from './TablaCarrito';
import PanelCobro from './PanelCobro';
import CarritosSwitcher from './CarritosSwitcher';
import * as api from '../api';

export default function VentaApp({ nombreVendedor, urlCarritos }) {
    const [catalogo, setCatalogo] = useState(null);
    const [carritos, setCarritos] = useState([]);
    const [pedidoId, setPedidoId] = useState(null);
    const [estado, setEstado] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [operando, setOperando] = useState(false);
    const [error, setError] = useState(null);
    const [mensajeExito, setMensajeExito] = useState(null);

    // Carga inicial: catálogo + lista de carritos abiertos. Si el vendedor
    // no tiene ninguno todavía, se crea uno (mismo comportamiento que
    // GET /carritos del flujo Blade, que nunca deja la pantalla sin carrito).
    useEffect(() => {
        (async () => {
            try {
                const [datosCatalogo, datosCarritos] = await Promise.all([api.obtenerCatalogo(), api.listarCarritos()]);
                setCatalogo(datosCatalogo);

                let listaCarritos = datosCarritos.carritos;
                let idInicial;

                if (listaCarritos.length === 0) {
                    const creado = await api.crearCarrito();
                    idInicial = creado.pedidoId;
                    listaCarritos = (await api.listarCarritos()).carritos;
                } else {
                    idInicial = listaCarritos[0].id;
                }

                setCarritos(listaCarritos);
                setPedidoId(idInicial);
                setEstado(await api.obtenerEstado(idInicial));
            } catch (e) {
                setError(e.message);
            } finally {
                setCargando(false);
            }
        })();
    }, []);

    async function refrescarListaCarritos() {
        try {
            setCarritos((await api.listarCarritos()).carritos);
        } catch (e) { /* no crítico, se reintenta en la próxima acción */ }
    }

    async function ejecutar(promesa, { refrescarLista = true } = {}) {
        setOperando(true);
        setError(null);

        try {
            const resultado = await promesa;
            setEstado((prev) => ({ ...prev, ...resultado }));
            if (refrescarLista) refrescarListaCarritos();
            return resultado;
        } catch (e) {
            setError(e.message);
            throw e;
        } finally {
            setOperando(false);
        }
    }

    function agregarProducto(producto) {
        ejecutar(api.agregarItem(pedidoId, { producto_id: producto.id, cantidad: 1 })).catch(() => {});
    }

    function cambiarCantidad(item, nuevaCantidad) {
        if (nuevaCantidad < 1) return;
        ejecutar(api.actualizarItem(pedidoId, item.id, { cantidad: nuevaCantidad })).catch(() => {});
    }

    function quitar(item) {
        ejecutar(api.quitarItem(pedidoId, item.id)).catch(() => {});
    }

    function guardarNombreCliente(nombre) {
        ejecutar(api.renombrarCliente(pedidoId, nombre)).catch(() => {});
    }

    async function cambiarCarrito(nuevoId) {
        setOperando(true);
        setError(null);
        try {
            const nuevoEstado = await api.obtenerEstado(nuevoId);
            setPedidoId(nuevoId);
            setEstado(nuevoEstado);
        } catch (e) {
            setError(e.message);
        } finally {
            setOperando(false);
        }
    }

    async function nuevoCarrito() {
        setOperando(true);
        setError(null);
        try {
            const { pedidoId: id } = await api.crearCarrito();
            await refrescarListaCarritos();
            await cambiarCarrito(id);
        } catch (e) {
            setError(e.message);
        } finally {
            setOperando(false);
        }
    }

    async function cerrar(body) {
        const resultado = await ejecutar(api.cerrarCarrito(pedidoId, body), { refrescarLista: false });
        setMensajeExito('¡Venta cobrada! Redirigiendo...');
        setTimeout(() => { window.location.href = resultado.redirect; }, 900);
        return resultado;
    }

    if (cargando) {
        return <div className="p-6 text-sm text-slate-500">Cargando...</div>;
    }

    if (!estado || !catalogo) {
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
                    <CarritosSwitcher
                        carritos={carritos}
                        pedidoActualId={pedidoId}
                        clienteNombre={estado.pedido.cliente_nombre}
                        permiteMultiplesCarritos={catalogo.config.permiteMultiplesCarritos}
                        deshabilitado={operando}
                        onCambiarCarrito={cambiarCarrito}
                        onNuevoCarrito={nuevoCarrito}
                        onGuardarNombre={guardarNombreCliente}
                    />

                    <div className="bg-white rounded-lg shadow p-4">
                        <BuscadorProducto productos={catalogo.productos} onAgregar={agregarProducto} deshabilitado={operando} />
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
                        config={catalogo.config}
                        clientes={catalogo.clientes}
                        deshabilitado={operando || estado.items.length === 0}
                        onCerrar={cerrar}
                    />
                </div>
            </main>
        </div>
    );
}
