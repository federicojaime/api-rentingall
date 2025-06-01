<?php

namespace objects;

use objects\Base;

class Logs extends Base
{
    private $table_name = "activity_logs";
    private $conn = null;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->conn = $db;
    }

    public function getLogs($filters = [])
    {
        $where = [];
        $params = [];

        // Construir filtros dinámicamente
        if (!empty($filters['user_id'])) {
            $where[] = "al.user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $where[] = "al.action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['table_name'])) {
            $where[] = "al.table_name = :table_name";
            $params['table_name'] = $filters['table_name'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "DATE(al.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "DATE(al.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $query = "SELECT al.*, 
                 u.firstname, u.lastname, u.email
                 FROM $this->table_name al
                 LEFT JOIN users u ON al.user_id = u.id
                 $whereClause
                 ORDER BY al.created_at DESC
                 LIMIT 1000";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getLog($id)
    {
        $query = "SELECT al.*, 
                 u.firstname, u.lastname, u.email
                 FROM $this->table_name al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.id = :id";
        parent::getOne($query, ["id" => $id]);
        return $this;
    }

    public function logActivity($user_id, $action, $table_name, $record_id, $old_data = null, $new_data = null, $description = null)
    {
        $query = "INSERT INTO $this->table_name SET 
                 user_id = :user_id,
                 action = :action,
                 table_name = :table_name,
                 record_id = :record_id,
                 old_data = :old_data,
                 new_data = :new_data,
                 description = :description,
                 ip_address = :ip_address,
                 user_agent = :user_agent";

        parent::add($query, [
            "user_id" => $user_id,
            "action" => $action,
            "table_name" => $table_name,
            "record_id" => $record_id,
            "old_data" => $old_data ? json_encode($old_data) : null,
            "new_data" => $new_data ? json_encode($new_data) : null,
            "description" => $description,
            "ip_address" => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        return $this;
    }

    public function getUserActivity($user_id, $limit = 50)
    {
        $query = "SELECT al.*, 
                 u.firstname, u.lastname
                 FROM $this->table_name al
                 LEFT JOIN users u ON al.user_id = u.id
                 WHERE al.user_id = :user_id
                 ORDER BY al.created_at DESC
                 LIMIT :limit";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':user_id', $user_id, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getTableActivity($table_name, $record_id = null)
    {
        $where = "WHERE al.table_name = :table_name";
        $params = ["table_name" => $table_name];

        if ($record_id) {
            $where .= " AND al.record_id = :record_id";
            $params["record_id"] = $record_id;
        }

        $query = "SELECT al.*, 
                 u.firstname, u.lastname, u.email
                 FROM $this->table_name al
                 LEFT JOIN users u ON al.user_id = u.id
                 $where
                 ORDER BY al.created_at DESC
                 LIMIT 100";

        try {
            $stmt = $this->conn->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $this->result = (object) [
                'ok' => true,
                'msg' => '',
                'data' => $results
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => []
            ];
        }

        return $this;
    }

    public function getActivityStats()
    {
        $query = "SELECT 
                 action,
                 table_name,
                 COUNT(*) as count,
                 DATE(created_at) as date
                 FROM $this->table_name
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY action, table_name, DATE(created_at)
                 ORDER BY date DESC, count DESC";

        parent::getAll($query);
        return $this;
    }

    public function cleanOldLogs($days = 90)
    {
        $query = "DELETE FROM $this->table_name 
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)";

        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindValue(':days', $days, \PDO::PARAM_INT);
            $stmt->execute();

            $this->result = (object) [
                'ok' => true,
                'msg' => "Se eliminaron logs anteriores a $days días",
                'data' => ["deleted_rows" => $stmt->rowCount()]
            ];
        } catch (\Exception $e) {
            $this->result = (object) [
                'ok' => false,
                'msg' => $e->getMessage(),
                'data' => null
            ];
        }

        return $this;
    }
}
