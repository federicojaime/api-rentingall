// objects/Facturas.php
<?php

namespace objects;

use objects\Base;

class Facturas extends Base
{
    private $table_name = "facturas";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db; // Asegurar que la conexión se almacena
    }

    public function getFacturas()
    {
        $query = "SELECT f.*, v.patente, v.marca, v.modelo
                 FROM $this->table_name f
                 LEFT JOIN vehiculos v ON f.vehiculo_id = v.id
                 ORDER BY f.fecha DESC";
        parent::getAll($query);
        return $this;
    }

    public function getFactura($id)
    {
        $query = "SELECT f.*, v.patente, v.marca, v.modelo
                 FROM $this->table_name f
                 LEFT JOIN vehiculos v ON f.vehiculo_id = v.id
                 WHERE f.id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function getFacturasPorVehiculo($vehiculo_id)
    {
        $query = "SELECT f.*, v.patente, v.marca, v.modelo
                 FROM $this->table_name f
                 LEFT JOIN vehiculos v ON f.vehiculo_id = v.id
                 WHERE f.vehiculo_id = :vehiculo_id
                 ORDER BY f.fecha DESC";
        parent::getAll($query, ["vehiculo_id" => $vehiculo_id]);
        return $this;
    }

    public function getFacturasPorMes($year, $month)
    {
        $query = "SELECT f.*, v.patente, v.marca, v.modelo
                 FROM $this->table_name f
                 LEFT JOIN vehiculos v ON f.vehiculo_id = v.id
                 WHERE YEAR(f.fecha) = :year AND MONTH(f.fecha) = :month
                 ORDER BY f.fecha DESC";
        parent::getAll($query, [
            "year" => $year,
            "month" => $month
        ]);
        return $this;
    }

    public function setFactura($values)
    {
        $query = "INSERT INTO $this->table_name SET 
                 vehiculo_id = :vehiculo_id,
                 fecha = :fecha,
                 numero = :numero,
                 monto = :monto,
                 pagado = :pagado,
                 notas = :notas";

        parent::add($query, [
            "vehiculo_id" => $values["vehiculo_id"],
            "fecha" => $values["fecha"],
            "numero" => $values["numero"],
            "monto" => $values["monto"],
            "pagado" => $values["pagado"] ?? false,
            "notas" => $values["notas"] ?? null
        ]);

        return $this;
    }

    public function updateFactura($id, $values)
    {
        $query = "UPDATE $this->table_name SET 
                 vehiculo_id = :vehiculo_id,
                 fecha = :fecha,
                 numero = :numero,
                 monto = :monto,
                 pagado = :pagado,
                 notas = :notas
                 WHERE id = :id";

        parent::update($query, [
            "id" => $id,
            "vehiculo_id" => $values["vehiculo_id"],
            "fecha" => $values["fecha"],
            "numero" => $values["numero"],
            "monto" => $values["monto"],
            "pagado" => $values["pagado"] ?? false,
            "notas" => $values["notas"] ?? null
        ]);

        return $this;
    }

    public function actualizarEstadoPago($id, $pagado)
    {
        $query = "UPDATE $this->table_name SET 
                 pagado = :pagado
                 WHERE id = :id";

        parent::update($query, [
            "id" => $id,
            "pagado" => $pagado
        ]);

        return $this;
    }

    public function deleteFactura($id)
    {
        $query = "DELETE FROM $this->table_name WHERE id = :id";
        parent::delete($query, ["id" => $id]);
        return $this;
    }

    public function getEstadisticas()
    {
        $query = "SELECT 
                 COUNT(*) as total_facturas,
                 SUM(monto) as monto_total,
                 SUM(CASE WHEN pagado = 1 THEN monto ELSE 0 END) as monto_pagado,
                 SUM(CASE WHEN pagado = 0 THEN monto ELSE 0 END) as monto_pendiente,
                 COUNT(CASE WHEN pagado = 1 THEN 1 END) as facturas_pagadas,
                 COUNT(CASE WHEN pagado = 0 THEN 1 END) as facturas_pendientes
                 FROM $this->table_name
                 WHERE YEAR(fecha) = YEAR(CURRENT_DATE())";
        parent::getOne($query);
        return $this;
    }

    public function getEstadisticasPorMes($year, $month)
    {
        $query = "SELECT 
                 COUNT(*) as total_facturas,
                 SUM(monto) as monto_total,
                 SUM(CASE WHEN pagado = 1 THEN monto ELSE 0 END) as monto_pagado,
                 SUM(CASE WHEN pagado = 0 THEN monto ELSE 0 END) as monto_pendiente
                 FROM $this->table_name
                 WHERE YEAR(fecha) = :year AND MONTH(fecha) = :month";
        parent::getOne($query, [
            "year" => $year,
            "month" => $month
        ]);
        return $this;
    }
}
?>