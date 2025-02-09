<?php

namespace objects;

use objects\Base;

class Clientes extends Base
{
    private $table_name = "clientes";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db; // Asegurar que la conexión se almacena
    }

    public function getClientes()
    {
        $query = "SELECT * FROM $this->table_name ORDER BY created_at DESC";
        parent::getAll($query);
        return $this;
    }

    public function getCliente($id)
    {
        $query = "SELECT * FROM $this->table_name WHERE id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function setCliente($values)
    {
        $query = "INSERT INTO $this->table_name SET 
                 tipo_cliente = :tipo_cliente,
                 nombre = :nombre,
                 razon_social = :razon_social,
                 dni_cuit = :dni_cuit,
                 telefono = :telefono,
                 email = :email";

        parent::add($query, [
            "tipo_cliente" => $values["tipoCliente"],
            "nombre" => $values["tipoCliente"] === 'persona' ? $values["nombre"] : null,
            "razon_social" => $values["tipoCliente"] === 'empresa' ? $values["razonSocial"] : null,
            "dni_cuit" => $values["dniCuit"],
            "telefono" => $values["telefono"],
            "email" => $values["email"]
        ]);

        return $this;
    }

    public function updateCliente($id, $values)
    {
        $query = "UPDATE $this->table_name SET 
                 tipo_cliente = :tipo_cliente,
                 nombre = :nombre,
                 razon_social = :razon_social,
                 dni_cuit = :dni_cuit,
                 telefono = :telefono,
                 email = :email
                 WHERE id = :id";

        parent::update($query, [
            "id" => $id,
            "tipo_cliente" => $values["tipoCliente"],
            "nombre" => $values["tipoCliente"] === 'persona' ? $values["nombre"] : null,
            "razon_social" => $values["tipoCliente"] === 'empresa' ? $values["razonSocial"] : null,
            "dni_cuit" => $values["dniCuit"],
            "telefono" => $values["telefono"],
            "email" => $values["email"]
        ]);

        return $this;
    }

    public function deleteCliente($id)
    {
        // Primero verificamos si el cliente tiene entregas asociadas
        $query = "SELECT COUNT(*) as count FROM entregas WHERE cliente_id = :id";
        parent::getOne($query, ["id" => $id]);
        $result = parent::getResult();

        if ($result->ok && $result->data->count > 0) {
            $result->ok = false;
            $result->msg = "No se puede eliminar el cliente porque tiene entregas asociadas";
            return $this;
        }

        $query = "DELETE FROM $this->table_name WHERE id = :id";
        parent::delete($query, ["id" => $id]);
        return $this;
    }

    public function buscarPorDniCuit($dni_cuit)
    {
        $query = "SELECT * FROM $this->table_name WHERE dni_cuit = :dni_cuit";
        parent::getOne($query, ["dni_cuit" => $dni_cuit]);
        return $this;
    }
}
