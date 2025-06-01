<?php

namespace objects;

use objects\Base;

class Reportes extends Base
{
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db;
    }

    public function getReporteVehiculos($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = "v.estado = :estado";
            $params['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['marca'])) {
            $where[] = "v.marca = :marca";
            $params['marca'] = $filtros['marca'];
        }

        if (!empty($filtros['year_desde'])) {
            $where[] = "YEAR(v.fecha_adquisicion) >= :year_desde";
            $params['year_desde'] = $filtros['year_desde'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT v.*,
                 s.compania as seguro_compania,
                 s.fecha_vencimiento as seguro_vencimiento,
                 COUNT(e.id) as total_entregas,
                 COUNT(CASE WHEN e.fecha_devolucion IS NULL OR e.fecha_devolucion = '0000-00-00' THEN 1 END) as entregas_activas,
                 COALESCE(SUM(f.monto), 0) as ingresos_totales,
                 COALESCE(AVG(f.monto), 0) as ingreso_promedio
                 FROM vehiculos v
                 LEFT JOIN seguros s ON v.id = s.vehiculo_id AND s.estado = 'ACTIVO'
                 LEFT JOIN entregas e ON v.id = e.vehiculo_id
                 LEFT JOIN facturas f ON v.id = f.vehiculo_id
                 $whereClause
                 GROUP BY v.id
                 ORDER BY v.nro_interno";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getReporteEntregas($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "e.fecha_entrega >= :fecha_desde";
            $params['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "e.fecha_entrega <= :fecha_hasta";
            $params['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['cliente_id'])) {
            $where[] = "e.cliente_id = :cliente_id";
            $params['cliente_id'] = $filtros['cliente_id'];
        }

        if (!empty($filtros['vehiculo_id'])) {
            $where[] = "e.vehiculo_id = :vehiculo_id";
            $params['vehiculo_id'] = $filtros['vehiculo_id'];
        }

        if (isset($filtros['activas_solo']) && $filtros['activas_solo'] == true) {
            $where[] = "(e.fecha_devolucion IS NULL OR e.fecha_devolucion = '0000-00-00')";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT e.*,
                 v.patente, v.marca, v.modelo, v.nro_interno,
                 CASE 
                    WHEN c.tipo_cliente = 'persona' THEN c.nombre
                    ELSE c.razon_social
                 END as cliente_nombre,
                 c.dni_cuit,
                 CASE 
                    WHEN e.fecha_devolucion IS NULL OR e.fecha_devolucion = '0000-00-00' THEN 'ACTIVA'
                    ELSE 'FINALIZADA'
                 END as estado_entrega,
                 DATEDIFF(COALESCE(e.fecha_devolucion, CURDATE()), e.fecha_entrega) as dias_duracion,
                 (e.kilometraje_devolucion - e.kilometraje_entrega) as kilometros_recorridos,
                 COALESCE(SUM(f.monto), 0) as monto_facturado
                 FROM entregas e
                 LEFT JOIN vehiculos v ON e.vehiculo_id = v.id
                 LEFT JOIN clientes c ON e.cliente_id = c.id
                 LEFT JOIN facturas f ON v.id = f.vehiculo_id AND MONTH(f.fecha) = MONTH(e.fecha_entrega)
                 $whereClause
                 GROUP BY e.id
                 ORDER BY e.fecha_entrega DESC";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getReporteFacturacion($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "f.fecha >= :fecha_desde";
            $params['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "f.fecha <= :fecha_hasta";
            $params['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (isset($filtros['pagado'])) {
            $where[] = "f.pagado = :pagado";
            $params['pagado'] = $filtros['pagado'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT f.*,
                 v.patente, v.marca, v.modelo, v.nro_interno,
                 DATE_FORMAT(f.fecha, '%Y-%m') as periodo,
                 CASE WHEN f.pagado = 1 THEN 'PAGADO' ELSE 'PENDIENTE' END as estado_pago
                 FROM facturas f
                 LEFT JOIN vehiculos v ON f.vehiculo_id = v.id
                 $whereClause
                 ORDER BY f.fecha DESC";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getReporteClientes($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tipo_cliente'])) {
            $where[] = "c.tipo_cliente = :tipo_cliente";
            $params['tipo_cliente'] = $filtros['tipo_cliente'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT c.*,
                 COUNT(e.id) as total_entregas,
                 COUNT(CASE WHEN e.fecha_devolucion IS NULL OR e.fecha_devolucion = '0000-00-00' THEN 1 END) as entregas_activas,
                 COALESCE(SUM(f.monto), 0) as monto_total_facturado,
                 COALESCE(AVG(f.monto), 0) as promedio_facturacion,
                 MIN(e.fecha_entrega) as primera_entrega,
                 MAX(e.fecha_entrega) as ultima_entrega
                 FROM clientes c
                 LEFT JOIN entregas e ON c.id = e.cliente_id
                 LEFT JOIN facturas f ON e.vehiculo_id = f.vehiculo_id
                 $whereClause
                 GROUP BY c.id
                 ORDER BY monto_total_facturado DESC";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getReporteInventario()
    {
        $query = "SELECT 
                 'VEHÍCULOS' as categoria,
                 COUNT(*) as total,
                 COUNT(CASE WHEN estado = 'DISPONIBLE' THEN 1 END) as disponibles,
                 COUNT(CASE WHEN estado = 'ALQUILADA' THEN 1 END) as alquilados,
                 COUNT(CASE WHEN estado = 'MANTENIMIENTO' THEN 1 END) as mantenimiento,
                 COUNT(CASE WHEN estado = 'BAJA' THEN 1 END) as baja,
                 AVG(precio) as precio_promedio,
                 SUM(precio) as valor_total_flota
                 FROM vehiculos
                 
                 UNION ALL
                 
                 SELECT 
                 'SEGUROS' as categoria,
                 COUNT(*) as total,
                 COUNT(CASE WHEN fecha_vencimiento > CURDATE() THEN 1 END) as vigentes,
                 COUNT(CASE WHEN fecha_vencimiento <= CURDATE() THEN 1 END) as vencidos,
                 COUNT(CASE WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as proximos_vencer,
                 0 as baja,
                 AVG(monto_prima) as prima_promedio,
                 SUM(monto_prima) as total_primas_anuales
                 FROM seguros
                 WHERE estado = 'ACTIVO'";

        parent::getAll($query);
        return $this;
    }

    public function getReporteDashboard()
    {
        $query = "SELECT 
                 'resumen_general' as tipo,
                 (SELECT COUNT(*) FROM vehiculos) as total_vehiculos,
                 (SELECT COUNT(*) FROM vehiculos WHERE estado = 'DISPONIBLE') as vehiculos_disponibles,
                 (SELECT COUNT(*) FROM vehiculos WHERE estado = 'ALQUILADA') as vehiculos_alquilados,
                 (SELECT COUNT(*) FROM clientes) as total_clientes,
                 (SELECT COUNT(*) FROM entregas WHERE fecha_devolucion IS NULL OR fecha_devolucion = '0000-00-00') as entregas_activas,
                 (SELECT COALESCE(SUM(monto), 0) FROM facturas WHERE MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE())) as ingresos_mes_actual,
                 (SELECT COALESCE(SUM(monto), 0) FROM facturas WHERE pagado = 0) as monto_pendiente_cobro,
                 (SELECT COUNT(*) FROM seguros WHERE fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as seguros_proximos_vencer";

        parent::getOne($query);
        return $this;
    }

    public function getReporteComparativo($periodo1, $periodo2)
    {
        // Formato esperado: 'YYYY-MM'
        $query = "SELECT 
                 'comparativo' as tipo,
                 
                 -- Período 1
                 (SELECT COUNT(*) FROM entregas WHERE DATE_FORMAT(fecha_entrega, '%Y-%m') = :periodo1) as entregas_p1,
                 (SELECT COALESCE(SUM(monto), 0) FROM facturas WHERE DATE_FORMAT(fecha, '%Y-%m') = :periodo1) as ingresos_p1,
                 (SELECT COUNT(DISTINCT cliente_id) FROM entregas WHERE DATE_FORMAT(fecha_entrega, '%Y-%m') = :periodo1) as clientes_activos_p1,
                 
                 -- Período 2
                 (SELECT COUNT(*) FROM entregas WHERE DATE_FORMAT(fecha_entrega, '%Y-%m') = :periodo2) as entregas_p2,
                 (SELECT COALESCE(SUM(monto), 0) FROM facturas WHERE DATE_FORMAT(fecha, '%Y-%m') = :periodo2) as ingresos_p2,
                 (SELECT COUNT(DISTINCT cliente_id) FROM entregas WHERE DATE_FORMAT(fecha_entrega, '%Y-%m') = :periodo2) as clientes_activos_p2";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':periodo1', $periodo1);
            $stmt->bindValue(':periodo2', $periodo2);
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $result
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => null
            ];
        }

        return $this;
    }

    public function getReporteKilometraje($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['vehiculo_id'])) {
            $where[] = "e.vehiculo_id = :vehiculo_id";
            $params['vehiculo_id'] = $filtros['vehiculo_id'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "e.fecha_entrega >= :fecha_desde";
            $params['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "e.fecha_entrega <= :fecha_hasta";
            $params['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT 
                 v.id as vehiculo_id,
                 v.patente,
                 v.marca,
                 v.modelo,
                 v.nro_interno,
                 COUNT(e.id) as total_entregas,
                 SUM(CASE WHEN e.kilometraje_devolucion > 0 THEN (e.kilometraje_devolucion - e.kilometraje_entrega) ELSE 0 END) as total_kilometros,
                 AVG(CASE WHEN e.kilometraje_devolucion > 0 THEN (e.kilometraje_devolucion - e.kilometraje_entrega) ELSE 0 END) as promedio_kilometros_por_entrega,
                 MAX(CASE WHEN e.fecha_devolucion IS NOT NULL THEN e.kilometraje_devolucion ELSE e.kilometraje_entrega END) as ultimo_kilometraje,
                 MIN(e.fecha_entrega) as primera_entrega,
                 MAX(COALESCE(e.fecha_devolucion, e.fecha_entrega)) as ultima_actividad
                 FROM vehiculos v
                 LEFT JOIN entregas e ON v.id = e.vehiculo_id
                 $whereClause
                 GROUP BY v.id
                 ORDER BY total_kilometros DESC";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function exportarCSV($tipo_reporte, $filtros = [])
    {
        $datos = [];
        $headers = [];

        switch ($tipo_reporte) {
            case 'vehiculos':
                $resultado = $this->getReporteVehiculos($filtros);
                $headers = ['ID', 'Nro Interno', 'Patente', 'Marca', 'Modelo', 'Estado', 'Total Entregas', 'Ingresos Totales'];
                if ($resultado->getResult()->ok) {
                    foreach ($resultado->getResult()->data as $row) {
                        $datos[] = [
                            $row->id,
                            $row->nro_interno,
                            $row->patente,
                            $row->marca,
                            $row->modelo,
                            $row->estado,
                            $row->total_entregas,
                            $row->ingresos_totales
                        ];
                    }
                }
                break;

            case 'entregas':
                $resultado = $this->getReporteEntregas($filtros);
                $headers = ['ID', 'Fecha Entrega', 'Vehículo', 'Cliente', 'Estado', 'Días Duración', 'Km Recorridos'];
                if ($resultado->getResult()->ok) {
                    foreach ($resultado->getResult()->data as $row) {
                        $datos[] = [
                            $row->id,
                            $row->fecha_entrega,
                            $row->patente . ' - ' . $row->marca . ' ' . $row->modelo,
                            $row->cliente_nombre,
                            $row->estado_entrega,
                            $row->dias_duracion,
                            $row->kilometros_recorridos
                        ];
                    }
                }
                break;

            case 'facturacion':
                $resultado = $this->getReporteFacturacion($filtros);
                $headers = ['ID', 'Número', 'Fecha', 'Vehículo', 'Monto', 'Estado Pago'];
                if ($resultado->getResult()->ok) {
                    foreach ($resultado->getResult()->data as $row) {
                        $datos[] = [
                            $row->id,
                            $row->numero,
                            $row->fecha,
                            $row->patente . ' - ' . $row->marca . ' ' . $row->modelo,
                            $row->monto,
                            $row->estado_pago
                        ];
                    }
                }
                break;
        }

        $this->result = (object) [
            'ok' => true,
            'msg' => 'Datos preparados para exportación',
            'data' => [
                'headers' => $headers,
                'datos' => $datos,
                'total_registros' => count($datos)
            ]
        ];

        return $this;
    }
}
