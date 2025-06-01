<?php

namespace objects;

use objects\Base;

class Seguros extends Base
{
    private $table_name = "seguros";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db;
    }

    public function getSeguros()
    {
        $query = "SELECT s.*, 
                 v.patente, v.marca, v.modelo, v.nro_interno
                 FROM $this->table_name s
                 LEFT JOIN vehiculos v ON s.vehiculo_id = v.id
                 ORDER BY s.fecha_vencimiento ASC";
        parent::getAll($query);
        return $this;
    }

    public function getSeguro($id)
    {
        $query = "SELECT s.*, 
                 v.patente, v.marca, v.modelo, v.nro_interno
                 FROM $this->table_name s
                 LEFT JOIN vehiculos v ON s.vehiculo_id = v.id
                 WHERE s.id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function getSegurosPorVehiculo($vehiculo_id)
    {
        $query = "SELECT s.*, 
                 v.patente, v.marca, v.modelo
                 FROM $this->table_name s
                 LEFT JOIN vehiculos v ON s.vehiculo_id = v.id
                 WHERE s.vehiculo_id = :vehiculo_id
                 ORDER BY s.fecha_vencimiento DESC";
        parent::getAll($query);

        // Modificar la consulta para usar parámetros
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':vehiculo_id', $vehiculo_id);
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

    public function getSegurosVencidos()
    {
        $query = "SELECT s.*, 
                 v.patente, v.marca, v.modelo, v.nro_interno
                 FROM $this->table_name s
                 LEFT JOIN vehiculos v ON s.vehiculo_id = v.id
                 WHERE s.fecha_vencimiento <= CURDATE()
                 ORDER BY s.fecha_vencimiento ASC";
        parent::getAll($query);
        return $this;
    }

    public function getSegurosProximosVencer($dias = 30)
    {
        $query = "SELECT s.*, 
                 v.patente, v.marca, v.modelo, v.nro_interno,
                 DATEDIFF(s.fecha_vencimiento, CURDATE()) as dias_restantes
                 FROM $this->table_name s
                 LEFT JOIN vehiculos v ON s.vehiculo_id = v.id
                 WHERE s.fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :dias DAY)
                 ORDER BY s.fecha_vencimiento ASC";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':dias', $dias, \PDO::PARAM_INT);
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

    public function setSeguro($values)
    {
        $query = "INSERT INTO $this->table_name SET 
                 vehiculo_id = :vehiculo_id,
                 compania = :compania,
                 nro_poliza = :nro_poliza,
                 fecha_inicio = :fecha_inicio,
                 fecha_vencimiento = :fecha_vencimiento,
                 tipo_cobertura = :tipo_cobertura,
                 monto_prima = :monto_prima,
                 notas = :notas";

        parent::add($query, [
            "vehiculo_id" => $values["vehiculo_id"],
            "compania" => $values["compania"],
            "nro_poliza" => $values["nroPoliza"],
            "fecha_inicio" => $values["fechaInicio"],
            "fecha_vencimiento" => $values["fechaVencimiento"],
            "tipo_cobertura" => $values["tipoCobertura"] ?? 'TERCEROS_COMPLETOS',
            "monto_prima" => $values["montoPrima"] ?? 0,
            "notas" => $values["notas"] ?? null
        ]);

        return $this;
    }

    public function updateSeguro($id, $values)
    {
        $query = "UPDATE $this->table_name SET 
                 vehiculo_id = :vehiculo_id,
                 compania = :compania,
                 nro_poliza = :nro_poliza,
                 fecha_inicio = :fecha_inicio,
                 fecha_vencimiento = :fecha_vencimiento,
                 tipo_cobertura = :tipo_cobertura,
                 monto_prima = :monto_prima,
                 notas = :notas
                 WHERE id = :id";

        parent::update($query, [
            "id" => $id,
            "vehiculo_id" => $values["vehiculo_id"],
            "compania" => $values["compania"],
            "nro_poliza" => $values["nroPoliza"],
            "fecha_inicio" => $values["fechaInicio"],
            "fecha_vencimiento" => $values["fechaVencimiento"],
            "tipo_cobertura" => $values["tipoCobertura"] ?? 'TERCEROS_COMPLETOS',
            "monto_prima" => $values["montoPrima"] ?? 0,
            "notas" => $values["notas"] ?? null
        ]);

        return $this;
    }

    public function deleteSeguro($id)
    {
        $query = "DELETE FROM $this->table_name WHERE id = :id";
        parent::delete($query, ["id" => $id]);
        return $this;
    }

    public function renovarSeguro($id, $values)
    {
        try {
            $this->conn->beginTransaction();

            // Marcar el seguro actual como renovado
            $query = "UPDATE $this->table_name SET 
                     estado = 'RENOVADO',
                     notas = CONCAT(COALESCE(notas, ''), '\nRenovado el: ', CURDATE())
                     WHERE id = :id";
            parent::update($query, ["id" => $id]);

            // Crear nuevo seguro
            $query = "INSERT INTO $this->table_name SET 
                     vehiculo_id = :vehiculo_id,
                     compania = :compania,
                     nro_poliza = :nro_poliza,
                     fecha_inicio = :fecha_inicio,
                     fecha_vencimiento = :fecha_vencimiento,
                     tipo_cobertura = :tipo_cobertura,
                     monto_prima = :monto_prima,
                     notas = :notas";

            parent::add($query, [
                "vehiculo_id" => $values["vehiculo_id"],
                "compania" => $values["compania"],
                "nro_poliza" => $values["nroPoliza"],
                "fecha_inicio" => $values["fechaInicio"],
                "fecha_vencimiento" => $values["fechaVencimiento"],
                "tipo_cobertura" => $values["tipoCobertura"] ?? 'TERCEROS_COMPLETOS',
                "monto_prima" => $values["montoPrima"] ?? 0,
                "notas" => $values["notas"] ?? null
            ]);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }

    public function getEstadisticasSeguros()
    {
        $query = "SELECT 
                 COUNT(*) as total_seguros,
                 COUNT(CASE WHEN fecha_vencimiento <= CURDATE() THEN 1 END) as vencidos,
                 COUNT(CASE WHEN fecha_vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as proximos_vencer,
                 COUNT(CASE WHEN fecha_vencimiento > DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as vigentes,
                 AVG(monto_prima) as promedio_prima,
                 SUM(monto_prima) as total_primas,
                 compania,
                 COUNT(*) as seguros_por_compania
                 FROM $this->table_name
                 WHERE estado = 'ACTIVO'
                 GROUP BY compania WITH ROLLUP";

        parent::getAll($query);
        return $this;
    }
}
