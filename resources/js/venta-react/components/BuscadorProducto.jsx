import { useMemo, useRef, useState } from 'react';

/**
 * Mismo criterio de teclado que el buscador vainilla-JS del resto del
 * sitio (ver partials con buscador_producto/buscador_cliente, y el fix de
 * "los buscadores de cliente no se comportaban igual" en el historial de
 * este proyecto): tipear filtra en vivo, flechas navegan, Enter selecciona
 * lo resaltado o el único resultado si el texto dejó uno solo — nunca
 * selecciona a ciegas si es ambiguo.
 */
export default function BuscadorProducto({ productos, onAgregar, deshabilitado }) {
    const [texto, setTexto] = useState('');
    const [indiceActivo, setIndiceActivo] = useState(-1);
    const inputRef = useRef(null);

    const resultados = useMemo(() => {
        const q = texto.trim().toLowerCase();
        if (q === '') return [];

        return productos
            .filter((p) => p.nombre.toLowerCase().includes(q) || (p.codigo && p.codigo.toLowerCase().includes(q)))
            .slice(0, 8);
    }, [texto, productos]);

    function seleccionar(producto) {
        onAgregar(producto, 1);
        setTexto('');
        setIndiceActivo(-1);
    }

    function alTipear(evento) {
        setTexto(evento.target.value);
        setIndiceActivo(-1);
    }

    function alTecla(evento) {
        if (evento.key === 'Enter') {
            evento.preventDefault();

            // Coincidencia exacta de código (lector de código de barras) —
            // mismo criterio que el carrito actual, prioridad sobre lo resaltado.
            const escaneado = productos.find((p) => p.codigo && p.codigo.toLowerCase() === texto.trim().toLowerCase());
            const elegido = escaneado
                || (indiceActivo >= 0 ? resultados[indiceActivo] : (resultados.length === 1 ? resultados[0] : null));

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
        } else if (evento.key === 'Escape') {
            setTexto('');
        }
    }

    return (
        <div className="relative">
            <label htmlFor="buscador_producto_react" className="block text-sm font-medium mb-1">Producto</label>
            <input
                ref={inputRef}
                id="buscador_producto_react"
                type="text"
                autoComplete="off"
                autoFocus
                disabled={deshabilitado}
                placeholder="Escribí para buscar (nombre o código)..."
                value={texto}
                onChange={alTipear}
                onKeyDown={alTecla}
                onBlur={() => setTimeout(() => setTexto((t) => t), 100)}
                className="w-full rounded border border-slate-300 text-sm focus:border-slate-500 focus:ring-slate-500 disabled:bg-slate-100"
            />

            {resultados.length > 0 && (
                <ul className="absolute z-10 mt-1 w-full max-h-56 overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                    {resultados.map((producto, indice) => (
                        <li
                            key={producto.id}
                            onMouseDown={(e) => { e.preventDefault(); seleccionar(producto); }}
                            className={`px-3 py-2 text-sm cursor-pointer ${indice === indiceActivo ? 'bg-slate-100' : ''}`}
                        >
                            {producto.nombre}{producto.codigo ? ` (${producto.codigo})` : ''} — ${producto.precio.toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
