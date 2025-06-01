<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Logs;
use \utils\Validate;

// GET todos los logs con filtros opcionales
$app->get("/logs", function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();

    $filtros = [];
    if (!empty($queryParams['user_id'])) {
        $filtros['user_id'] = $queryParams['user_id'];
    }
    if (!empty($queryParams['action'])) {
        $filtros['action'] = $queryParams['action'];
    }
    if (!empty($queryParams['table_name'])) {
        $filtros['table_name'] = $queryParams['table_name'];
    }
    if (!empty($queryParams['date_from'])) {
        $filtros['date_from'] = $queryParams['date_from'];
    }
    if (!empty($queryParams['date_to'])) {
        $filtros['date_to'] = $queryParams['date_to'];
    }

    $logs = new Logs($this->get("db"));
    $resp = $logs->getLogs($filtros)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET un log específico
$app->get("/log/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $logs = new Logs($this->get("db"));
    $resp = $logs->getLog($args["id"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET actividad de un usuario específico
$app->get("/logs/user/{user_id:[0-9]+}", function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 50;

    $logs = new Logs($this->get("db"));
    $resp = $logs->getUserActivity($args["user_id"], $limit)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET actividad de una tabla específica
$app->get("/logs/table/{table_name}", function (Request $request, Response $response, array $args) {
    $queryParams = $request->getQueryParams();
    $record_id = isset($queryParams['record_id']) ? $queryParams['record_id'] : null;

    $logs = new Logs($this->get("db"));
    $resp = $logs->getTableActivity($args["table_name"], $record_id)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// GET estadísticas de actividad
$app->get("/logs/stats", function (Request $request, Response $response, array $args) {
    $logs = new Logs($this->get("db"));
    $resp = $logs->getActivityStats()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// POST registrar actividad manualmente (para casos especiales)
$app->post("/log", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    $jwt = $request->getAttribute('jwt');
    $user_id = $jwt['data']['id'];

    $verificar = [
        "action" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "table_name" => [
            "type" => "string",
            "min" => 3,
            "max" => 50
        ],
        "record_id" => [
            "type" => "number",
            "min" => 1
        ]
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        $logs = new Logs($this->get("db"));
        $resp = $logs->logActivity(
            $user_id,
            $fields["action"],
            $fields["table_name"],
            $fields["record_id"],
            $fields["old_data"] ?? null,
            $fields["new_data"] ?? null,
            $fields["description"] ?? null
        )->getResult();
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

// DELETE limpiar logs antiguos
$app->delete("/logs/clean/{days:[0-9]+}", function (Request $request, Response $response, array $args) {
    $logs = new Logs($this->get("db"));
    $resp = $logs->cleanOldLogs($args["days"])->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
