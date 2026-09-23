import { useMemo, useState } from 'react';
import BuscadorCliente from './BuscadorCliente';

function calcularAjustado(total, medio, ajustes) {
    const pct = ajustes[medio] || 0;
    return Math.round(total * (1 + pct / 100) * 100) / 100;
}

/**
 * Versión simplificada del formulario de cobro real (formulario-cobro.blade.php):
 * cubre el camino más común (un solo medio de pago, remito o factura,
 * cliente para fiado/factura) — a propósito no incluye pago dividido en
 * varios medios ni el botón de WhatsApp todavía, para no inflar el
 * prototipo antes de validar si el rumbo en React vale la pena.
 */
export default function PanelCobro({ total, config, clientes, deshabilitado, onCerrar }) {
    const [medio, setMedio] = useState('efectivo');
    const [tipoComprobante, setTipoComprobante] = useState('remito');
    const [tipoFactura, setTipoFactura] = useState('B');
    const [cliente, setCliente] = useState(null);
    const [errores, setErrores] = useState({});
    const [enviando, setEnviando] = useState(false);

    const ajustado = useMemo(() => calcularAjustado(total, medio, config.ajustes), [total, medio, config.ajustes]);
    const necesitaCliente = medio === 'fiado' || tipoComprobante === 'factura';

    async function confirmar() {
        setErrores({});
        setEnviando(true);

        const body = {
            destino: 'inmediato',
            tipo_comprobante: tipoComprobante,
            tipo_factura: tipoComprobante === 'factura' ? tipoFactura : null,
            monto_efectivo: medio === 'efectivo' ? ajustado : 0,
            monto_tarjeta: medio === 'tarjeta' ? ajustado : 0,
            monto_transferencia: medio === 'transferencia' ? ajustado : 0,
            monto_fiado: medio === 'fiado' ? total : 0,
            cliente_id: cliente?.id ?? null,
        };

        try {
            await onCerrar(body);
        } catch (error) {
            setErrores(error.errores || { general: [error.message] });
        } finally {
            setEnviando(false);
        }
    }

    return (
        <div className="bg-white rounded-lg shadow p-4 space-y-4">
            <p className="font-medium">Cobrar</p>

            {Object.values(errores).flat().map((msg, i) => (
                <p key={i} className="text-sm text-red-600">{msg}</p>
            ))}

            <div>
                <label className="block text-sm font-medium mb-1">Medio de pago</label>
                <select value={medio} onChange={(e) => setMedio(e.target.value)} className="w-full rounded border border-slate-300 text-sm">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                    {config.permiteFiado && <option value="fiado">Fiado (a crédito)</option>}
                </select>
                <p className="text-xs text-slate-500 mt-1">
                    {medio === 'fiado' ? `Queda fiado: $${total.toFixed(2)}` : `A cobrar: $${ajustado.toFixed(2)}`}
                </p>
            </div>

            <div>
                <label className="block text-sm font-medium mb-1">Comprobante</label>
                <select value={tipoComprobante} onChange={(e) => setTipoComprobante(e.target.value)} className="w-full rounded border border-slate-300 text-sm">
                    <option value="remito">Remito</option>
                    <option value="factura">Factura</option>
                </select>
            </div>

            {tipoComprobante === 'factura' && (
                <div>
                    <label className="block text-sm font-medium mb-1">Tipo de factura</label>
                    <select value={tipoFactura} onChange={(e) => setTipoFactura(e.target.value)} className="w-full rounded border border-slate-300 text-sm">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                    </select>
                </div>
            )}

            {necesitaCliente && (
                <BuscadorCliente clientes={clientes} clienteSeleccionado={cliente} onSeleccionar={setCliente} />
            )}

            <button
                type="button"
                disabled={deshabilitado || enviando}
                onClick={confirmar}
                className="w-full rounded bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:bg-slate-800 disabled:opacity-50"
            >
                {enviando ? 'Procesando...' : 'Confirmar cobro'}
            </button>
        </div>
    );
}
