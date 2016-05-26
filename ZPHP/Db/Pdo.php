<?php
/**
 * User: shenzhe
 * Date: 13-6-17
 */


namespace ZPHP\Db;
use ZPHP\Protocol\Request;
use ZPHP\Core\Config as ZConfig;

class Pdo
{
    /**
     * @var \PDO
     */
    private $pdo;
    private $dbName;
    private $tableName;
    private $className;
    private $config;
    private $lastTime;
    private $lastSql;
    /**
     * @var Pdo
     */
    private static $instance = [];

    /**
     * @param null $config
     * @param null $className
     * @param null $dbName
     * @return Pdo
     * @throws \Exception
     */
    public static function getInstance($config=null, $className=null, $dbName=null)
    {
        if(empty($config)) {
            $config = ZConfig::get('pdo');
        }
        if(empty($config['dsn'])) {
            throw new \Exception('dsn empty');
        }
        if(empty(self::$instance[$config['dsn']])) {
            self::$instance[$config['dsn']] = new Pdo($config);
        } else if(Request::isLongServer()){
            self::$instance[$config['dsn']]->ping();
        }

        if($className) {
            self::$instance[$config['dsn']]->setClassName($className);
        }

        if($dbName) {
            self::$instance[$config['dsn']]->setDBName($dbName);
        }


        return self::$instance[$config['dsn']];
    }


    /**
     * @param $config
     * @param null $className
     * entityDemo
     * <?php
     *    假设数据库有user表,表含有id(自增主键), username, password三个字段
     *    class UserEntity {
     *         const TABLE_NAME = 'user';  //对应的数据表名
     *         const PK_ID = 'id';         //主键id名
     *         public $id;                 //public属性与表字段一一对应
     *         public $username;
     *         public $password;
     *    }
     * @param null $dbName
     */
    public function __construct($config=null, $className = null, $dbName = null)
    {
        if(empty($config)) {
            $config = ZConfig::get('pdo');
        }
        if(empty($config)) {
            throw new \Exception('config empty', -1);
        }
        $this->config = $config;
        if(empty($this->config['pingtime'])) {
            $this->config['pingtime'] = 3600;
        }
        if (!empty($className)) {
            $this->className = $className;
        }
        if (empty($dbName)) {
            $this->dbName = $config['dbname'];
        } else {
            $this->dbName = $dbName;
        }
        $this->lastTime = time() + $this->config['pingtime'];
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
        if(Request::isLongServer()) {
            $persistent = 0;
        } else {
            $persistent = empty($this->config['pconnect']) ? 0 : 1;
        }
        return new \PDO($this->config['dsn'], $this->config['user'], $this->config['pass'], array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$this->config['charset']}';",
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_PERSISTENT => $persistent
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
            if(method_exists($this->className, 'getTableName')) {
                $this->tableName = call_user_func(array($this->className, 'getTableName'));
            } else {
                $entityRef = new \ReflectionClass($this->className);
                $this->tableName = $entityRef->getConstant('TABLE_NAME');
            }
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
        $_fields = array_map('md5', $fields);
        $strValues = ':' . implode(', :', $_fields);

        $query = "INSERT INTO {$this->getLibName()} ({$strFields}) VALUES ({$strValues})";

        if (!empty($onDuplicate)) {
            $query .= 'ON DUPLICATE KEY UPDATE ' . $onDuplicate;
        }

        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
        $params = array();

        foreach ($_fields as $_i=>$field) {
            $params[$field] = $entity->{$fields[$_i]};
        }

        $statement->execute($params);
        return $this->pdo->lastInsertId();
    }

    public function addMulti($entitys, $fields)
    {
        $items = array();
        $params = array();

        $_fileds = array_map('md5', $fields);

        foreach ($entitys as $index => $entity) {
            $items[] = '(:' . implode($index . ', :', $_fileds) . $index . ')';

            foreach ($_fileds as $_i=>$field) {
                $params[$field . $index] = $entity->{$fields[$_i]};
            }
        }

        $query = "INSERT INTO {$this->getLibName()} (`" . implode('`,`', $fields) . "`) VALUES " . implode(',', $items);
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
        $statement->execute($params);
        return $statement->rowCount();
    }

    public function replace($entity, $fields)
    {
        $strFields = '`' . implode('`,`', $fields) . '`';
        $strValues = ':' . implode(', :', $fields);

        $query = "REPLACE INTO {$this->getLibName()} ({$strFields}) VALUES ({$strValues})";
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
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
        $this->lastSql = $query;
        $statement->execute($params);
        return $statement->rowCount();
    }

    public function fetchValue($where = '1', $params = null, $fields = '*')
    {
        $query = "SELECT {$fields} FROM {$this->getLibName()} WHERE {$where} limit 1";
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
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
        $this->lastSql = $query;
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
        $this->lastSql = $query;

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
        $this->lastSql = $query;
        $statement->execute($params);
        $statement->setFetchMode(\PDO::FETCH_CLASS, $this->className);
        return $statement->fetch();
    }

    public function fetchCount($where = '1', $pk = "*")
    {
        $query = "SELECT count({$pk}) as count FROM {$this->getLibName()} WHERE {$where}";
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
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
        $statement->execute($params);
        $this->lastSql = $query;
        return $statement->rowCount();
    }

    public function flush()
    {
        $query = "TRUNCATE {$this->getLibName()}";
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
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

    public function fetchBySql($sql, $mode=\PDO::FETCH_ASSOC)
    {
        $statement = $this->pdo->prepare($sql);
        $this->lastSql = $sql;
        $statement->execute();
        $statement->setFetchMode($mode);
        return $statement->fetchAll();
    }

    public function queryBySql($query)
    {
        $statement = $this->pdo->prepare($query);
        $this->lastSql = $query;
        $statement->execute();
        return $statement->rowCount();
    }


    public function ping()
    {
        $now = time();
        if($this->lastTime < $now) {
            if (empty($this->pdo)) {
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
        }
        $this->lastTime = $now + $this->config['pingtime'];
        return $this->pdo;
    }

    public function close()
    {
        if(empty($this->config['pconnect'])) {
            $this->pdo = null;
        }
    }

    public function getLastSql()
    {
        return $this->lastSql;
    }
}
