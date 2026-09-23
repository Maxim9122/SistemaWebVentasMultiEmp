<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CajaController;
use App\Http\Controllers\CajaSesionController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ClienteImportController;
use App\Http\Controllers\ConfiguracionEmpresaController;
use App\Http\Controllers\CreditoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EgresoController;
use App\Http\Controllers\FacturacionElectronicaController;
use App\Http\Controllers\GrupoProductoController;
use App\Http\Controllers\HistorialCajasController;
use App\Http\Controllers\HistorialSesionesController;
use App\Http\Controllers\ImportacionClienteController;
use App\Http\Controllers\ImportacionProductoController;
use App\Http\Controllers\MotivoEgresoController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProductoImportController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\RegistroEmpresaController;
use App\Http\Controllers\ReporteProductoController;
use App\Http\Controllers\SeguridadAccesoController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\Superadmin\EmpresaController as SuperadminEmpresaController;
use App\Http\Controllers\Superadmin\PerfilController as SuperadminPerfilController;
use App\Http\Controllers\TicketPublicoController;
use App\Http\Controllers\VentaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/registro-empresa', [RegistroEmpresaController::class, 'create'])->name('empresas.registro');
    Route::post('/registro-empresa', [RegistroEmpresaController::class, 'store'])->name('empresas.registro.store');
});

