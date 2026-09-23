import { createRoot } from 'react-dom/client';
import VentaApp from './components/VentaApp';

const contenedor = document.getElementById('venta-react-root');

if (contenedor) {
    createRoot(contenedor).render(
        <VentaApp
            nombreVendedor={contenedor.dataset.nombreVendedor}
            urlCarritos={contenedor.dataset.urlCarritos}
        />,
    );
}
