<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Seguros;
use \utils\Validate;

// GET todos los seguros
$app->get("/seguros", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getSeguros()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET un seguro específico
$app->get("/seguro/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getSeguro($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET seguros por vehículo
$app->get("/seguros/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getSegurosPorVehiculo($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET seguros vencidos
$app->get("/seguros/vencidos", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getSegurosVencidos()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET seguros próximos a vencer
$app->get("/seguros/proximos-vencer", function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $dias = isset($queryParams['dias']) ? (int)$queryParams['dias'] : 30;

    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getSegurosProximosVencer($dias)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET estadísticas de seguros
$app->get("/seguros/estadisticas", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->getEstadisticasSeguros()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST nuevo seguro
$app->post("/seguro", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();

    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "compania" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "nroPoliza" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "fechaInicio" => [
            "type" => "date"
        ],
        "fechaVencimiento" => [
            "type" => "date"
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $seguros = new Seguros($this->get("db"));
        $resp = $seguros->setSeguro($fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH actualizar seguro
$app->patch("/seguro/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();

    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "compania" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "nroPoliza" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "fechaInicio" => [
            "type" => "date"
        ],
        "fechaVencimiento" => [
            "type" => "date"
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $seguros = new Seguros($this->get("db"));
        $resp = $seguros->updateSeguro($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST renovar seguro
$app->post("/seguro/{id:[0-9]+}/renovar", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();

    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "compania" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "nroPoliza" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "fechaInicio" => [
            "type" => "date"
        ],
        "fechaVencimiento" => [
            "type" => "date"
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $seguros = new Seguros($this->get("db"));
        $resp = $seguros->renovarSeguro($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// DELETE eliminar seguro
$app->delete("/seguro/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $seguros = new Seguros($this->get("db"));
    $resp = $seguros->deleteSeguro($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
