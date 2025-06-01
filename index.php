<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;
use utils\LogMiddleware;

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

// Middleware de JWT
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

// Middleware de logging automático
$app->add(new LogMiddleware($container->get("db"), [
	'/stats',
	'/logs',
	'/reportes'
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
	$response->getBody()->write("API de Alquiler de Vehículos - v2.0");
	return $response;
});

// Endpoint de salud de la API
$app->get("/health", function (Request $request, Response $response, array $args) {
	$health = [
		"status" => "ok",
		"timestamp" => date('c'),
		"version" => "2.0.0",
		"database" => "connected",
		"endpoints" => [
			"users" => "active",
			"vehicles" => "active", 
			"clients" => "active",
			"deliveries" => "active",
			"invoices" => "active",
			"insurance" => "active",
			"logs" => "active",
			"reports" => "active",
			"stats" => "active"
		]
	];
	
	try {
		$db = $this->get("db");
		$stmt = $db->query("SELECT 1");
		$health["database"] = "connected";
	} catch (Exception $e) {
		$health["database"] = "error";
		$health["status"] = "error";
	}
	
	$response->getBody()->write(json_encode($health, JSON_PRETTY_PRINT));
	return $response->withHeader("Content-Type", "application/json");
});

// Cargar todas las rutas existentes
require_once("routes/r_users.php");
require_once("routes/r_vehiculos.php");
require_once("routes/r_clientes.php");
require_once("routes/r_entregas.php");
require_once("routes/r_facturas.php");
require_once("routes/r_stats.php");

// Cargar las nuevas rutas
require_once("routes/r_logs.php");
require_once("routes/r_seguros.php");
require_once("routes/r_reportes.php");

// Endpoint para obtener información de la API
$app->get("/api/info", function (Request $request, Response $response, array $args) {
	$info = [
		"name" => "API de Alquiler de Vehículos",
		"version" => "2.0.0",
		"description" => "API completa para gestión de alquiler de vehículos con sistema de logs, reportes y seguros",
		"endpoints" => [
			"authentication" => [
				"POST /user/login" => "Iniciar sesión",
				"POST /user/register" => "Registrar usuario",
				"GET /user/token/validate/{token}" => "Validar token",
				"POST /user/password/recover" => "Recuperar contraseña"
			],
			"users" => [
				"GET /users" => "Obtener todos los usuarios",
				"GET /user/{id}" => "Obtener usuario por ID",
				"POST /user" => "Crear usuario",
				"DELETE /user/{id}" => "Eliminar usuario",
				"PATCH /user/password" => "Cambiar contraseña"
			],
			"clients" => [
				"GET /clientes" => "Obtener todos los clientes",
				"GET /cliente/{id}" => "Obtener cliente por ID",
				"GET /cliente/buscar/{dni_cuit}" => "Buscar cliente por DNI/CUIT",
				"POST /cliente" => "Crear cliente",
				"PATCH /cliente/{id}" => "Actualizar cliente",
				"DELETE /cliente/{id}" => "Eliminar cliente"
			],
			"vehicles" => [
				"GET /vehiculos" => "Obtener todos los vehículos",
				"GET /vehiculo/{id}" => "Obtener vehículo por ID",
				"POST /vehiculo" => "Crear vehículo",
				"PATCH /vehiculo/{id}" => "Actualizar vehículo",
				"DELETE /vehiculo/{id}" => "Eliminar vehículo"
			],
			"deliveries" => [
				"GET /entregas" => "Obtener todas las entregas",
				"GET /entrega/{id}" => "Obtener entrega por ID",
				"GET /entregas/vehiculo/{id}" => "Entregas por vehículo",
				"GET /entregas/cliente/{id}" => "Entregas por cliente",
				"POST /entrega" => "Crear entrega",
				"PATCH /entrega/{id}/finalizar" => "Finalizar entrega",
				"DELETE /entrega/{id}" => "Eliminar entrega"
			],
			"invoices" => [
				"GET /facturas" => "Obtener todas las facturas",
				"GET /factura/{id}" => "Obtener factura por ID",
				"GET /facturas/vehiculo/{id}" => "Facturas por vehículo",
				"GET /facturas/{year}/{month}" => "Facturas por mes",
				"GET /facturas/estadisticas" => "Estadísticas de facturación",
				"POST /factura" => "Crear factura",
				"PATCH /factura/{id}" => "Actualizar factura",
				"PATCH /factura/{id}/pago" => "Actualizar estado de pago",
				"DELETE /factura/{id}" => "Eliminar factura"
			],
			"insurance" => [
				"GET /seguros" => "Obtener todos los seguros",
				"GET /seguro/{id}" => "Obtener seguro por ID",
				"GET /seguros/vehiculo/{id}" => "Seguros por vehículo",
				"GET /seguros/vencidos" => "Seguros vencidos",
				"GET /seguros/proximos-vencer" => "Seguros próximos a vencer",
				"GET /seguros/estadisticas" => "Estadísticas de seguros",
				"POST /seguro" => "Crear seguro",
				"PATCH /seguro/{id}" => "Actualizar seguro",
				"POST /seguro/{id}/renovar" => "Renovar seguro",
				"DELETE /seguro/{id}" => "Eliminar seguro"
			],
			"logs" => [
				"GET /logs" => "Obtener logs con filtros",
				"GET /log/{id}" => "Obtener log específico",
				"GET /logs/user/{user_id}" => "Actividad de usuario",
				"GET /logs/table/{table_name}" => "Actividad de tabla",
				"GET /logs/stats" => "Estadísticas de actividad",
				"POST /log" => "Registrar actividad manual",
				"DELETE /logs/clean/{days}" => "Limpiar logs antiguos"
			],
			"reports" => [
				"GET /reportes/vehiculos" => "Reporte de vehículos",
				"GET /reportes/entregas" => "Reporte de entregas",
				"GET /reportes/facturacion" => "Reporte de facturación",
				"GET /reportes/clientes" => "Reporte de clientes",
				"GET /reportes/inventario" => "Reporte de inventario",
				"GET /reportes/dashboard" => "Reporte dashboard",
				"GET /reportes/comparativo/{periodo1}/{periodo2}" => "Reporte comparativo",
				"GET /reportes/kilometraje" => "Reporte de kilometraje",
				"GET /reportes/export/{tipo}" => "Exportar reporte a CSV"
			],
			"statistics" => [
				"GET /stats" => "Estadísticas generales",
				"GET /stats/chart" => "Datos para gráficos"
			]
		],
		"features" => [
			"JWT Authentication",
			"Automatic Activity Logging",
			"Comprehensive Reporting",
			"Insurance Management",
			"Vehicle Fleet Management",
			"Client Management",
			"Delivery Tracking",
			"Invoice Management",
			"CSV Export",
			"Dashboard Statistics"
		],
		"author" => "Sistema de Gestión de Alquiler de Vehículos",
		"documentation" => "/documentacion/documentacion.md"
	];
	
	$response->getBody()->write(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	return $response->withHeader("Content-Type", "application/json");
});

$app->map(["GET", "POST", "PUT", "DELETE", "PATCH"], "/{routes:.+}", function ($request, $response) {
	throw new HttpNotFoundException($request);
});

$app->run();