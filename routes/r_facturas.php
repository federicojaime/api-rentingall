<?php
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Facturas;
use \utils\Validate;

// GET todas las facturas
$app->get("/facturas", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getFacturas()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET una factura específica
$app->get("/factura/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getFactura($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET facturas por vehículo
$app->get("/facturas/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getFacturasPorVehiculo($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET facturas por mes
$app->get("/facturas/{year:[0-9]+}/{month:[0-9]+}", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getFacturasPorMes($args["year"], $args["month"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET estadísticas de facturación
$app->get("/facturas/estadisticas", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getEstadisticas()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET estadísticas por mes
$app->get("/facturas/estadisticas/{year:[0-9]+}/{month:[0-9]+}", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->getEstadisticasPorMes($args["year"], $args["month"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST nueva factura
$app->post("/factura", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "fecha" => [
            "type" => "date"
        ],
        "numero" => [
            "type" => "string",
            "min" => 1,
            "max" => 50,
            "unique" => "facturas"
        ],
        "monto" => [
            "type" => "number",
            "min" => 0
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $facturas = new Facturas($this->get("db"));
        $resp = $facturas->setFactura($fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH actualizar factura
$app->patch("/factura/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    $verificar = [
        "vehiculo_id" => [
            "type" => "number",
            "min" => 1,
            "exist" => "vehiculos"
        ],
        "fecha" => [
            "type" => "date"
        ],
        "numero" => [
            "type" => "string",
            "min" => 1,
            "max" => 50
        ],
        "monto" => [
            "type" => "number",
            "min" => 0
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $facturas = new Facturas($this->get("db"));
        $resp = $facturas->updateFactura($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH actualizar estado de pago
$app->patch("/factura/{id:[0-9]+}/pago", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    $verificar = [
        "pagado" => [
            "type" => "number",
            "min" => 0,
            "max" => 1
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $facturas = new Facturas($this->get("db"));
        $resp = $facturas->actualizarEstadoPago($args["id"], $fields["pagado"])->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// DELETE eliminar factura
$app->delete("/factura/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $facturas = new Facturas($this->get("db"));
    $resp = $facturas->deleteFactura($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
?>