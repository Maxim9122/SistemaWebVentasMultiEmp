<?php

namespace App\Support\Manual;

/**
 * Contenido estático del Centro de ayuda — un "manual" navegable por
 * secciones y temas. A propósito NO es un agente de IA: es contenido
 * escrito a mano (por eso mismo nunca inventa nada ni se desactualiza
 * solo), simple de mantener editando este array cuando cambie una
 * funcionalidad real del sistema.
 *
 * Estructura: secciones (slug => [titulo, descripcion, temas]),
 * temas (slug => [titulo, resumen]). `resumen` es HTML de confianza
 * (lo escribimos nosotros, nunca datos de usuario) — se renderiza sin
 * escapar en la vista.
 */
class ContenidoAyuda
{
    public static function secciones(): array
    {
        return [
            'ventas' => [
                'titulo' => 'Ventas',
                'descripcion' => 'Cómo cobrar, imprimir y compartir una venta.',
                'temas' => [
                    'venta-remito' => [
                        'titulo' => 'Venta con remito (sin factura)',
                        'resumen' => '
                            <p>Es la venta más simple, sin comprobante fiscal ante AFIP.</p>
                            <ol>
                                <li>Desde <strong>Carritos</strong> (vendedor) o <strong>Caja</strong> (cajero), agregá los productos.</li>
                                <li>Al cobrar, elegí <strong>Remito</strong> como tipo de comprobante.</li>
                                <li>Completá los montos por medio de pago (efectivo/tarjeta/transferencia) — tienen que sumar exactamente el total.</li>
                                <li>Confirmá el cobro. Se descuenta el stock en ese momento (nunca antes).</li>
                            </ol>
                            <p>El remito se puede ver, imprimir o enviar por WhatsApp/email desde <strong>Ventas → el detalle de esa venta</strong>.</p>
                        ',
                    ],
                    'venta-factura' => [
                        'titulo' => 'Venta con factura (A/B/C)',
                        'resumen' => '
                            <p>Igual que el remito, pero eligiendo <strong>Factura</strong> como tipo de comprobante y el tipo (A/B/C) según la condición fiscal del cliente.</p>
                            <ul>
                                <li>Factura A o C: necesita un cliente con CUIT cargado.</li>
                                <li>Si la empresa ya tiene la <strong>facturación electrónica</strong> configurada (ver sección Configuración), el comprobante se emite ante AFIP automáticamente después de cobrar y aparece el CAE + el QR oficial en el PDF.</li>
                                <li>Si todavía no está configurada, la venta se cobra igual (nunca se bloquea por eso) y la factura queda "pendiente" hasta que se configure — se puede reintentar la emisión después desde el detalle de la venta.</li>
                            </ul>
                        ',
                    ],
                    'venta-fiada' => [
                        'titulo' => 'Venta fiada (a crédito)',
                        'resumen' => '
                            <p>Solo si la empresa tiene habilitado "Permite fiado" en Configuración.</p>
                            <ol>
                                <li>Al cobrar, cargá un monto en el campo <strong>Fiado</strong> (puede ser el total o una parte, combinado con efectivo/tarjeta).</li>
                                <li>Es obligatorio elegir o cargar un cliente — sin cliente no se puede fiar.</li>
                            </ol>
                            <p>Después se hace seguimiento desde <strong>Créditos</strong>: ver cuánto debe cada cliente, poner una fecha de promesa de pago, y registrar pagos parciales.</p>
                        ',
                    ],
                    'ver-comprobante' => [
                        'titulo' => 'Ver, imprimir o compartir un comprobante',
                        'resumen' => '
                            <p>Desde <strong>Ventas → el detalle de la venta</strong> hay botones para:</p>
                            <ul>
                                <li><strong>Ver / imprimir</strong>: abre el PDF en otra pestaña, listo para imprimir directo (no hace falta ningún lector de PDF especial, el navegador lo muestra solo).</li>
                                <li><strong>Enviar por WhatsApp</strong>: arma un link con el ticket para mandar al cliente.</li>
                                <li><strong>Enviar por email</strong>: si el cliente tiene email cargado.</li>
                            </ul>
                        ',
                    ],
                    'exportar-pdf-ventas' => [
                        'titulo' => 'Descargar un listado de ventas en PDF',
                        'resumen' => '
                            <p>En <strong>Ventas</strong>, filtrá por rango de fechas (y los demás filtros que necesites) y usá el botón <strong>Descargar PDF</strong> — genera un reporte imprimible en A4 con todo lo filtrado.</p>
                        ',
                    ],
                ],
            ],

            'presupuestos' => [
                'titulo' => 'Presupuestos',
                'descripcion' => 'Cotizar sin cobrar, y convertir en venta cuando el cliente confirma.',
                'temas' => [
                    'crear-presupuesto' => [
                        'titulo' => 'Crear un presupuesto',
                        'resumen' => '
                            <p>Desde <strong>Presupuestos → Nuevo</strong>, agregás productos igual que en un carrito de venta. Tiene su propia numeración (independiente de las ventas) y <strong>no descuenta stock</strong> — el stock recién se toca si después se cobra.</p>
                        ',
                    ],
                    'cobrar-presupuesto' => [
                        'titulo' => 'Cobrar un presupuesto (convertirlo en venta)',
                        'resumen' => '
                            <p>Desde el detalle del presupuesto, botón <strong>Cobrar</strong> — es el mismo flujo de cobro que una venta normal (remito, factura o fiado). Una vez cobrado pasa a verse también en <strong>Ventas</strong>, y conserva su número de presupuesto original como referencia.</p>
                        ',
                    ],
                    'cancelar-presupuesto' => [
                        'titulo' => 'Cancelar un presupuesto',
                        'resumen' => '
                            <p>Se puede cancelar en cualquier momento antes de cobrarlo. <strong>Importante</strong>: el número de presupuesto no se reutiliza ni se libera al cancelar — es intencional, para no tener dos presupuestos distintos con el mismo número en ningún momento.</p>
                        ',
                    ],
                ],
            ],

            'creditos' => [
                'titulo' => 'Créditos (ventas fiadas)',
                'descripcion' => 'Seguimiento de lo que deben los clientes.',
                'temas' => [
                    'ver-deuda' => [
                        'titulo' => 'Ver cuánto debe cada cliente',
                        'resumen' => '
                            <p>En <strong>Créditos</strong> aparece cada venta fiada con el total adeudado menos lo ya pagado. Lo que falta pagar se muestra en <strong>naranja</strong>.</p>
                            <p>Sin filtrar por cliente, arriba de la tabla aparece el <strong>total general</strong>: cuánto fiaron entre todos los clientes, cuánto pagaron y cuánto falta — histórico, de toda la empresa. Filtrando por un cliente puntual, ese total general se reemplaza por el desglose de ese cliente (fiado/pagado/pendiente) más un botón para ver el detalle de todos sus pagos parciales.</p>
                        ',
                    ],
                    'promesa-de-pago' => [
                        'titulo' => 'Poner una fecha de promesa de pago',
                        'resumen' => '
                            <p>Es opcional. Se puede cargar una fecha individual por cliente, o seleccionar varios clientes y ponerle la misma fecha a todos de una. Cuando faltan <strong>5 días o menos</strong> para esa fecha (o ya venció), la fila se pinta de naranja como recordatorio visual — no bloquea nada, es solo informativo.</p>
                        ',
                    ],
                    'registrar-pago' => [
                        'titulo' => 'Registrar un pago parcial',
                        'resumen' => '
                            <p>Desde Créditos, se carga el monto que el cliente pagó (puede ser parcial, no hace falta saldar todo de una vez) — efectivo, tarjeta y/o transferencia, se puede repartir entre varios medios. Queda en el historial de ese cliente y el saldo pendiente se recalcula solo.</p>
                            <p>Al guardar el pago aparece un cartel para descargar el <strong>comprobante en PDF</strong> (mismo formato Ticket/A4 elegido en Configuración). Si en ese momento no lo descargás, después lo podés volver a bajar desde "Ver detalle de pagos" — cada pago de la lista tiene su propio link de comprobante.</p>
                        ',
                    ],
                ],
            ],

            'caja' => [
                'titulo' => 'Caja',
                'descripcion' => 'Apertura, cobros y cierre del turno de un cajero.',
                'temas' => [
                    'abrir-cerrar-caja' => [
                        'titulo' => 'Abrir y cerrar caja',
                        'resumen' => '
                            <p>Un cajero (o cajero_vendedor) tiene que <strong>abrir su caja</strong> desde "Mi caja" antes de poder cobrar cualquier venta o cargar un egreso — es obligatorio, el sistema no deja cobrar sin caja abierta. Al cerrar, se declara el monto final y queda guardado en el historial.</p>
                        ',
                    ],
                    'cobrar-desde-caja' => [
                        'titulo' => 'Cobrar una venta desde Caja',
                        'resumen' => '
                            <p>Los pedidos que llegan de los vendedores (carritos cerrados) aparecen en <strong>Caja</strong> para cobrar. Si el rol es <strong>cajero_vendedor</strong>, puede cobrar directo desde su propio carrito, sin pasar por esta pantalla.</p>
                        ',
                    ],
                    'historial-cajas' => [
                        'titulo' => 'Ver el historial de cajas cerradas',
                        'resumen' => '
                            <p>Solo el admin ve <strong>Historial de cajas</strong>: todas las cajas abiertas y cerradas por cualquier cajero, con sus montos y egresos.</p>
                        ',
                    ],
                ],
            ],

            'productos' => [
                'titulo' => 'Productos',
                'descripcion' => 'Alta, importación, grupos y promociones.',
                'temas' => [
                    'crear-producto' => [
                        'titulo' => 'Crear un producto',
                        'resumen' => '
                            <p>Desde <strong>Productos → Nuevo</strong>: nombre, código, precio, costo, stock, categoría, marca y unidad. Opcionalmente se pueden cargar precios escalonados por cantidad (ej. "de 10 en adelante, precio X").</p>
                        ',
                    ],
                    'importar-productos' => [
                        'titulo' => 'Importar productos desde Excel/CSV',
                        'resumen' => '
                            <p>Desde <strong>Productos → Importar</strong>, subís el archivo y mapeás qué columna corresponde a cada campo. Antes de confirmar se muestra una vista previa. Queda un historial de importaciones que se puede <strong>deshacer</strong> si algo salió mal.</p>
                        ',
                    ],
                    'reponer-stock' => [
                        'titulo' => 'Reponer stock (cuando llega mercadería)',
                        'resumen' => '
                            <p>Botón <strong>Reponer stock</strong> en Productos: se abre un modal donde vas buscando productos (escribís y aparecen las coincidencias, con flechas del teclado y Enter también se puede elegir) y les cargás la cantidad que llegó de cada uno. Se pueden agregar varios productos distintos antes de guardar, con proveedor y nota opcionales.</p>
                            <p>Cada reposición guardada queda en el historial (<strong>Reposiciones</strong>, en el menú de Productos), y desde ahí hay dos acciones distintas:</p>
                            <ul>
                                <li><strong>Editar reposición</strong>: reabre el mismo modal con las líneas ya cargadas, por si algo se escribió mal — se puede corregir una cantidad, sacar una línea o agregar una nueva, y al guardar solo se ajusta la diferencia en el stock (no se vuelve a sumar todo de nuevo).</li>
                                <li><strong>Deshacer reposición</strong>: revierte todo el lote de una — le resta a cada producto exactamente lo que esa reposición le había sumado. El historial sigue mostrándose igual, no se borra, solo queda marcado como revertido.</li>
                            </ul>
                        ',
                    ],
                    'grupos-y-promociones' => [
                        'titulo' => 'Grupos de productos y promociones',
                        'resumen' => '
                            <p><strong>Grupos</strong>: agrupar productos para ajustar precios de todos juntos de una vez (ej. subir 10% toda una categoría). <strong>Promociones</strong>: combos de varios productos vendidos como un ítem con precio especial.</p>
                        ',
                    ],
                    'exportar-pdf-productos' => [
                        'titulo' => 'Descargar un listado de productos en PDF',
                        'resumen' => '
                            <p>En <strong>Productos</strong>, filtrá por marca, categoría o proveedor (o seleccioná productos puntuales de la lista) y usá <strong>Descargar PDF</strong> para un listado imprimible.</p>
                        ',
                    ],
                    'ajustar-precio-busqueda' => [
                        'titulo' => 'Ajustar precio por % a un grupo de productos',
                        'resumen' => '
                            <p>En <strong>Productos</strong>, arriba de la tabla hay un campo para cargar un porcentaje (positivo para aumentar, negativo — con el signo "-" — para descontar). Hay dos formas de aplicarlo:</p>
                            <ul>
                                <li><strong>A toda la búsqueda</strong>: filtrá primero por nombre, marca, categoría y/o proveedor, y usá el botón "Aplicar a TODOS estos N producto(s)" — pega únicamente en lo que ese filtro está mostrando en ese momento, nunca en el resto del catálogo.</li>
                                <li><strong>A mano</strong>: tildá los productos puntuales en la tabla (o usá el check del encabezado para tildar todos los de la página), y usá el botón "Aplicar % (de arriba) a estos seleccionados" que aparece en el panel de selección.</li>
                            </ul>
                            <p>En los dos casos se pide confirmación antes de aplicar, mostrando el % y la cantidad exacta de productos afectados. Las promociones quedan siempre afuera (su precio es un valor final armado a mano, no un precio de catálogo).</p>
                            <p>Queda un <strong>Historial de ajustes de precio</strong> (link junto a "Importaciones"), con la fecha, el % aplicado, el detalle de qué se filtró/seleccionó, y un botón <strong>Deshacer</strong> por si se aplicó algo mal — devuelve cada producto a su precio anterior (salvo que ya se haya vuelto a editar después, en cuyo caso ese cambio más reciente no se pisa).</p>
                        ',
                    ],
                ],
            ],

            'clientes' => [
                'titulo' => 'Clientes',
                'descripcion' => 'Alta, importación y datos de contacto.',
                'temas' => [
                    'crear-cliente' => [
                        'titulo' => 'Crear un cliente',
                        'resumen' => '
                            <p>Nombre, CUIT (opcional), teléfono y email. El CUIT no es obligatorio — sirve para poder facturar tipo A/C y para el link directo de WhatsApp.</p>
                        ',
                    ],
                    'importar-clientes' => [
                        'titulo' => 'Importar clientes',
                        'resumen' => '
                            <p>Igual que productos: se sube un archivo, se mapean columnas y hay vista previa. Si algunos clientes no tienen CUIT (es normal), se importan igual — al final se avisa cuántos campos quedaron sin completar, sin bloquear la importación.</p>
                        ',
                    ],
                ],
            ],

            'staff' => [
                'titulo' => 'Staff',
                'descripcion' => 'Usuarios del sistema y sus roles.',
                'temas' => [
                    'crear-usuario' => [
                        'titulo' => 'Crear un usuario y elegir su rol',
                        'resumen' => '
                            <p>Desde <strong>Staff → Nuevo</strong>. Los roles disponibles:</p>
                            <ul>
                                <li><strong>Admin</strong>: acceso total a la empresa.</li>
                                <li><strong>Vendedor</strong>: arma carritos, no cobra.</li>
                                <li><strong>Cajero</strong>: cobra los carritos que le llegan, no vende directo.</li>
                                <li><strong>Cajero_vendedor</strong>: hace las dos cosas, vende y cobra en el mismo carrito.</li>
                            </ul>
                        ',
                    ],
                    'seguridad-acceso' => [
                        'titulo' => 'Restringir por IP u horario laboral',
                        'resumen' => '
                            <p>En <strong>Staff → Seguridad</strong> se puede limitar el acceso del staff (no del admin, que nunca se restringe) a una IP fija y/o a un horario laboral con turnos por día. Fuera de esas condiciones, el login se rechaza con un mensaje claro.</p>
                        ',
                    ],
                ],
            ],

            'configuracion' => [
                'titulo' => 'Configuración',
                'descripcion' => 'Datos de la empresa, logo, ajustes de pago y facturación.',
                'temas' => [
                    'datos-empresa' => [
                        'titulo' => 'Datos de la empresa y logo',
                        'resumen' => '
                            <p>Razón social, CUIT, contacto — y el <strong>logo</strong> que se ve en el panel, al lado del nombre de la empresa. Se sube en formato JPG/PNG/WEBP.</p>
                            <p>El <strong>CUIT es opcional</strong>: solo hace falta cargarlo si la empresa va a facturar — recién se vuelve obligatorio al elegir una condición fiscal (Responsable Inscripto o Monotributista), más abajo en esta misma sección. Si la empresa nunca factura (solo remitos), no hace falta cargarlo.</p>
                        ',
                    ],
                    'ajustes-pago' => [
                        'titulo' => 'Recargos/descuentos por medio de pago',
                        'resumen' => '
                            <p>Se puede configurar un porcentaje de ajuste (recargo o descuento) para efectivo, tarjeta y transferencia — se aplica automáticamente al calcular el total según cómo pague el cliente.</p>
                        ',
                    ],
                    'facturacion-electronica' => [
                        'titulo' => 'Configurar la facturación electrónica (AFIP/ARCA)',
                        'resumen' => '
                            <p>Solo si la empresa tiene condición fiscal cargada (Responsable Inscripto o Monotributista). Se elige el ambiente (homologación o producción) y se inicia el trámite — te redirige a completar el resto afuera del sistema. Mientras no esté terminado, las ventas con factura se siguen cobrando normal, solo que quedan "pendientes" de emitir.</p>
                        ',
                    ],
                    'permite-fiado' => [
                        'titulo' => 'Habilitar venta fiada',
                        'resumen' => '
                            <p>Interruptor simple: si está apagado, nadie puede cargar un monto fiado al cobrar, sin importar el rol.</p>
                        ',
                    ],
                    'formato-comprobante' => [
                        'titulo' => 'Formato de impresión: Ticket o A4',
                        'resumen' => '
                            <p>Elegís cómo se imprimen/descargan todos los comprobantes: ventas (remito o factura), presupuestos y comprobantes de ventas fiadas. Por defecto viene en <strong>Ticket</strong>.</p>
                            <ul>
                                <li><strong>Ticket</strong>: angosto, pensado para impresora térmica de 80mm (la típica de mostrador).</li>
                                <li><strong>A4</strong>: hoja entera, para imprimir en una impresora común de oficina.</li>
                            </ul>
                            <p>Cambia el diseño de todos los documentos de una — no hay que elegir formato por cada venta.</p>
                        ',
                    ],
                ],
            ],

            'egresos' => [
                'titulo' => 'Egresos',
                'descripcion' => 'Salidas de dinero de una caja abierta.',
                'temas' => [
                    'registrar-egreso' => [
                        'titulo' => 'Registrar un egreso',
                        'resumen' => '
                            <p>Necesita tener la caja abierta. Se elige un <strong>motivo</strong> (configurable desde Egresos → Motivos), y opcionalmente puede requerir seleccionar un producto (ej. "rotura de mercadería") o un proveedor.</p>
                        ',
                    ],
                ],
            ],
        ];
    }

    public static function seccion(string $slug): ?array
    {
        return self::secciones()[$slug] ?? null;
    }

    public static function tema(string $seccionSlug, string $temaSlug): ?array
    {
        return self::seccion($seccionSlug)['temas'][$temaSlug] ?? null;
    }
}
