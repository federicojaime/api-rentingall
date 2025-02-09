<?php
// Crear nuevo archivo: objects/Stats.php
namespace objects;

use objects\Base;

class Stats extends Base {
    private $conn = null;

    public function __construct($db) {
        parent::__construct($db);
        $this->conn = $db;
    }

    public function getStats() {
        try {
            // Obtener total de vehículos
            $query = "SELECT COUNT(*) as total_vehiculos FROM vehiculos";
            $stmt = $this->conn->query($query);
            $totalVehiculos = $stmt->fetch(\PDO::FETCH_OBJ)->total_vehiculos;

            // Obtener entregas activas (sin fecha de devolución)
            $query = "SELECT COUNT(*) as entregas_activas FROM entregas WHERE fecha_devolucion = 0000-00-00";
            $stmt = $this->conn->query($query);
            $entregasActivas = $stmt->fetch(\PDO::FETCH_OBJ)->entregas_activas;

            // Obtener total de clientes
            $query = "SELECT COUNT(*) as total_clientes FROM clientes";
            $stmt = $this->conn->query($query);
            $totalClientes = $stmt->fetch(\PDO::FETCH_OBJ)->total_clientes;

            // Obtener ingresos totales de facturas
            $query = "SELECT COALESCE(SUM(monto), 0) as ingresos_totales FROM facturas";
            $stmt = $this->conn->query($query);
            $ingresosTotales = $stmt->fetch(\PDO::FETCH_OBJ)->ingresos_totales;

            $stats = [
                'total_vehiculos' => $totalVehiculos,
                'entregas_activas' => $entregasActivas,
                'total_clientes' => $totalClientes,
                'ingresos_totales' => $ingresosTotales
            ];

            parent::getResult()->ok = true;
            parent::getResult()->data = $stats;

        } catch (\Exception $e) {
            parent::getResult()->ok = false;
            parent::getResult()->msg = $e->getMessage();
        }

        return $this;
    }

    public function getChartData() {
        try {
            $query = "SELECT 
                        DATE_FORMAT(fecha_entrega, '%Y-%m') as mes,
                        COUNT(*) as total_entregas,
                        COALESCE(SUM(f.monto), 0) as ingresos
                     FROM entregas e
                     LEFT JOIN facturas f ON MONTH(e.fecha_entrega) = MONTH(f.fecha)
                     GROUP BY DATE_FORMAT(fecha_entrega, '%Y-%m')
                     ORDER BY mes DESC
                     LIMIT 6";
            
            $stmt = $this->conn->query($query);
            $chartData = $stmt->fetchAll(\PDO::FETCH_OBJ);

            parent::getResult()->ok = true;
            parent::getResult()->data = $chartData;

        } catch (\Exception $e) {
            parent::getResult()->ok = false;
            parent::getResult()->msg = $e->getMessage();
        }

        return $this;
    }
}