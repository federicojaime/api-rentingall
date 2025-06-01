<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Entregas;
use \utils\Validate;

// GET todas las entregas
$app->get("/entregas", function (Request $request, Response $response, array $args) {
    $entregas = new Entregas($this->get("db"));
    $resp = $entregas->getEntregas()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET una entrega específica
$app->get("/entrega/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $entregas = new Entregas($this->get("db"));
    $resp = $entregas->getEntrega($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST nueva entrega
$app->post("/entrega", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();

    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "cliente_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "clientes"
        ],
        "funcionarioEntrega" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "funcionarioRecibe" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "dniEntrega" => [
            "type" => "string",
            "min" => 7,
            "max" => 20
        ],
        "dniRecibe" => [
            "type" => "string",
            "min" => 7,
            "max" => 20
        ],
        "fechaEntrega" => [
            "type" => "date"
        ],
        "lugarEntrega" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "kilometrajeEntrega" => [
            "type" => "number",
            "min" => 0
        ],
        "nivelCombustible" => [
            "type" => "string",
            "min" => 3,
            "max" => 10
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $entregas = new Entregas($this->get("db"));
        $resp = $entregas->setEntrega($fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH finalizar entrega
$app->patch("/entrega/{id:[0-9]+}/finalizar", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();

    $verificar = [
        "fechaDevolucion" => [
            "type" => "date"
        ],
        "lugarDevolucion" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "kilometrajeDevolucion" => [
            "type" => "number",
            "min" => 0
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $entregas = new Entregas($this->get("db"));
        $resp = $entregas->finalizarEntrega($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// DELETE eliminar entrega
$app->delete("/entrega/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $entregas = new Entregas($this->get("db"));
    $resp = $entregas->deleteEntrega($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET entregas por vehículo
$app->get("/entregas/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $query = "SELECT e.*, 
             CASE 
                WHEN c.tipo_cliente = 'persona' THEN c.nombre
                ELSE c.razon_social
             END as cliente_nombre,
             c.dni_cuit as cliente_documento
             FROM entregas e
             LEFT JOIN clientes c ON e.cliente_id = c.id
             WHERE e.vehiculo_id = :id
             ORDER BY e.fecha_entrega DESC";

    $entregas = new Entregas($this->get("db"));
    $resp = $entregas->getAll($query)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET entregas por cliente
$app->get("/entregas/cliente/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $query = "SELECT e.*, v.patente, v.marca, v.modelo
             FROM entregas e
             LEFT JOIN vehiculos v ON e.vehiculo_id = v.id
             WHERE e.cliente_id = :id
             ORDER BY e.fecha_entrega DESC";

    $entregas = new Entregas($this->get("db"));
    $result = $entregas->getAll($query, ["id" => $args["id"]]);

    // Verificar si el resultado es null
    if ($result === null) {
        $resp = (object) [
            'ok' => true,
            'msg' => 'No hay entregas para este cliente',
            'data' => []
        ];
    } else {
        $resp = $result->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
