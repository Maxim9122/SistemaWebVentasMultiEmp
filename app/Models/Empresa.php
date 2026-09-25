<?php

namespace App\Models;

use App\Models\Concerns\TieneTelefonoWhatsapp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Empresa extends Model
{
    use HasFactory, TieneTelefonoWhatsapp;

    protected $fillable = [
        'razon_social',
        'cuit',
        'rubro',
        'email_contacto',
        'telefono',
        'direccion',
        'estado',
        'motivo_rechazo',
        'motivo_suspension',
        'permite_multiples_carritos',
        'permite_cambiar_precio_venta',
        'controla_stock',
        'permite_cajero_modificar_ventas',
        'permite_cajero_notas_credito',
        'condicion_fiscal',
        'factura_habilitada',
        'comprobante_predeterminado',
        'formato_comprobante',
        'mostrar_modal_comprobante',
        'ajuste_efectivo_porcentaje',
        'ajuste_tarjeta_porcentaje',
        'ajuste_transferencia_porcentaje',
        'ultimo_numero_venta',
        'ultimo_numero_presupuesto',
        'ip_permitida',
        'descuento_precio_empleado_porcentaje',
        'horario_laboral_habilitado',
        'permite_fiado',
        'logo_path',
    ];

    public const CONDICION_RESPONSABLE_INSCRIPTO = 'responsable_inscripto';

    public const CONDICION_MONOTRIBUTISTA = 'monotributista';

    public const COMPROBANTE_REMITO = 'remito';

    public const FORMATO_COMPROBANTE_TICKET = 'ticket';

    public const FORMATO_COMPROBANTE_A4 = 'a4';

    protected function casts(): array
    {
        return [
            'permite_multiples_carritos' => 'boolean',
            'permite_cambiar_precio_venta' => 'boolean',
            'controla_stock' => 'boolean',
            'permite_cajero_modificar_ventas' => 'boolean',
            'permite_cajero_notas_credito' => 'boolean',
            'factura_habilitada' => 'boolean',
            'mostrar_modal_comprobante' => 'boolean',
            'horario_laboral_habilitado' => 'boolean',
            'permite_fiado' => 'boolean',
            'ajuste_efectivo_porcentaje' => 'decimal:2',
            'ajuste_tarjeta_porcentaje' => 'decimal:2',
            'ajuste_transferencia_porcentaje' => 'decimal:2',
            'descuento_precio_empleado_porcentaje' => 'decimal:2',
        ];
    }

    /**
     * Precio de catálogo con el descuento de "precio empleado" aplicado, si
     * el admin configuró uno — si no, devuelve el precio tal cual.
     */
    public function precioEmpleadoPara(float $precioCatalogo): float
    {
        if ($this->descuento_precio_empleado_porcentaje === null) {
            return $precioCatalogo;
        }

        return round($precioCatalogo * (1 - (float) $this->descuento_precio_empleado_porcentaje / 100), 2);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class);
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function credencialFacturacion(): HasOne
    {
        return $this->hasOne(CredencialFacturacion::class);
    }

    public function turnosLaborales(): HasMany
    {
        return $this->hasMany(TurnoLaboral::class);
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class);
    }

    public function motivosEgreso(): HasMany
    {
        return $this->hasMany(MotivoEgreso::class);
    }

    public function pagosAbono(): HasMany
    {
        return $this->hasMany(PagoAbono::class)->orderBy('fecha_pago');
    }

    /**
     * Hasta cuándo está cubierto el abono mensual de esta empresa. Cada pago
     * suma 30 días — si se registran 2 pagos seguidos (pagó 2 meses de una),
     * el segundo extiende la vigencia otros 30 días más a partir de donde
     * quedó el primero, en vez de "pisarlo" — así 2 pagos cubren 60 días, no
     * 30 (pedido explícito del usuario: "que cuente 30 días por cada pago").
     * Sin ningún pago todavía, se da 30 días de gracia desde el alta.
     */
    public function vigenciaAbonoHasta(): Carbon
    {
        $pagos = $this->pagosAbono;

        if ($pagos->isEmpty()) {
            return $this->created_at->copy()->startOfDay()->addDays(30);
        }

        $vigencia = $this->created_at->copy()->startOfDay();

        foreach ($pagos as $pago) {
            $fechaPago = $pago->fecha_pago->copy()->startOfDay();
            $base = $fechaPago->gt($vigencia) ? $fechaPago : $vigencia;
            $vigencia = $base->copy()->addDays(30);
        }

        return $vigencia;
    }

    /**
     * Cuántos días pasaron desde que venció el abono (0 si todavía está
     * vigente o vence hoy mismo).
     */
    public function diasVencidoAbono(): int
    {
        $vigencia = $this->vigenciaAbonoHasta();
        $hoy = now()->startOfDay();

        return $hoy->gt($vigencia) ? (int) $vigencia->diffInDays($hoy) : 0;
    }

    public function abonoVencido(): bool
    {
        return $this->diasVencidoAbono() > 0;
    }

    /**
     * Para el numerito del menú lateral del superadmin — se llama en cada
     * página (ver layouts/app.blade.php), por eso se mantiene liviano: solo
     * trae empresas activas con sus pagos, nada más.
     */
    public static function cantidadConAbonoVencido(): int
    {
        return static::where('estado', 'activa')
            ->with('pagosAbono')
            ->get()
            ->filter(fn (Empresa $empresa) => $empresa->abonoVencido())
            ->count();
    }

    /**
     * Se llama de forma perezosa (no al crear la empresa) porque hasta ahora
     * nada obligaba a tener motivos armados — la primera vez que alguien
     * necesita elegir uno (abrir el modal de egreso, o entrar a
     * configurarlos), se cargan los 3 por defecto si todavía no hay ninguno.
     * Después el admin los puede renombrar, agregar más o desactivar.
     */
    public function asegurarMotivosEgresoPorDefecto(): void
    {
        if ($this->motivosEgreso()->exists()) {
            return;
        }

        foreach (MotivoEgreso::DEFAULTS as $datos) {
            $this->motivosEgreso()->create($datos);
        }
    }

    /**
     * A diferencia de otras funciones "apagadas por ausencia de datos" de
     * este proyecto, acá el admin pidió explícitamente un check aparte: así
     * puede precargar los turnos sin activarlos todavía, o apagar la función
     * un rato sin perder la configuración ya cargada. Los turnos siguen
     * existiendo en la tabla aunque este flag esté en false.
     */
    public function horarioLaboralHabilitado(): bool
    {
        return (bool) $this->horario_laboral_habilitado;
    }

    public function estaActiva(): bool
    {
        return $this->estado === 'activa';
    }

    /**
     * Otras empresas (cualquier estado) que comparten este mismo CUIT — el
     * alta por `/registro-empresa` ya no bloquea un CUIT repetido en seco,
     * así que puede haber más de una fila con el mismo CUIT en la tabla.
     * Se usa para avisarle al superadmin al revisar una solicitud, y para
     * evitar que termine habiendo dos empresas *activas* a la vez con el
     * mismo CUIT (ver `EmpresaController::aprobar()`/`reactivar()`).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public function empresasConMismoCuit()
    {
        // Sin CUIT cargado no hay nada que comparar — ->where('cuit', null)
        // traduciría a "IS NULL" y matchearía cualquier otra empresa que
        // tampoco tenga CUIT todavía, marcándolas como "duplicadas" sin serlo.
        if (! $this->cuit) {
            return $this->newCollection();
        }

        return static::where('cuit', $this->cuit)->where('id', '!=', $this->id)->get();
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }

    public function puedeFacturar(): bool
    {
        return in_array($this->condicion_fiscal, [self::CONDICION_RESPONSABLE_INSCRIPTO, self::CONDICION_MONOTRIBUTISTA], true);
    }

    /**
     * A diferencia de puedeFacturar() (elegibilidad fiscal: tiene condición
     * fiscal configurada), esto es un interruptor operativo que el admin
     * puede apagar temporalmente sin tocar la condición fiscal ni el
     * certificado AFIP ya configurado — por eso la sección de facturación
     * electrónica en Configuración sigue usando puedeFacturar(), y este
     * método gatea puntualmente la opción de elegir "Factura" al cobrar.
     */
    public function facturacionHabilitada(): bool
    {
        return $this->puedeFacturar() && $this->factura_habilitada;
    }

    /**
     * Qué comprobante viene preseleccionado al cobrar (`comprobante_predeterminado`
     * lo elige el admin en Configuración), pero siempre validado contra el
     * estado actual de la empresa — así un valor guardado que dejó de ser
     * válido (ej. se cambió la condición fiscal, o se pausó la facturación)
     * nunca rompe el formulario de cobro: cae a Remito en vez de romper.
     */
    public function comprobantePredeterminadoEfectivo(): string
    {
        if (! $this->facturacionHabilitada()) {
            return self::COMPROBANTE_REMITO;
        }

        if ($this->esResponsableInscripto() && in_array($this->comprobante_predeterminado, ['A', 'B'], true)) {
            return $this->comprobante_predeterminado;
        }

        if (! $this->esResponsableInscripto() && $this->comprobante_predeterminado === 'C') {
            return $this->comprobante_predeterminado;
        }

        return self::COMPROBANTE_REMITO;
    }

    public function usaFormatoA4(): bool
    {
        return $this->formato_comprobante === self::FORMATO_COMPROBANTE_A4;
    }

    public function esResponsableInscripto(): bool
    {
        return $this->condicion_fiscal === self::CONDICION_RESPONSABLE_INSCRIPTO;
    }

    public function ajustePorcentajePara(string $medioPago): float
    {
        return (float) match ($medioPago) {
            'efectivo' => $this->ajuste_efectivo_porcentaje,
            'tarjeta' => $this->ajuste_tarjeta_porcentaje,
            'transferencia' => $this->ajuste_transferencia_porcentaje,
            default => 0,
        };
    }

    public function condicionFiscalLegible(): ?string
    {
        return match ($this->condicion_fiscal) {
            self::CONDICION_RESPONSABLE_INSCRIPTO => 'Responsable Inscripto',
            self::CONDICION_MONOTRIBUTISTA => 'Monotributista',
            default => null,
        };
    }

    public static function formatearCuit(?string $cuit): ?string
    {
        if ($cuit === null || ! preg_match('/^\d{11}$/', $cuit)) {
            return $cuit;
        }

        return substr($cuit, 0, 2).'-'.substr($cuit, 2, 8).'-'.substr($cuit, 10, 1);
    }
}
