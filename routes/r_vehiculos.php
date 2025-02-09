<?php
use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Vehiculos;
use \utils\Validate;

// GET todos los vehículos
$app->get("/vehiculos", function (Request $request, Response $response, array $args) {
    $vehiculos = new Vehiculos($this->get("db"));
    $resp = $vehiculos->getVehiculos()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET un vehículo específico
$app->get("/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $vehiculos = new Vehiculos($this->get("db"));
    $resp = $vehiculos->getVehiculo($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST nuevo vehículo
$app->post("/vehiculo", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    $verificar = [
        "nroInterno" => [
            "type" => "string",
            "min" => 1,
            "max" => 20
        ],
        "designacion" => [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ],
        "marca" => [
            "type" => "string",
            "min" => 2,
            "max" => 50
        ],
        "modelo" => [
            "type" => "string",
            "min" => 2,
            "max" => 50
        ],
        "adquisicion" => [
            "type" => "date"
        ],
        "motor" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "chasis" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "patente" => [
            "type" => "string",
            "min" => 6,
            "max" => 10
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $vehiculos = new Vehiculos($this->get("db"));
        $resp = $vehiculos->setVehiculo($fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// PATCH actualizar vehículo
$app->patch("/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    
    // Solo definimos las reglas para los campos que vienen en la petición
    $verificar = [];
    
    if(isset($fields["nro_interno"])) {
        $verificar["nro_interno"] = [
            "type" => "string",
            "min" => 1,
            "max" => 20
        ];
    }
    if(isset($fields["designacion"])) {
        $verificar["designacion"] = [
            "type" => "string",
            "min" => 3,
            "max" => 100
        ];
    }
    if(isset($fields["marca"])) {
        $verificar["marca"] = [
            "type" => "string",
            "min" => 2,
            "max" => 50
        ];
    }
    if(isset($fields["modelo"])) {
        $verificar["modelo"] = [
            "type" => "string",
            "min" => 2,
            "max" => 50
        ];
    }
    if(isset($fields["adquisicion"])) {
        $verificar["adquisicion"] = [
            "type" => "date"
        ];
    }
    if(isset($fields["motor"])) {
        $verificar["motor"] = [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ];
    }
    if(isset($fields["chasis"])) {
        $verificar["chasis"] = [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ];
    }
    if(isset($fields["patente"])) {
        $verificar["patente"] = [
            "type" => "string",
            "min" => 6,
            "max" => 10
        ];
    }
    if(isset($fields["titulo"])) {
        $verificar["titulo"] = [
            "type" => "string",
            "min" => 1,
            "max" => 100
        ];
    }
    if(isset($fields["estado"])) {
        $verificar["estado"] = [
            "type" => "string"
        ];
    }
    
    // Campos opcionales - solo los validamos si vienen con valor
    if(isset($fields["responsable"]) && $fields["responsable"] !== "") {
        $verificar["responsable"] = [
            "type" => "string",
            "max" => 100
        ];
    }
    if(isset($fields["ministerio"]) && $fields["ministerio"] !== "") {
        $verificar["ministerio"] = [
            "type" => "string",
            "max" => 100
        ];
    }
    if(isset($fields["precio"]) && $fields["precio"] !== "") {
        $verificar["precio"] = [
            "type" => "number"
        ];
    }
    if(isset($fields["compania"]) && $fields["compania"] !== "") {
        $verificar["compania"] = [
            "type" => "string",
            "max" => 100
        ];
    }
    if(isset($fields["nro_poliza"]) && $fields["nro_poliza"] !== "") {
        $verificar["nro_poliza"] = [
            "type" => "string",
            "max" => 20
        ];
    }
    if(isset($fields["vencimiento"]) && $fields["vencimiento"] !== "") {
        $verificar["vencimiento"] = [
            "type" => "date"
        ];
    }

    $validacion = new Validate($this->get("db"));
    if(!empty($verificar)) {
        $validacion->validar($fields, $verificar);
    }

    if($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $vehiculos = new Vehiculos($this->get("db"));
        $resp = $vehiculos->updateVehiculo($args["id"], $fields)->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
// DELETE eliminar vehículo
$app->delete("/vehiculo/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $vehiculos = new Vehiculos($this->get("db"));
    $resp = $vehiculos->deleteVehiculo($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
?>