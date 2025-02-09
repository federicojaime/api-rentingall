<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \objects\Stats;

$app->get("/stats", function (Request $request, Response $response, array $args) {
    $stats = new Stats($this->get("db"));
    $resp = $stats->getStats()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

$app->get("/stats/chart", function (Request $request, Response $response, array $args) {
    $stats = new Stats($this->get("db"));
    $resp = $stats->getChartData()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});
