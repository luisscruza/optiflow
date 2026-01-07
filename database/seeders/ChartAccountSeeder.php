<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChartAccountType;
use App\Models\ChartAccount;
use Illuminate\Database\Seeder;

final class ChartAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Plan de cuentas básico para una óptica en República Dominicana
     * basado en las normativas contables dominicanas.
     */
    public function run(): void
    {
        // =============================================
        // 1. ACTIVOS
        // =============================================
        $activos = $this->createAccount('1', 'Activos', ChartAccountType::Asset, null, true);

        // 1.1 Activos Corrientes
        $activosCorrientes = $this->createAccount('1.1', 'Activos Corrientes', ChartAccountType::Asset, $activos);

        // 1.1.1 Efectivo y Equivalentes
        $efectivo = $this->createAccount('1.1.1', 'Efectivo y Equivalentes de Efectivo', ChartAccountType::Asset, $activosCorrientes);
        $this->createAccount('1.1.1.01', 'Caja General', ChartAccountType::Asset, $efectivo);
        $this->createAccount('1.1.1.02', 'Caja Chica', ChartAccountType::Asset, $efectivo);
        $this->createAccount('1.1.1.03', 'Bancos Moneda Nacional', ChartAccountType::Asset, $efectivo);
        $this->createAccount('1.1.1.04', 'Bancos Moneda Extranjera', ChartAccountType::Asset, $efectivo);

        // 1.1.2 Cuentas por Cobrar
        $cuentasPorCobrar = $this->createAccount('1.1.2', 'Cuentas por Cobrar', ChartAccountType::Asset, $activosCorrientes);
        $this->createAccount('1.1.2.01', 'Clientes', ChartAccountType::Asset, $cuentasPorCobrar);
        $this->createAccount('1.1.2.02', 'Anticipos a Proveedores', ChartAccountType::Asset, $cuentasPorCobrar);
        $this->createAccount('1.1.2.03', 'Préstamos a Empleados', ChartAccountType::Asset, $cuentasPorCobrar);
        $this->createAccount('1.1.2.04', 'Otras Cuentas por Cobrar', ChartAccountType::Asset, $cuentasPorCobrar);
        $this->createAccount('1.1.2.05', 'Provisión Cuentas Incobrables', ChartAccountType::Asset, $cuentasPorCobrar);

        // 1.1.3 Inventarios
        $inventarios = $this->createAccount('1.1.3', 'Inventarios', ChartAccountType::Asset, $activosCorrientes);
        $this->createAccount('1.1.3.01', 'Inventario de Monturas', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.02', 'Inventario de Lentes Oftálmicos', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.03', 'Inventario de Lentes de Contacto', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.04', 'Inventario de Lentes de Sol', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.05', 'Inventario de Accesorios', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.06', 'Inventario de Soluciones y Líquidos', ChartAccountType::Asset, $inventarios);
        $this->createAccount('1.1.3.07', 'Inventario de Repuestos', ChartAccountType::Asset, $inventarios);

        // 1.1.4 Impuestos por Recuperar
        $impuestosRecuperar = $this->createAccount('1.1.4', 'Impuestos por Recuperar', ChartAccountType::Asset, $activosCorrientes);
        $this->createAccount('1.1.4.01', 'ITBIS Pagado', ChartAccountType::Asset, $impuestosRecuperar);
        $this->createAccount('1.1.4.02', 'Anticipos ISR', ChartAccountType::Asset, $impuestosRecuperar);
        $this->createAccount('1.1.4.03', 'Otros Impuestos Anticipados', ChartAccountType::Asset, $impuestosRecuperar);

        // 1.2 Activos No Corrientes
        $activosNoCorrientes = $this->createAccount('1.2', 'Activos No Corrientes', ChartAccountType::Asset, $activos);

        // 1.2.1 Propiedad, Planta y Equipo
        $propiedadPlantaEquipo = $this->createAccount('1.2.1', 'Propiedad, Planta y Equipo', ChartAccountType::Asset, $activosNoCorrientes);
        $this->createAccount('1.2.1.01', 'Terrenos', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.02', 'Edificios', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.03', 'Mobiliario y Equipo de Oficina', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.04', 'Equipos de Optometría', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.05', 'Equipos de Laboratorio Óptico', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.06', 'Equipos de Computación', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.07', 'Vehículos', ChartAccountType::Asset, $propiedadPlantaEquipo);
        $this->createAccount('1.2.1.08', 'Depreciación Acumulada', ChartAccountType::Asset, $propiedadPlantaEquipo);

        // =============================================
        // 2. PASIVOS
        // =============================================
        $pasivos = $this->createAccount('2', 'Pasivos', ChartAccountType::Liability, null, true);

        // 2.1 Pasivos Corrientes
        $pasivosCorrientes = $this->createAccount('2.1', 'Pasivos Corrientes', ChartAccountType::Liability, $pasivos);

        // 2.1.1 Cuentas por Pagar
        $cuentasPorPagar = $this->createAccount('2.1.1', 'Cuentas por Pagar', ChartAccountType::Liability, $pasivosCorrientes);
        $this->createAccount('2.1.1.01', 'Proveedores Nacionales', ChartAccountType::Liability, $cuentasPorPagar);
        $this->createAccount('2.1.1.02', 'Proveedores Extranjeros', ChartAccountType::Liability, $cuentasPorPagar);
        $this->createAccount('2.1.1.03', 'Anticipos de Clientes', ChartAccountType::Liability, $cuentasPorPagar);

        // 2.1.2 Obligaciones Laborales
        $obligacionesLaborales = $this->createAccount('2.1.2', 'Obligaciones Laborales', ChartAccountType::Liability, $pasivosCorrientes);
        $this->createAccount('2.1.2.01', 'Sueldos por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.02', 'Vacaciones por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.03', 'Bonificación por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.04', 'Regalia Pascual por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.05', 'AFP por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.06', 'ARS por Pagar', ChartAccountType::Liability, $obligacionesLaborales);
        $this->createAccount('2.1.2.07', 'Infotep por Pagar', ChartAccountType::Liability, $obligacionesLaborales);

        // 2.1.3 Impuestos por Pagar
        $impuestosPorPagar = $this->createAccount('2.1.3', 'Impuestos por Pagar', ChartAccountType::Liability, $pasivosCorrientes);
        $this->createAccount('2.1.3.01', 'ITBIS por Pagar', ChartAccountType::Liability, $impuestosPorPagar);
        $this->createAccount('2.1.3.02', 'ISR por Pagar', ChartAccountType::Liability, $impuestosPorPagar);
        $this->createAccount('2.1.3.03', 'Retenciones ISR por Pagar', ChartAccountType::Liability, $impuestosPorPagar);
        $this->createAccount('2.1.3.04', 'Retenciones ITBIS por Pagar', ChartAccountType::Liability, $impuestosPorPagar);

        // 2.1.4 Otras Cuentas por Pagar
        $otrasCuentasPorPagar = $this->createAccount('2.1.4', 'Otras Cuentas por Pagar', ChartAccountType::Liability, $pasivosCorrientes);
        $this->createAccount('2.1.4.01', 'Préstamos Bancarios Corto Plazo', ChartAccountType::Liability, $otrasCuentasPorPagar);
        $this->createAccount('2.1.4.02', 'Tarjetas de Crédito por Pagar', ChartAccountType::Liability, $otrasCuentasPorPagar);

        // 2.2 Pasivos No Corrientes
        $pasivosNoCorrientes = $this->createAccount('2.2', 'Pasivos No Corrientes', ChartAccountType::Liability, $pasivos);
        $this->createAccount('2.2.1', 'Préstamos Bancarios Largo Plazo', ChartAccountType::Liability, $pasivosNoCorrientes);
        $this->createAccount('2.2.2', 'Provisión Prestaciones Laborales', ChartAccountType::Liability, $pasivosNoCorrientes);

        // =============================================
        // 3. PATRIMONIO
        // =============================================
        $patrimonio = $this->createAccount('3', 'Patrimonio', ChartAccountType::Equity, null, true);
        $this->createAccount('3.1', 'Capital Social', ChartAccountType::Equity, $patrimonio);
        $this->createAccount('3.2', 'Reserva Legal', ChartAccountType::Equity, $patrimonio);
        $this->createAccount('3.3', 'Utilidades Retenidas', ChartAccountType::Equity, $patrimonio);
        $this->createAccount('3.4', 'Utilidad (Pérdida) del Ejercicio', ChartAccountType::Equity, $patrimonio);

        // =============================================
        // 4. INGRESOS
        // =============================================
        $ingresos = $this->createAccount('4', 'Ingresos', ChartAccountType::Income, null, true);

        // 4.1 Ingresos Operacionales
        $ingresosOperacionales = $this->createAccount('4.1', 'Ingresos Operacionales', ChartAccountType::Income, $ingresos);
        // 4.2 Otros Ingresos
        $otrosIngresos = $this->createAccount('4.2', 'Otros Ingresos', ChartAccountType::Income, $ingresos);
        $this->createAccount('4.2.01', 'Ingresos por Intereses', ChartAccountType::Income, $otrosIngresos);
        $this->createAccount('4.2.02', 'Ingresos por Diferencial Cambiario', ChartAccountType::Income, $otrosIngresos);
        $this->createAccount('4.2.03', 'Devolución de Impuestos', ChartAccountType::Income, $otrosIngresos);
        $this->createAccount('4.2.04', 'Ingresos Diversos', ChartAccountType::Income, $otrosIngresos);
        $this->createAccount('4.2.05', 'Comisiones Recibidas', ChartAccountType::Income, $otrosIngresos);

        // =============================================
        // 5. GASTOS / COSTOS
        // =============================================
        $gastos = $this->createAccount('5', 'Gastos y Costos', ChartAccountType::Expense, null, true);

        // 5.1 Costo de Ventas
        $costoVentas = $this->createAccount('5.1', 'Costo de Ventas', ChartAccountType::Expense, $gastos);
        $this->createAccount('5.1.01', 'Costo de Monturas Vendidas', ChartAccountType::Expense, $costoVentas);
        $this->createAccount('5.1.02', 'Costo de Lentes Oftálmicos Vendidos', ChartAccountType::Expense, $costoVentas);
        $this->createAccount('5.1.03', 'Costo de Lentes de Contacto Vendidos', ChartAccountType::Expense, $costoVentas);
        $this->createAccount('5.1.04', 'Costo de Lentes de Sol Vendidos', ChartAccountType::Expense, $costoVentas);
        $this->createAccount('5.1.05', 'Costo de Accesorios Vendidos', ChartAccountType::Expense, $costoVentas);
        $this->createAccount('5.1.06', 'Costo de Soluciones y Líquidos Vendidos', ChartAccountType::Expense, $costoVentas);

        // 5.2 Gastos Operacionales
        $gastosOperacionales = $this->createAccount('5.2', 'Gastos Operacionales', ChartAccountType::Expense, $gastos);

        // 5.2.1 Gastos de Personal
        $gastosPersonal = $this->createAccount('5.2.1', 'Gastos de Personal', ChartAccountType::Expense, $gastosOperacionales);
        $this->createAccount('5.2.1.01', 'Sueldos y Salarios', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.02', 'Comisiones sobre Ventas', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.03', 'Vacaciones', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.04', 'Regalía Pascual', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.05', 'Bonificaciones', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.06', 'Aportes AFP Patronal', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.07', 'Aportes ARS Patronal', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.08', 'Aportes ARL', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.09', 'Infotep', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.10', 'Uniformes', ChartAccountType::Expense, $gastosPersonal);
        $this->createAccount('5.2.1.11', 'Capacitación y Entrenamiento', ChartAccountType::Expense, $gastosPersonal);

        // 5.2.2 Gastos de Local
        $gastosLocal = $this->createAccount('5.2.2', 'Gastos de Local', ChartAccountType::Expense, $gastosOperacionales);
        $this->createAccount('5.2.2.01', 'Alquiler de Local', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.02', 'Energía Eléctrica', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.03', 'Agua', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.04', 'Teléfono e Internet', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.05', 'Mantenimiento de Local', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.06', 'Seguridad y Vigilancia', ChartAccountType::Expense, $gastosLocal);
        $this->createAccount('5.2.2.07', 'Seguros', ChartAccountType::Expense, $gastosLocal);

        // 5.2.3 Gastos Administrativos
        $gastosAdmin = $this->createAccount('5.2.3', 'Gastos Administrativos', ChartAccountType::Expense, $gastosOperacionales);
        $this->createAccount('5.2.3.01', 'Útiles de Oficina', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.02', 'Materiales de Limpieza', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.03', 'Honorarios Profesionales', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.04', 'Gastos de Representación', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.05', 'Viáticos y Transporte', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.06', 'Gastos Legales', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.07', 'Suscripciones y Membresías', ChartAccountType::Expense, $gastosAdmin);
        $this->createAccount('5.2.3.08', 'Software y Licencias', ChartAccountType::Expense, $gastosAdmin);

        // 5.2.4 Gastos de Ventas y Marketing
        $gastosVentas = $this->createAccount('5.2.4', 'Gastos de Ventas y Marketing', ChartAccountType::Expense, $gastosOperacionales);
        $this->createAccount('5.2.4.01', 'Publicidad y Promoción', ChartAccountType::Expense, $gastosVentas);
        $this->createAccount('5.2.4.02', 'Comisiones Bancarias', ChartAccountType::Expense, $gastosVentas);
        $this->createAccount('5.2.4.03', 'Empaques y Estuches', ChartAccountType::Expense, $gastosVentas);
        $this->createAccount('5.2.4.04', 'Garantías', ChartAccountType::Expense, $gastosVentas);

        // 5.2.5 Gastos de Equipos
        $gastosEquipos = $this->createAccount('5.2.5', 'Gastos de Equipos', ChartAccountType::Expense, $gastosOperacionales);
        $this->createAccount('5.2.5.01', 'Mantenimiento Equipos de Optometría', ChartAccountType::Expense, $gastosEquipos);
        $this->createAccount('5.2.5.02', 'Mantenimiento Equipos de Laboratorio', ChartAccountType::Expense, $gastosEquipos);
        $this->createAccount('5.2.5.03', 'Mantenimiento Equipos de Cómputo', ChartAccountType::Expense, $gastosEquipos);
        $this->createAccount('5.2.5.04', 'Depreciación de Equipos', ChartAccountType::Expense, $gastosEquipos);

        // 5.3 Gastos Financieros
        $gastosFinancieros = $this->createAccount('5.3', 'Gastos Financieros', ChartAccountType::Expense, $gastos);
        $this->createAccount('5.3.01', 'Intereses Bancarios', ChartAccountType::Expense, $gastosFinancieros);
        $this->createAccount('5.3.02', 'Comisiones Bancarias', ChartAccountType::Expense, $gastosFinancieros);
        $this->createAccount('5.3.03', 'Pérdida por Diferencial Cambiario', ChartAccountType::Expense, $gastosFinancieros);
        $this->createAccount('5.3.04', 'Otros Gastos Financieros', ChartAccountType::Expense, $gastosFinancieros);

        // 5.4 Otros Gastos
        $otrosGastos = $this->createAccount('5.4', 'Otros Gastos', ChartAccountType::Expense, $gastos);
        $this->createAccount('5.4.01', 'Pérdida en Venta de Activos', ChartAccountType::Expense, $otrosGastos);
        $this->createAccount('5.4.02', 'Gastos No Deducibles', ChartAccountType::Expense, $otrosGastos);
        $this->createAccount('5.4.03', 'Multas y Recargos', ChartAccountType::Expense, $otrosGastos);
        $this->createAccount('5.4.04', 'Donaciones', ChartAccountType::Expense, $otrosGastos);
        $this->createAccount('5.4.05', 'Gastos Diversos', ChartAccountType::Expense, $otrosGastos);
    }

    /**
     * Create a chart account.
     */
    private function createAccount(
        string $code,
        string $name,
        ChartAccountType $type,
        ?ChartAccount $parent = null,
        bool $isSystem = false
    ): ChartAccount {
        return ChartAccount::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'parent_id' => $parent?->id,
                'is_active' => true,
                'is_system' => $isSystem,
            ]
        );
    }
}
