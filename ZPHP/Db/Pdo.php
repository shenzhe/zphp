<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Db;
class Pdo
{
    private $pdo;
    private $dbName;
    private $tableName;
    private $className;
    private $config;

    public function __construct($config, $className = null, $dbName = null)
    {
        $this->config = $config;
        if (!empty($className)) {
            $this->className = $className;
        }
        if (empty($dbName)) {
            $this->dbName = $config['dbname'];
        } else {
            $this->dbName = $dbName;
        }
        $this->checkPing();
    }

    public function checkPing()
    {
        if (empty($this->pdo)) {
            $this->pdo = $this->connect();
        } elseif (!empty($this->config['ping'])) {
            $this->ping();
        }
    }

    private function connect()
    {
        return new \PDO($this->config['dsn'], $this->config['user'], $this->config['pass'], array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$this->config['charset']}';",
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => empty($this->config['pconnect']) ? false : true
            ));

    }

    public function getDBName()
    {
        return $this->dbName;
    }


    public function setDBName($dbName)
    {
        if (empty($dbName)) {
            return;
        }
        $this->dbName = $dbName;
    }
	//add set TableName change by ahuo 2013-11-05 14:23
	public function setTableName($tableName)
	{
		if(empty($tableName)){
			return;
		}
		$this->tableName = $tableName;
	}

    public function getTableName()
    {
        if (empty($this->tableName)) {
            $classRef = new \ReflectionClass($this->className);
            $this->tableName = $classRef->getConstant('TABLE_NAME');
        }

        return $this->tableName;
    }

    public function setClassName($className)
    {
        if (!empty($className) && $this->className != $className) {
            $this->className = $className;
            $this->tableName = null;
        }
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function getLibName()
    {
        return "`{$this->getDBName()}`.`{$this->getTableName()}`";
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function setPdo($pdo)
    {
        $this->pdo = $pdo;
    }

    public function add($entity, $fields, $onDuplicate = null)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);

        $query = "INSERT INTO {$this->getLibName()} ({$strFields}) VALUES ({$strValues})";

        if (!empty($onDuplicate)) {
            $query .= 'ON DUPLICATE KEY UPDATE ' . $onDuplicate;
        }

        $statement = $this->pdo->prepare($query);
        $params = array();

        foreach ($fields as $field) {
            $params[$field] = $entity->$field;
        }

        $statement->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function addMulti($entitys, $fields)
    {
        $items = array();
        $params = array();

        foreach ($entitys as $index => $entity) {
            $items[] = '(:' . implode($index . ', :', $fields) . $index . ')';

            foreach ($fields as $field) {
                $params[$field . $index] = $entity->$field;
            }
        }

        $query = "INSERT INTO {$this->getLibName()} (`" . implode('`,`', $fields) . "`) VALUES " . implode(',', $items);
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

    public function replace($entity, $fields)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);

        $query = "REPLACE INTO {$this->getLibName()} ({$strFields}) VALUES ({$strValues})";
        $statement = $this->pdo->prepare($query);
        $params = array();

        foreach ($fields as $field) {
            $params[$field] = $entity->$field;
        }
        $statement->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function update($fields, $params, $where, $change = false)
    {
        if ($change) {
            $updateFields = array_map(__CLASS__ . '::changeFieldMap', $fields);
        } else {
            $updateFields = array_map(__CLASS__ . '::updateFieldMap', $fields);
        }

        $strUpdateFields = implode(',', $updateFields);
        $query = "UPDATE {$this->getLibName()} SET {$strUpdateFields} WHERE {$where}";
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

    public function fetchValue($where = '1', $params = null, $fields = '*')
    {
        $query = "SELECT {$fields} FROM {$this->getLibName()} WHERE {$where} limit 1";
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        return $statement->fetchColumn();
    }

    public function fetchArray($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $query = "SELECT {$fields} FROM {$this->getLibName()} WHERE {$where}";

        if ($orderBy) {
            $query .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $query .= " limit {$limit}";
        }

        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $statement->setFetchMode(\PDO::FETCH_ASSOC);
        return $statement->fetchAll();
    }

    public function fetchCol($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $results = $this->fetchArray($where, $params, $fields, $orderBy, $limit);
        return empty($results) ? array() : array_map('reset', $results);
    }

    public function fetchAll($where = '1', $params = null, $fields = '*', $orderBy = null, $limit = null)
    {
        $query = "SELECT {$fields} FROM {$this->getLibName()} WHERE {$where}";

        if ($orderBy) {
            $query .= " order by {$orderBy}";
        }

        if ($limit) {
            $query .= " limit {$limit}";
        }
        $statement = $this->pdo->prepare($query);

        if (!$statement->execute($params)) {
            throw new \Exception('data base error');
        }

        $statement->setFetchMode(\PDO::FETCH_CLASS, $this->className);
        return $statement->fetchAll();
    }

    public function fetchEntity($where = '1', $params = null, $fields = '*', $orderBy = null)
    {
        $query = "SELECT {$fields} FROM {$this->getLibName()} WHERE {$where}";

        if ($orderBy) {
            $query .= " order by {$orderBy}";
        }

        $query .= " limit 1";
        $statement = $this->pdo->prepare($query);
        $statement->execute($params);
        $statement->setFetchMode(\PDO::FETCH_CLASS, $this->className);
        return $statement->fetch();
    }

    public function fetchCount($where = '1', $pk = "*")
    {
        $query = "SELECT count({$pk}) as count FROM {$this->getLibName()} WHERE {$where}";
        $statement = $this->pdo->prepare($query);
        $statement->execute();
        $result = $statement->fetch();
        return $result["count"];
    }
	//$params = [] php5.3.6 报语法错误 change by ahuo 2013-11-05 14:23
    public function remove($where, $params = array())
    {
        if (empty($where)) {
            return false;
        }

        $query = "DELETE FROM {$this->getLibName()} WHERE {$where}";
        $statement = $this->pdo->prepare($query);
        return $statement->execute($params);
    }

    public function flush()
    {
        $query = "TRUNCATE {$this->getLibName()}";
        $statement = $this->pdo->prepare($query);
        return $statement->execute();
    }

    public static function updateFieldMap($field)
    {
        return '`' . $field . '`=:' . $field;
    }

    public static function changeFieldMap($field)
    {
        return '`' . $field . '`=`' . $field . '`+:' . $field;
    }

    public function fetchBySql($sql)
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute();
        return $statement->fetch();
    }


    public function ping()
    {
        if(empty($this->pdo)) {
            $this->pdo = $this->connect();
        } else {
            try {
                $status = $this->pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
            } catch (\Exception $e) {
                if ($e->getCode() == 'HY000') {
                    $this->pdo = $this->connect();
                } else {
                    throw $e;
                }
            }
        }
        return $this->pdo;
    }

    public function close()
    {
        if(empty($this->config['pconnect'])) {
            $this->pdo = null;
        }
    }
}