// Sin login: el link que se manda por WhatsApp al teléfono del cliente nunca
// tiene sesión en el sistema. La seguridad depende 100% de la firma de la
// URL (middleware `signed`), no de `auth`/`role` — ver TicketPublicoController.
Route::get('/comprobantes/compartido/{pedido}/{tipo}', TicketPublicoController::class)
    ->name('ticket.publico')
    ->middleware('signed');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware(['cuenta.activa', 'sesion.actividad'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::prefix('staff')->name('staff.')->middleware('role:admin')->group(function () {
            Route::get('/', [StaffController::class, 'index'])->name('index');
            Route::get('/nuevo', [StaffController::class, 'create'])->name('create');
            Route::post('/', [StaffController::class, 'store'])->name('store');
            Route::get('/{staff}/editar', [StaffController::class, 'edit'])->name('edit');
            Route::put('/{staff}', [StaffController::class, 'update'])->name('update');
            Route::post('/{staff}/activar', [StaffController::class, 'activar'])->name('activar');
            Route::post('/{staff}/desactivar', [StaffController::class, 'desactivar'])->name('desactivar');
            Route::get('/sesiones', [HistorialSesionesController::class, 'index'])->name('sesiones');

            Route::prefix('seguridad')->name('seguridad.')->group(function () {
                Route::get('/', [SeguridadAccesoController::class, 'edit'])->name('edit');
                Route::put('/ip', [SeguridadAccesoController::class, 'actualizarIp'])->name('ip.update');
                Route::put('/horario', [SeguridadAccesoController::class, 'actualizarHorarioHabilitado'])->name('horario.update');
                Route::post('/turnos', [SeguridadAccesoController::class, 'guardarTurno'])->name('turnos.store');
                Route::delete('/turnos/{turno}', [SeguridadAccesoController::class, 'eliminarTurno'])->name('turnos.destroy');
            });
        });

        Route::prefix('productos')->name('productos.')->middleware('role:admin')->group(function () {
            Route::get('/', [ProductoController::class, 'index'])->name('index');
            Route::get('/exportar', [ProductoController::class, 'exportar'])->name('exportar');
            Route::match(['GET', 'POST'], '/exportar-pdf', [ProductoController::class, 'exportarPdf'])->name('exportarPdf');
            Route::get('/nuevo', [ProductoController::class, 'create'])->name('create');
            Route::post('/', [ProductoController::class, 'store'])->name('store');
            Route::get('/{producto}/editar', [ProductoController::class, 'edit'])->name('edit');
            Route::put('/{producto}', [ProductoController::class, 'update'])->name('update');
            Route::post('/{producto}/activar', [ProductoController::class, 'activar'])->name('activar');
            Route::post('/{producto}/desactivar', [ProductoController::class, 'desactivar'])->name('desactivar');

            Route::get('/importar', [ProductoImportController::class, 'subir'])->name('importar.subir');
            Route::post('/importar', [ProductoImportController::class, 'previsualizar'])->name('importar.previsualizar');
            Route::post('/importar/confirmar', [ProductoImportController::class, 'confirmar'])->name('importar.confirmar');

            Route::prefix('grupos')->name('grupos.')->group(function () {
                Route::get('/', [GrupoProductoController::class, 'index'])->name('index');
                Route::post('/', [GrupoProductoController::class, 'store'])->name('store');
                Route::get('/{grupo}', [GrupoProductoController::class, 'show'])->name('show');
                Route::get('/{grupo}/exportar', [GrupoProductoController::class, 'exportar'])->name('exportar');
                Route::post('/{grupo}/agregar-productos', [GrupoProductoController::class, 'agregarProductos'])->name('agregarProductos');
                Route::delete('/{grupo}', [GrupoProductoController::class, 'destroy'])->name('destroy');
                Route::post('/{grupo}/ajustar-precio', [GrupoProductoController::class, 'ajustarPrecio'])->name('ajustarPrecio');
            });

            Route::prefix('proveedores')->name('proveedores.')->group(function () {
                Route::get('/', [ProveedorController::class, 'index'])->name('index');
                Route::get('/nuevo', [ProveedorController::class, 'create'])->name('create');
                Route::post('/', [ProveedorController::class, 'store'])->name('store');
                Route::get('/{proveedor}/editar', [ProveedorController::class, 'edit'])->name('edit');
                Route::put('/{proveedor}', [ProveedorController::class, 'update'])->name('update');
                Route::post('/{proveedor}/activar', [ProveedorController::class, 'activar'])->name('activar');
                Route::post('/{proveedor}/desactivar', [ProveedorController::class, 'desactivar'])->name('desactivar');
            });

            Route::prefix('importaciones')->name('importaciones.')->group(function () {
                Route::get('/', [ImportacionProductoController::class, 'index'])->name('index');
                Route::post('/{importacion}/deshacer', [ImportacionProductoController::class, 'deshacer'])->name('deshacer');
            });

            Route::prefix('promociones')->name('promociones.')->group(function () {
                Route::post('/previsualizar', [PromocionController::class, 'previsualizar'])->name('previsualizar');
                Route::post('/', [PromocionController::class, 'store'])->name('store');
                Route::get('/{promocion}/editar', [PromocionController::class, 'edit'])->name('edit');
                Route::put('/{promocion}', [PromocionController::class, 'update'])->name('update');
            });
        });

        Route::prefix('clientes')->name('clientes.')->middleware('role:admin')->group(function () {
            Route::get('/', [ClienteController::class, 'index'])->name('index');
            Route::get('/exportar', [ClienteController::class, 'exportar'])->name('exportar');
            Route::get('/nuevo', [ClienteController::class, 'create'])->name('create');
            Route::post('/', [ClienteController::class, 'store'])->name('store');
            Route::get('/{cliente}/editar', [ClienteController::class, 'edit'])->name('edit');
            Route::put('/{cliente}', [ClienteController::class, 'update'])->name('update');
            Route::post('/{cliente}/activar', [ClienteController::class, 'activar'])->name('activar');
            Route::post('/{cliente}/desactivar', [ClienteController::class, 'desactivar'])->name('desactivar');

            Route::get('/importar', [ClienteImportController::class, 'subir'])->name('importar.subir');
            Route::post('/importar', [ClienteImportController::class, 'previsualizar'])->name('importar.previsualizar');
            Route::post('/importar/confirmar', [ClienteImportController::class, 'confirmar'])->name('importar.confirmar');

            Route::prefix('importaciones')->name('importaciones.')->group(function () {
                Route::get('/', [ImportacionClienteController::class, 'index'])->name('index');
                Route::post('/{importacion}/deshacer', [ImportacionClienteController::class, 'deshacer'])->name('deshacer');
            });
        });

        Route::middleware('role:admin')->group(function () {
            Route::get('/configuracion', [ConfiguracionEmpresaController::class, 'edit'])->name('configuracion.edit');
            Route::put('/configuracion', [ConfiguracionEmpresaController::class, 'update'])->name('configuracion.update');
            Route::put('/configuracion/datos-empresa', [ConfiguracionEmpresaController::class, 'actualizarDatos'])->name('configuracion.datos.update');
            Route::put('/configuracion/logo', [ConfiguracionEmpresaController::class, 'actualizarLogo'])->name('configuracion.logo.update');
            Route::delete('/configuracion/logo', [ConfiguracionEmpresaController::class, 'eliminarLogo'])->name('configuracion.logo.destroy');

            Route::post('/configuracion/facturacion-electronica', [FacturacionElectronicaController::class, 'iniciar'])
                ->name('configuracion.facturacion.iniciar');

            Route::get('/egresos', [EgresoController::class, 'index'])->name('egresos.index');

            Route::prefix('cajas')->name('cajas.')->group(function () {
                Route::get('/', [HistorialCajasController::class, 'index'])->name('index');
                Route::get('/{caja}', [HistorialCajasController::class, 'show'])->name('show');
            });

            Route::prefix('egresos/motivos')->name('egresos.motivos.')->group(function () {
                Route::get('/', [MotivoEgresoController::class, 'index'])->name('index');
                Route::post('/', [MotivoEgresoController::class, 'store'])->name('store');
                Route::post('/{motivo}/activar', [MotivoEgresoController::class, 'activar'])->name('activar');
                Route::post('/{motivo}/desactivar', [MotivoEgresoController::class, 'desactivar'])->name('desactivar');
                Route::post('/{motivo}/alternar-requiere-producto', [MotivoEgresoController::class, 'alternarRequiereProducto'])->name('alternarRequiereProducto');
                Route::post('/{motivo}/alternar-solo-proveedores', [MotivoEgresoController::class, 'alternarSoloProveedores'])->name('alternarSoloProveedores');
            });
        });

        Route::prefix('carritos')->name('carritos.')->middleware('role:vendedor,cajero_vendedor')->group(function () {
            Route::get('/', [CarritoController::class, 'index'])->name('index');
            Route::post('/', [CarritoController::class, 'store'])->name('store');
            Route::get('/{pedido}', [CarritoController::class, 'show'])->name('show');
            Route::put('/{pedido}/nombre', [CarritoController::class, 'renombrar'])->name('renombrar');
            Route::post('/{pedido}/items', [CarritoController::class, 'agregarItem'])->name('items.store');
            Route::put('/{pedido}/items/{item}', [CarritoController::class, 'actualizarItem'])->name('items.update');
            Route::delete('/{pedido}/items/{item}', [CarritoController::class, 'quitarItem'])->name('items.destroy');
            Route::post('/{pedido}/cerrar', [CarritoController::class, 'cerrar'])->name('cerrar');
            Route::post('/{pedido}/cancelar', [CarritoController::class, 'cancelar'])->name('cancelar');
        });

        Route::prefix('caja')->name('caja.')->middleware('role:cajero,cajero_vendedor')->group(function () {
            Route::get('/', [CajaController::class, 'index'])->name('index');
            Route::get('/{pedido}', [CajaController::class, 'show'])->name('show');
            Route::get('/{pedido}/editar', [CajaController::class, 'editar'])->name('editar');
            Route::post('/{pedido}/editar', [CajaController::class, 'actualizarItems'])->name('actualizarItems');
            Route::post('/{pedido}/cobrar', [CajaController::class, 'cobrar'])->name('cobrar');
        });

        Route::prefix('mi-caja')->name('caja-sesion.')->middleware('role:cajero,cajero_vendedor')->group(function () {
            Route::get('/', [CajaSesionController::class, 'show'])->name('show');
            Route::post('/abrir', [CajaSesionController::class, 'abrir'])->name('abrir');
            Route::post('/cerrar', [CajaSesionController::class, 'cerrar'])->name('cerrar');
            Route::get('/{caja}', [CajaSesionController::class, 'detalle'])->name('detalle');
        });

        Route::post('/egresos', [EgresoController::class, 'store'])->name('egresos.store')->middleware('role:cajero,cajero_vendedor');

        Route::prefix('creditos')->name('creditos.')->middleware('role:admin,cajero,cajero_vendedor')->group(function () {
            Route::get('/', [CreditoController::class, 'index'])->name('index');
            Route::post('/pagos', [CreditoController::class, 'registrarPago'])->name('pagos.store');
            Route::put('/clientes/{cliente}/promesa-pago', [CreditoController::class, 'actualizarPromesaPago'])->name('clientes.promesaPago.update');
            Route::post('/promesa-pago-masiva', [CreditoController::class, 'actualizarPromesaPagoMasiva'])->name('promesaPagoMasiva');
        });

        Route::prefix('presupuestos')->name('presupuestos.')->middleware('role:admin,cajero,cajero_vendedor')->group(function () {
            Route::get('/', [PresupuestoController::class, 'index'])->name('index');
            Route::post('/', [PresupuestoController::class, 'store'])->name('store');
            Route::get('/{pedido}', [PresupuestoController::class, 'show'])->name('show');
            Route::put('/{pedido}/cliente', [PresupuestoController::class, 'actualizarCliente'])->name('cliente.update');
            Route::post('/{pedido}/items', [PresupuestoController::class, 'agregarItem'])->name('items.store');
            Route::put('/{pedido}/items/{item}', [PresupuestoController::class, 'actualizarItem'])->name('items.update');
            Route::delete('/{pedido}/items/{item}', [PresupuestoController::class, 'quitarItem'])->name('items.destroy');
            Route::post('/{pedido}/cobrar', [PresupuestoController::class, 'cobrar'])->name('cobrar');
            Route::post('/{pedido}/cancelar', [PresupuestoController::class, 'cancelar'])->name('cancelar');
            Route::get('/{pedido}/ticket.pdf', [PresupuestoController::class, 'ticketPdf'])->name('ticketPdf');
            Route::post('/{pedido}/enviar-email', [PresupuestoController::class, 'enviarEmail'])->name('enviarEmail');
        });

        Route::prefix('pedidos')->name('pedidos.')->middleware('role:vendedor,cajero,cajero_vendedor')->group(function () {
            Route::get('/', [PedidoController::class, 'index'])->name('index');
            Route::get('/{pedido}', [PedidoController::class, 'show'])->name('show');
            Route::get('/{pedido}/editar', [PedidoController::class, 'editar'])->name('editar');
            Route::post('/{pedido}/editar', [PedidoController::class, 'actualizarItems'])->name('actualizarItems');
            Route::post('/{pedido}/pasar-a-caja', [PedidoController::class, 'pasarACaja'])->name('pasarACaja');
            Route::post('/{pedido}/cancelar', [PedidoController::class, 'cancelar'])->name('cancelar');
        });

        Route::prefix('ventas')->name('ventas.')->middleware('role:admin,cajero,cajero_vendedor')->group(function () {
            Route::get('/', [VentaController::class, 'index'])->name('index');
            Route::get('/exportar-pdf', [VentaController::class, 'exportarPdf'])->name('exportarPdf');
            Route::get('/reporte-producto', [ReporteProductoController::class, 'index'])->name('reporteProducto');
            Route::get('/{pedido}', [VentaController::class, 'show'])->name('show');
            Route::get('/{pedido}/editar', [VentaController::class, 'edit'])->name('edit');
            Route::post('/{pedido}/editar', [VentaController::class, 'update'])->name('update');
            Route::post('/{pedido}/confirmar-reapertura', [VentaController::class, 'confirmarReapertura'])->name('confirmarReapertura');
            Route::post('/{pedido}/reintentar-facturacion', [VentaController::class, 'reintentarFacturacion'])->name('reintentarFacturacion');
            Route::post('/{pedido}/enviar-email', [VentaController::class, 'enviarEmail'])->name('enviarEmail');
            Route::get('/{pedido}/comprobante.pdf', [VentaController::class, 'comprobantePdf'])->name('comprobantePdf');
            Route::get('/{pedido}/remito.pdf', [VentaController::class, 'remitoPdf'])->name('remitoPdf');
            Route::post('/{pedido}/anular', [VentaController::class, 'anularFactura'])->name('anularFactura');
            Route::post('/{pedido}/reintentar-nota-credito', [VentaController::class, 'reintentarNotaCredito'])->name('reintentarNotaCredito');
            Route::get('/{pedido}/nota-credito.pdf', [VentaController::class, 'notaCreditoPdf'])->name('notaCreditoPdf');
        });
    });

    Route::prefix('superadmin')->name('superadmin.')->middleware('role:superadmin')->group(function () {
        Route::get('/perfil', [SuperadminPerfilController::class, 'edit'])->name('perfil.edit');
        Route::put('/perfil', [SuperadminPerfilController::class, 'update'])->name('perfil.update');
        Route::put('/perfil/icono', [SuperadminPerfilController::class, 'actualizarIcono'])->name('perfil.icono.update');
        Route::delete('/perfil/icono', [SuperadminPerfilController::class, 'eliminarIcono'])->name('perfil.icono.destroy');

        Route::get('/empresas', [SuperadminEmpresaController::class, 'index'])->name('empresas.index');
        Route::get('/empresas/{empresa}', [SuperadminEmpresaController::class, 'show'])->name('empresas.show');
        Route::post('/empresas/{empresa}/aprobar', [SuperadminEmpresaController::class, 'aprobar'])->name('empresas.aprobar');
        Route::post('/empresas/{empresa}/rechazar', [SuperadminEmpresaController::class, 'rechazar'])->name('empresas.rechazar');
        Route::post('/empresas/{empresa}/suspender', [SuperadminEmpresaController::class, 'suspender'])->name('empresas.suspender');
        Route::post('/empresas/{empresa}/reactivar', [SuperadminEmpresaController::class, 'reactivar'])->name('empresas.reactivar');
    });
});
