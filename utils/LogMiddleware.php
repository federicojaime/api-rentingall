<?php

namespace utils;

use objects\Logs;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogMiddleware implements MiddlewareInterface
{
    private $db;
    private $exclude_paths;

    public function __construct($db, $exclude_paths = [])
    {
        $this->db = $db;
        $this->exclude_paths = array_merge([
            '/logs',
            '/stats',
            '/user/token/validate'
        ], $exclude_paths);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // No loggear ciertas rutas
        foreach ($this->exclude_paths as $exclude) {
            if (strpos($uri, $exclude) !== false) {
                return $handler->handle($request);
            }
        }

        // Solo loggear operaciones de modificación
        if (!in_array($method, ['POST', 'PATCH', 'DELETE'])) {
            return $handler->handle($request);
        }

        // Obtener datos del usuario del JWT
        $jwt = $request->getAttribute('jwt');
        $user_id = $jwt['data']['id'] ?? null;

        if (!$user_id) {
            return $handler->handle($request);
        }

        // Obtener datos antes de la operación (para UPDATE y DELETE)
        $old_data = null;
        $table_name = $this->extractTableFromUri($uri);
        $record_id = $this->extractIdFromUri($uri);

        if (($method === 'PATCH' || $method === 'DELETE') && $table_name && $record_id) {
            $old_data = $this->getRecordData($table_name, $record_id);
        }

        // Procesar la request
        $response = $handler->handle($request);

        // Solo loggear si la operación fue exitosa
        $response_body = (string) $response->getBody();
        $response_data = json_decode($response_body, true);

        if ($response_data && isset($response_data['ok']) && $response_data['ok']) {
            $this->logOperation($user_id, $method, $uri, $table_name, $record_id, $old_data, $request, $response_data);
        }

        return $response;
    }

    private function extractTableFromUri($uri)
    {
        // Mapear URIs a nombres de tabla
        $table_mapping = [
            '/user' => 'users',
            '/cliente' => 'clientes',
            '/vehiculo' => 'vehiculos',
            '/entrega' => 'entregas',
            '/factura' => 'facturas',
            '/seguro' => 'seguros'
        ];

        foreach ($table_mapping as $path => $table) {
            if (strpos($uri, $path) !== false) {
                return $table;
            }
        }

        return null;
    }

    private function extractIdFromUri($uri)
    {
        // Extraer ID de URIs como /user/123 o /vehiculo/456/finalizar
        if (preg_match('/\/(\w+)\/(\d+)/', $uri, $matches)) {
            return (int) $matches[2];
        }
        return null;
    }

    private function getRecordData($table_name, $record_id)
    {
        try {
            $query = "SELECT * FROM {$table_name} WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $record_id);
            $stmt->execute();
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function logOperation($user_id, $method, $uri, $table_name, $record_id, $old_data, $request, $response_data)
    {
        try {
            $action = $this->mapMethodToAction($method, $uri);
            $new_data = null;
            $description = $this->generateDescription($action, $uri, $request);

            // Para POST, obtener el ID del nuevo registro
            if ($method === 'POST' && isset($response_data['data']['newId'])) {
                $record_id = $response_data['data']['newId'];
                if ($table_name && $record_id) {
                    $new_data = $this->getRecordData($table_name, $record_id);
                }
            }

            // Para PATCH, obtener los datos nuevos
            if ($method === 'PATCH' && $table_name && $record_id) {
                $new_data = $this->getRecordData($table_name, $record_id);
            }

            $logs = new Logs($this->db);
            $logs->logActivity(
                $user_id,
                $action,
                $table_name ?: 'system',
                $record_id ?: 0,
                $old_data,
                $new_data,
                $description
            );
        } catch (\Exception $e) {
            // No interrumpir la operación si falla el logging
            error_log("Error en LogMiddleware: " . $e->getMessage());
        }
    }

    private function mapMethodToAction($method, $uri)
    {
        $action_mapping = [
            'POST' => 'CREATE',
            'PATCH' => 'UPDATE',
            'DELETE' => 'DELETE'
        ];

        $base_action = $action_mapping[$method] ?? 'UNKNOWN';

        // Acciones especiales basadas en la URI
        if (strpos($uri, '/login') !== false) {
            return 'LOGIN';
        }
        if (strpos($uri, '/finalizar') !== false) {
            return 'FINALIZE';
        }
        if (strpos($uri, '/renovar') !== false) {
            return 'RENEW';
        }
        if (strpos($uri, '/pago') !== false) {
            return 'PAYMENT_UPDATE';
        }

        return $base_action;
    }

    private function generateDescription($action, $uri, $request)
    {
        $descriptions = [
            'CREATE' => 'Registro creado',
            'UPDATE' => 'Registro actualizado',
            'DELETE' => 'Registro eliminado',
            'LOGIN' => 'Inicio de sesión',
            'FINALIZE' => 'Entrega finalizada',
            'RENEW' => 'Seguro renovado',
            'PAYMENT_UPDATE' => 'Estado de pago actualizado'
        ];

        $base_description = $descriptions[$action] ?? 'Operación realizada';

        // Agregar contexto adicional si está disponible
        $parsed_body = $request->getParsedBody();
        if ($parsed_body && is_array($parsed_body)) {
            $context_fields = ['numero', 'patente', 'nombre', 'email', 'nro_interno'];
            foreach ($context_fields as $field) {
                if (isset($parsed_body[$field])) {
                    $base_description .= " - {$field}: {$parsed_body[$field]}";
                    break;
                }
            }
        }

        return $base_description;
    }
}
