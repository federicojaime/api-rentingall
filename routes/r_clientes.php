<?php
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Clientes;
use \utils\Validate;



// GET todos los clientes
$app->get("/clientes", function (Request $request, Response $response, array $args) {
    $clientes = new Clientes($this->get("db"));
    $resp = $clientes->getClientes()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET un cliente específico
$app->get("/cliente/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $clientes = new Clientes($this->get("db"));
    $resp = $clientes->getCliente($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET buscar cliente por DNI/CUIT
$app->get("/cliente/buscar/{dni_cuit}", function (Request $request, Response $response, array $args) {
    $clientes = new Clientes($this->get("db"));
    $resp = $clientes->buscarPorDniCuit($args["dni_cuit"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST nuevo cliente
// POST nuevo cliente
$app->post("/cliente", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    // Validar que recibimos los datos
    if (!$fields) {
        $resp = new \stdClass();
        $resp->ok = false;
        $resp->msg = "No se recibieron datos";
        $resp->data = null;
        $response->getBody()->write(json_encode($resp));
        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(400);
    }

    // Log para debug
    error_log('Datos recibidos: ' . print_r($fields, true));

    $verificar = [
        "tipoCliente" => [
            "type" => "string",
            "min" => 1,
            "max" => 10
        ],
        "dniCuit" => [
            "type" => "string",
            "min" => 7,
            "max" => 20,
            "unique" => "clientes"
        ],
        "telefono" => [
            "type" => "string",
            "min" => 8,
            "max" => 20
        ],
        "email" => [
            "type" => "string",
            "isValidMail" => true
        ]
    ];

    // Agregamos validaciones específicas según el tipo de cliente
    if (isset($fields["tipoCliente"]) && $fields["tipoCliente"] === "persona") {
        $verificar["nombre"] = [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ];
    } else if (isset($fields["tipoCliente"]) && $fields["tipoCliente"] === "empresa") {
        $verificar["razonSocial"] = [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ];
    }

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
        $response->getBody()->write(json_encode($resp));
        return $response
            ->withHeader("Content-Type", "application/json")
            ->withStatus(400);
    }

    $clientes = new Clientes($this->get("db"));
    $resp = $clientes->setCliente($fields)->getResult();

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH actualizar cliente
$app->patch("/cliente/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    $verificar = [
        "tipoCliente" => [
            "type" => "string",
            "min" => 1,
            "max" => 10
        ],
        "dniCuit" => [
            "type" => "string",
            "min" => 7,
            "max" => 20
        ],
        "telefono" => [
            "type" => "string",
            "min" => 8,
            "max" => 20
        ],
        "email" => [
            "type" => "string",
            "isValidMail" => true
        ]
    ];

    if ($fields["tipoCliente"] === "persona") {
        $verificar["nombre"] = [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ];
    } else {
        $verificar["razonSocial"] = [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ];
    }

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $clientes = new Clientes($this->get("db"));
        $resp = $clientes->updateCliente($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// DELETE eliminar cliente
$app->delete("/cliente/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $clientes = new Clientes($this->get("db"));
    $resp = $clientes->deleteCliente($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
?>