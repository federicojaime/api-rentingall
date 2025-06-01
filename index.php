<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

require(__DIR__ . "/vendor/autoload.php");

$container = new Container();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container->set("db", function () {
	$con = array(
		"host" => "srv1597.hstgr.io", // Hostinger usually uses localhost
		"dbname" => "u565673608_bd_renting_all",
		"user" => "u565673608_bd_renting_all",
		"pass" => "wsN9[Hl0:R"
	);
	$pdo = new PDO("mysql:host=" . $con["host"] . ";dbname=" . $con["dbname"], $con["user"], $con["pass"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
	return $pdo;
});

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->setBasePath(preg_replace("/(.*)\/.*/", "$1", $_SERVER["SCRIPT_NAME"]));

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->add(new \Tuupola\Middleware\JwtAuthentication([
	"ignore" => [
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/login",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/register",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/password/recover",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/password/temp",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/token/validate"
	],
	"secret" => $_ENV["JWT_SECRET_KEY"],
	"algorithm" => $_ENV["JWT_ALGORITHM"],
	"attribute" => "jwt",
	"error" => function ($response, $arguments) {
		$data["ok"] = false;
		$data["msg"] = $arguments["message"];
		$response->getBody()->write(
			json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
		);
		return $response->withHeader("Content-Type", "application/json");
	}
]));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->options("/{routes:.+}", function ($request, $response, $args) {
	return $response;
});

$app->add(function ($request, $handler) {
	$response = $handler->handle($request);
	return $response
		->withHeader("Access-Control-Allow-Origin", "*")
		->withHeader("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Accept, Origin, Authorization")
		->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, PATCH, OPTIONS");
});

$app->get("/", function (Request $request, Response $response, array $args) {
	$response->getBody()->write("API-TEST");
	return $response;
});

// Cargamos todas las rutas
require_once("routes/r_users.php");
require_once("routes/r_vehiculos.php");
require_once("routes/r_clientes.php");
require_once("routes/r_entregas.php");
require_once("routes/r_facturas.php");
require_once("routes/r_stats.php");


$app->map(["GET", "POST", "PUT", "DELETE", "PATCH"], "/{routes:.+}", function ($request, $response) {
	throw new HttpNotFoundException($request);
});

$app->run();
