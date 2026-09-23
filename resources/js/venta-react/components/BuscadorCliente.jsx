import { useMemo, useState } from 'react';

/** Mismo criterio de teclado que BuscadorProducto — ver ese archivo. */
export default function BuscadorCliente({ clientes, clienteSeleccionado, onSeleccionar }) {
    const [texto, setTexto] = useState('');
    const [indiceActivo, setIndiceActivo] = useState(-1);

    const resultados = useMemo(() => {
        const q = texto.trim().toLowerCase();
        if (q === '') return [];

        return clientes
            .filter((c) => c.nombre.toLowerCase().includes(q) || (c.cuit && c.cuit.toLowerCase().includes(q)))
            .slice(0, 8);
    }, [texto, clientes]);

    function seleccionar(cliente) {
        onSeleccionar(cliente);
        setTexto('');
        setIndiceActivo(-1);
    }

    function alTecla(evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            const elegido = indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null);
            if (elegido) seleccionar(elegido);
            return;
        }

        if (resultados.length === 0) return;

        if (evento.key === 'ArrowDown') {
            evento.preventDefault();
            setIndiceActivo((i) => (i + 1) % resultados.length);
        } else if (evento.key === 'ArrowUp') {
            evento.preventDefault();
            setIndiceActivo((i) => (i - 1 + resultados.length) % resultados.length);
        }
    }

    return (
        <div className="relative">
            <label htmlFor="buscador_cliente_react" className="block text-sm font-medium mb-1">Cliente</label>

            {clienteSeleccionado ? (
                <div className="flex items-center justify-between rounded border border-slate-300 px-3 py-2 text-sm">
                    <span>{clienteSeleccionado.nombre}{clienteSeleccionado.cuit ? ` (${clienteSeleccionado.cuit})` : ''}</span>
                    <button type="button" onClick={() => onSeleccionar(null)} className="text-xs text-slate-500 hover:underline">Cambiar</button>
                </div>
            ) : (
                <>
                    <input
                        id="buscador_cliente_react"
                        type="text"
                        autoComplete="off"
                        placeholder="Buscar por nombre o CUIT..."
                        value={texto}
                        onChange={(e) => { setTexto(e.target.value); setIndiceActivo(-1); }}
                        onKeyDown={alTecla}
                        onBlur={() => setTimeout(() => setTexto((t) => t), 100)}
                        className="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500"
                    />
                    {resultados.length > 0 && (
                        <ul className="absolute z-10 mt-1 w-full max-h-48 overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                            {resultados.map((cliente, indice) => (
                                <li
                                    key={cliente.id}
                                    onMouseDown={(e) => { e.preventDefault(); seleccionar(cliente); }}
                                    className={`px-3 py-2 text-sm cursor-pointer ${indice === indiceActivo ? 'bg-slate-100' : ''}`}
                                >
                                    {cliente.nombre}{cliente.cuit ? ` (${cliente.cuit})` : ''}
                                </li>
                            ))}
                        </ul>
                    )}
                </>
            )}
        </div>
    );
}
