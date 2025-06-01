<?php

namespace objects;

use objects\Base;

class Entregas extends Base
{
    private $table_name = "entregas";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db; // Asegurar que la conexión se almacena
    }

    public function getAll($query, $params = [])
    {
        try {
            // Si $params no contiene 'id' pero el query incluye ':id', extraerlo de $params
            if (strpos($query, ':id') !== false && !isset($params['id']) && func_num_args() > 1) {
                $args = func_get_args();
                if (isset($args[1]['id'])) {
                    $params = ['id' => $args[1]['id']];
                }
            }

            $stmt = $this->conn->prepare($query);

            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            // Asegurarnos de que siempre tenemos un resultado, incluso si está vacío
            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];

            return $this;
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];

            return $this;
        }
    }
    public function getEntregas()
    {
        $query = "SELECT e.*, 
                 v.patente, v.marca, v.modelo, v.designacion,
                 CASE 
                    WHEN c.tipo_cliente = 'persona' THEN c.nombre
                    ELSE c.razon_social
                 END as cliente_nombre,
                 c.dni_cuit as cliente_documento,
                 i.*
                 FROM $this->table_name e
                 LEFT JOIN vehiculos v ON e.vehiculo_id = v.id
                 LEFT JOIN clientes c ON e.cliente_id = c.id
                 LEFT JOIN inventario_entrega i ON e.id = i.entrega_id
                 ORDER BY e.fecha_entrega DESC";
        parent::getAll($query);
        return $this;
    }

    public function getEntrega($id)
    {
        $query = "SELECT e.*, 
          v.marca, v.modelo, v.patente, v.designacion,
          c.nombre as cliente_nombre, c.dni_cuit as cliente_documento,
          i.*
          FROM entregas e
          LEFT JOIN vehiculos v ON e.vehiculo_id = v.id
          LEFT JOIN clientes c ON e.cliente_id = c.id
          LEFT JOIN inventario_entrega i ON e.id = i.entrega_id
          WHERE e.id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function setEntrega($values)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Insertar la entrega
            $query = "INSERT INTO $this->table_name SET 
                     vehiculo_id = :vehiculo_id,
                     cliente_id = :cliente_id,
                     funcionario_entrega = :funcionario_entrega,
                     funcionario_recibe = :funcionario_recibe,
                     dni_entrega = :dni_entrega,
                     dni_recibe = :dni_recibe,
                     fecha_entrega = :fecha_entrega,
                     fecha_devolucion = :fecha_devolucion,
                     lugar_entrega = :lugar_entrega,
                     lugar_devolucion = :lugar_devolucion,
                     kilometraje_entrega = :kilometraje_entrega,
                     kilometraje_devolucion = :kilometraje_devolucion,
                     nivel_combustible = :nivel_combustible,
                     observaciones = :observaciones";

            parent::add($query, [
                "vehiculo_id" => $values["vehiculo_id"],
                "cliente_id" => $values["cliente_id"],
                "funcionario_entrega" => $values["funcionarioEntrega"],
                "funcionario_recibe" => $values["funcionarioRecibe"],
                "dni_entrega" => $values["dniEntrega"],
                "dni_recibe" => $values["dniRecibe"],
                "fecha_entrega" => $values["fechaEntrega"],
                "fecha_devolucion" => $values["fechaDevolucion"] ?? null,
                "lugar_entrega" => $values["lugarEntrega"],
                "lugar_devolucion" => $values["lugarDevolucion"] ?? null,
                "kilometraje_entrega" => $values["kilometrajeEntrega"],
                "kilometraje_devolucion" => $values["kilometrajeDevolucion"] ?? null,
                "nivel_combustible" => $values["nivelCombustible"],
                "observaciones" => $values["observaciones"] ?? null
            ]);

            $result = parent::getResult();

            if ($result->ok) {
                // 2. Insertar el inventario
                $query = "INSERT INTO inventario_entrega SET 
         entrega_id = :entrega_id,
         luces_principales = :luces_principales,
         luz_media = :luz_media,
         luz_stop = :luz_stop,
         antena_radio = :antena_radio,
         limpia_parabrisas = :limpia_parabrisas,
         espejo_izquierdo = :espejo_izquierdo,
         espejo_derecho = :espejo_derecho,
         vidrios_laterales = :vidrios_laterales,
         parabrisas = :parabrisas,
         tapones = :tapones,
         tapon_gasolina = :tapon_gasolina,
         carroceria = :carroceria,
         parachoque_delantero = :parachoque_delantero,
         parachoque_trasero = :parachoque_trasero,
         placas = :placas,
         calefaccion = :calefaccion,
         radio_cd = :radio_cd,
         bocinas = :bocinas,
         encendedor = :encendedor,
         espejo_retrovisor = :espejo_retrovisor,
         ceniceros = :ceniceros,
         cinturones = :cinturones,
         manijas_vidrios = :manijas_vidrios,
         pisos_goma = :pisos_goma,
         tapetes = :tapetes,
         funda_asientos = :funda_asientos,
         jalador_puertas = :jalador_puertas,
         sujetador_manos = :sujetador_manos,
         gato = :gato,
         llave_rueda = :llave_rueda,
         estuche_llaves = :estuche_llaves,
         triangulo = :triangulo,
         llanta_auxilio = :llanta_auxilio,
         extintor = :extintor,
         botiquin = :botiquin,
         otros = :otros,
         soat = :soat,
         inspeccion_tecnica = :inspeccion_tecnica";

                parent::add($query, [
                    "entrega_id" => $result->data["newId"],
                    "luces_principales" => $values["inventario"]["lucesPrincipales"] ?? false,
                    "luz_media" => $values["inventario"]["luzMedia"] ?? false,
                    "luz_stop" => $values["inventario"]["luzStop"] ?? false,
                    "antena_radio" => $values["inventario"]["antenaRadio"] ?? false,
                    "limpia_parabrisas" => $values["inventario"]["limpiaParabrisas"] ?? false,
                    "espejo_izquierdo" => $values["inventario"]["espejoIzquierdo"] ?? false,
                    "espejo_derecho" => $values["inventario"]["espejoDerecho"] ?? false,
                    "vidrios_laterales" => $values["inventario"]["vidriosLaterales"] ?? false,
                    "parabrisas" => $values["inventario"]["parabrisas"] ?? false,
                    "tapones" => $values["inventario"]["tapones"] ?? false,
                    "tapon_gasolina" => $values["inventario"]["taponGasolina"] ?? false,
                    "carroceria" => $values["inventario"]["carroceria"] ?? false,
                    "parachoque_delantero" => $values["inventario"]["parachoqueDelantero"] ?? false,
                    "parachoque_trasero" => $values["inventario"]["parachoqueTrasero"] ?? false,
                    "placas" => $values["inventario"]["placas"] ?? false,
                    // Nuevos campos
                    "calefaccion" => $values["inventario"]["calefaccion"] ?? false,
                    "radio_cd" => $values["inventario"]["radioCd"] ?? false,
                    "bocinas" => $values["inventario"]["bocinas"] ?? false,
                    "encendedor" => $values["inventario"]["encendedor"] ?? false,
                    "espejo_retrovisor" => $values["inventario"]["espejoRetrovisor"] ?? false,
                    "ceniceros" => $values["inventario"]["ceniceros"] ?? false,
                    "cinturones" => $values["inventario"]["cinturones"] ?? false,
                    "manijas_vidrios" => $values["inventario"]["manijasVidrios"] ?? false,
                    "pisos_goma" => $values["inventario"]["pisosGoma"] ?? false,
                    "tapetes" => $values["inventario"]["tapetes"] ?? false,
                    "funda_asientos" => $values["inventario"]["fundaAsientos"] ?? false,
                    "jalador_puertas" => $values["inventario"]["jaladorPuertas"] ?? false,
                    "sujetador_manos" => $values["inventario"]["sujetadorManos"] ?? false,
                    "gato" => $values["inventario"]["gato"] ?? false,
                    "llave_rueda" => $values["inventario"]["llaveRueda"] ?? false,
                    "estuche_llaves" => $values["inventario"]["estucheLlaves"] ?? false,
                    "triangulo" => $values["inventario"]["triangulo"] ?? false,
                    "llanta_auxilio" => $values["inventario"]["llantaAuxilio"] ?? false,
                    "extintor" => $values["inventario"]["extintor"] ?? false,
                    "botiquin" => $values["inventario"]["botiquin"] ?? false,
                    "otros" => $values["inventario"]["otros"] ?? false,
                    "soat" => $values["inventario"]["soat"] ?? false,
                    "inspeccion_tecnica" => $values["inventario"]["inspeccionTecnica"] ?? false
                ]);

                // 3. Actualizar estado del vehículo
                $query = "UPDATE vehiculos SET estado = 'ALQUILADA' WHERE id = :id";
                parent::update($query, ["id" => $values["vehiculo_id"]]);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }

    public function finalizarEntrega($id, $values)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Actualizar la entrega con los datos de devolución
            $query = "UPDATE $this->table_name SET 
                     fecha_devolucion = :fecha_devolucion,
                     lugar_devolucion = :lugar_devolucion,
                     kilometraje_devolucion = :kilometraje_devolucion,
                     observaciones = CONCAT(COALESCE(observaciones, ''), '\n', :observaciones)
                     WHERE id = :id";

            parent::update($query, [
                "id" => $id,
                "fecha_devolucion" => $values["fechaDevolucion"],
                "lugar_devolucion" => $values["lugarDevolucion"],
                "kilometraje_devolucion" => $values["kilometrajeDevolucion"],
                "observaciones" => $values["observaciones"] ?? null
            ]);

            // 2. Obtener el vehiculo_id de la entrega
            $query = "SELECT vehiculo_id FROM $this->table_name WHERE id = :id";
            parent::getOne($query, ["id" => $id]);
            $result = parent::getResult();

            if ($result->ok && isset($result->data->vehiculo_id)) {
                // 3. Actualizar estado del vehículo
                $query = "UPDATE vehiculos SET estado = 'DISPONIBLE' WHERE id = :id";
                parent::update($query, ["id" => $result->data->vehiculo_id]);
            }

            $this->conn->commit();
        } catch (\Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }

        return $this;
    }

    public function deleteEntrega($id)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Eliminar el inventario
            $query = "DELETE FROM inventario_entrega WHERE entrega_id = :id";
            parent::delete($query, ["id" => $id]);

            // 2. Eliminar la entrega
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
