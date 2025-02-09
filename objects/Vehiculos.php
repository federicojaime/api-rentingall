<?php

namespace objects;

use objects\Base;

class Vehiculos extends Base
{
    private $table_name = "vehiculos";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db; // Asegurar que la conexión se almacena
    }


    public function getVehiculos()
    {
        $query = "SELECT v.*
                 FROM $this->table_name v 
                 LEFT JOIN seguros s ON v.id = s.vehiculo_id 
                 ORDER BY v.id";
        parent::getAll($query);
        return $this;
    }

    public function getVehiculo($id)
    {
        $query = "SELECT v.*
                 FROM $this->table_name v 
                 LEFT JOIN seguros s ON v.id = s.vehiculo_id 
                 WHERE v.id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function setVehiculo($values)
    {
        // Iniciamos una transacción porque insertaremos en dos tablas
        try {
            $this->conn->beginTransaction();

            // Insertar vehículo
            $query = "INSERT INTO $this->table_name SET 
                     nro_interno = :nro_interno,
                     designacion = :designacion,
                     marca = :marca,
                     modelo = :modelo,
                     fecha_adquisicion = :fecha_adquisicion,
                     nro_motor = :nro_motor,
                     nro_chasis = :nro_chasis,
                     patente = :patente,
                     titulo = :titulo,
                     estado = :estado,
                     responsable = :responsable,
                     ministerio = :ministerio,
                     precio = :precio";

            parent::add($query, [
                "nro_interno" => $values["nroInterno"],
                "designacion" => $values["designacion"],
                "marca" => $values["marca"],
                "modelo" => $values["modelo"],
                "fecha_adquisicion" => $values["adquisicion"],
                "nro_motor" => $values["motor"],
                "nro_chasis" => $values["chasis"],
                "patente" => $values["patente"],
                "titulo" => $values["titulo"],
                "estado" => $values["estado"],
                "responsable" => $values["responsable"],
                "ministerio" => $values["ministerio"],
                "precio" => $values["precio"]
            ]);

            $result = parent::getResult();

            if ($result->ok && isset($values["compania"]) && !empty($values["compania"])) {
                // Insertar seguro
                $query = "INSERT INTO seguros SET 
                         vehiculo_id = :vehiculo_id,
                         compania = :compania,
                         nro_poliza = :nro_poliza,
                         fecha_vencimiento = :fecha_vencimiento";

                parent::add($query, [
                    "vehiculo_id" => $result->data["newId"],
                    "compania" => $values["compania"],
                    "nro_poliza" => $values["nroPoliza"],
                    "fecha_vencimiento" => $values["vencimiento"]
                ]);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }

    public function updateVehiculo($id, $values)
    {
        try {
            $this->conn->beginTransaction();

            // Construimos la consulta SQL dinámicamente basada en los campos proporcionados
            $updateFields = [];
            $updateValues = ["id" => $id];

            // Mapeo de campos del frontend a la base de datos
            $fieldMapping = [
                "nro_interno" => "nro_interno",
                "designacion" => "designacion",
                "marca" => "marca",
                "modelo" => "modelo",
                "adquisicion" => "fecha_adquisicion",
                "motor" => "nro_motor",
                "chasis" => "nro_chasis",
                "patente" => "patente",
                "titulo" => "titulo",
                "estado" => "estado",
                "responsable" => "responsable",
                "ministerio" => "ministerio",
                "precio" => "precio",
                "compania" => "compania_seguro",
                "nroPoliza" => "nro_poliza",
                "vencimiento" => "fecha_vencimiento"
            ];

            foreach ($values as $key => $value) {
                if (isset($fieldMapping[$key])) {
                    $dbField = $fieldMapping[$key];
                    $updateFields[] = "$dbField = :$dbField";
                    $updateValues[$dbField] = $value;
                }
            }

            if (!empty($updateFields)) {
                $query = "UPDATE $this->table_name SET " . implode(", ", $updateFields) . " WHERE id = :id";
                parent::update($query, $updateValues);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }

    public function deleteVehiculo($id)
    {
        try {
            $this->conn->beginTransaction();

            // Primero eliminamos los seguros asociados
            $query = "DELETE FROM seguros WHERE vehiculo_id = :id";
            parent::delete($query, ["id" => $id]);

            // Luego eliminamos el vehículo
            $query = "DELETE FROM $this->table_name WHERE id = :id";
            parent::delete($query, ["id" => $id]);

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }
}
