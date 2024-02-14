<?php
use PDO;
use Exception;

/**
 * Wrapper for PDO
 */
class Database
{
    /**
     * hold database connection
     */
    protected $db;

    private $debug;

    /**
     * Array of connection arguments
     * 
     * @param array $args
     */
    
    public function debug($debug){
      $this->debug=$debug;
  }
    
    public function log( $msg ){
        echo '<script>';
        echo 'console.log("🛑' . $msg . '")';
        echo '</script>';
    }

    public function __construct($args)
    {
        if (!isset($args['database'])) {
            throw new Exception('&args[\'database\'] is required');
        }

        if (!isset($args['username'])) {
            throw new Exception('&args[\'username\']  is required');
        }

        $type     = isset($args['type']) ? $args['type'] : 'mysql';
        $host     = isset($args['host']) ? $args['host'] : 'localhost';
        $charset  = isset($args['charset']) ? $args['charset'] : 'utf8';
        $port     = isset($args['port']) ? 'port=' . $args['port'] . ';' : '';
        $password = isset($args['password']) ? $args['password'] : '';
        $database = $args['database'];
        $username = $args['username'];

        $this->db = new PDO("$type:host=$host;$port" . "dbname=$database;charset=$charset", $username, $password);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * get PDO instance
     * 
     * @return $db PDO instance
     */
    public function getPdo()
    {
        return $this->db;
    }

    /**
     * Run raw sql query 
     * 
     * @param  string $sql       sql query
     * @return void
     */
    public function raw($sql)
    {
      $this->db->query($sql);
    }

    /**
     * Run sql query
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns a PDO object
     */
    public function run(string $sql, $args = [])
    {
      $params=[];
      try {
        if (empty($args)) {
            return $this->db->query($sql);
        }

        if (strpos($sql, 'WHERE 1=1')) {
          foreach ($args as $k=>$v) {
            if (is_array($v)) {
              if (strpos($k, ' IN')) 
              {
                $in = '(' . str_repeat('?,', count($v) - 1) . '?)';
                $k = str_replace('(?)', $in, $k);
              }
              $params=array_merge($params, $v);
            }
            else {
              $params[]=$v;          
            }
            if (strpos($k, '?'))
              $parts[]=$k;
            else
              $parts[]=$k.' = ?';
          }
          $args=$params;
          $where=implode(' AND ', $parts);
          $sql=str_replace('1=1', $where, $sql);          
        }
        else {
          if (!is_array($args)) $args=(array)$args;
        }

        if ($this->debug) {
          $debugsql=$sql;
          $indexed=$args==array_values($args);
          foreach($args as $k=>$v) {
            //if(is_string($v)) $v="'$v'";
            if($indexed) $debugsql=preg_replace('/\?/',$v,$debugsql,1);
            else $debugsql=str_replace(":$k",$v,$debugsql);
          }
          if ($this->debug == 'log') $this->log($debugsql); 
          else echo '<pre>'.$debugsql.'</pre>'; 
        }   

        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);  
        return $stmt;
      }    
      catch (PDOException $e) {
        if ($this->debug) $this->log('Ошибка при обращении к БД: ' . $e->getMessage() .'\n'. $sql);
      }     
    }   

    /**
     * Get array of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */
    public function rows(string $sql, $args = [])
    {
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_OBJ);
    } 

    /**
     * Get array of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */    
    public function rowsArray(string $sql, $args = [])
    {
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_ASSOC);
    } 

    /**
     * Get arrays of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return string            returns JSON string
     */
    public function rowsJSON(string $sql, $args = [])
    {
      return json_encode($this->run($sql, $args)->fetchAll(PDO::FETCH_OBJ), JSON_UNESCAPED_UNICODE);
    } 

    /**
     * Get array of records indexed by the first field
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */  
    public function rowsList(string $sql, $args = [])
    {
      $args = (array)$args;
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_UNIQUE);
    }

    /**
     * Get nested array of records grouped by first field
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */  
    public function rowsGroup(string $sql, $args = [])
    {
      $args = (array)$args;
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_GROUP);
    }

    /**
     * Get array of second field values indexed by the first field
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */  
    public function rowsColumnList(string $sql, $args = [])
    {
      $args = (array)$args;
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_KEY_PAIR);
    }


    /**
     * Get array of field values
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @return object            returns multiple records
     */      
    public function rowsColumn(string $sql, $args = [])
    {
      $args = (array)$args;
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_COLUMN);
    }    

    public function cell($sql, $args = [])
    {
      $data = array();
      $args = (array)$args;
      return $this->run($sql, $args)->fetchColumn();
    }
    
    public function array($sql, $args = [])
    {
      $data = array();
      $args = (array)$args;
      return $this->run($sql, $args)->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);
    }

 
    /**
     * Get record
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  object $fetchMode set return mode ie object or array
     * @return object            returns single record
     */
    public function row(string $sql, $args = [])
    {
      return $this->run($sql, $args)->fetch(PDO::FETCH_OBJ);
    }


    /**
     * Get record as JSON string
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  object $fetchMode set return mode ie object or array
     * @return string            returns single record as JSON string
     */
    public function rowJSON(string $sql, $args = [])
    {
      return json_encode($this->run($sql, $args)->fetch(PDO::FETCH_OBJ), JSON_UNESCAPED_UNICODE);
    } 

    /**
     * Get record by id
     * 
     * @param  string $table     name of table
     * @param  integer $id       id of record
     * @param  object $fetchMode set return mode ie object or array
     * @return object            returns single record
     */
    public function record(string $table, $id)
    {
      return $this->run("SELECT * FROM $table WHERE id = ?", [$id])->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Get number of records
     * 
     * @param  string $sql       sql query
     * @param  array  $args      params
     * @param  object $fetchMode set return mode ie object or array
     * @return integer           returns number of records
     */
    public function count($sql, $args = [])
    {
      return $this->run($sql, $args)->rowCount();
    }


    public function select($table, $args = [])
    {       
        foreach ($args as $k=>$v) {
            $wheres[]= "$k = :$k";
        }
        $where = implode(' AND ', $wheres);
        return $this->run("SELECT * FROM $table WHERE $where", $args);
    } 
 
    /**
     * Get primary key of last inserted record
     */
    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }

    /**
     * insert record
     * 
     * @param  string $table table name
     * @param  array $data  array of columns and values
     */
    public function insert(string $table, $data)
    {
        //add columns into comma seperated string
        $columns = implode(',', array_keys($data));

        //get values
        $values = array_values($data);

        $placeholders = array_map(function ($val) {
            return '?';
        }, array_keys($data));

        //convert array into comma seperated string
        $placeholders = implode(',', array_values($placeholders));

     
        $this->run("INSERT INTO $table ($columns) VALUES ($placeholders)", $values);
    
        return $this->lastInsertId();
    }

    /**
     * update record
     * 
     * @param  string $table table name
     * @param  array $data  array of columns and values
     * @param  array $where array of columns and values OR id of updated record
     */
    public function update(string $table, $data, $where)
    {        
      if (is_int($where)) {
        $wheres['id'] = $where;
      }
      else {
        $wheres = $where;
      }
      //merge data and where together
      $collection = array_merge($data, $where);

      //collect the values from collection
      $values = array_values($collection);

      //setup fields
      $fieldDetails = null;
      foreach ($data as $key => $value) {
          $fieldDetails .= "$key = ?,";
      }
      $fieldDetails = rtrim($fieldDetails, ',');

      //setup where 
      $whereDetails = null;
      $i = 0;
      foreach ($where as $key => $value) {
          $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
          $i++;
      }          

      $stmt = $this->run("UPDATE $table SET $fieldDetails WHERE $whereDetails", $values);

      return $stmt->rowCount();
    }

    /**
     * Delete records
     * 
     * @param  string $table table name
     * @param  array $where array of columns and values
     * @param  integer $limit limit number of records
     */
    public function delete(string $table, $where, $limit = 1)
    {
        if (is_int($where)) {
          $wheres['id'] = $where;
        }
        else {
          $wheres = $where;
        }        
        //collect the values from collection
        $values = array_values($wheres);

        //setup where 
        $whereDetails = null;
        $i = 0;
        foreach ($where as $key => $value) {
            $whereDetails .= $i == 0 ? "$key = ?" : " AND $key = ?";
            $i++;
        }

        //if limit is a number use a limit on the query
        if (is_numeric($limit)) {
            $limit = "LIMIT $limit";
        }

        $stmt = $this->run("DELETE FROM $table WHERE $whereDetails $limit", $values);

        return $stmt->rowCount();
    }

    /**
     * Delete all records records
     * 
     * @param  string $table table name
     */
    public function deleteAll($table)
    {
        $stmt = $this->run("DELETE FROM $table");

        return $stmt->rowCount();
    }

    /**
     * Delete record by id
     * 
     * @param  string $table table name
     * @param  integer $id id of record
     */
    public function deleteById($table, $id)
    {
        $stmt = $this->run("DELETE FROM $table WHERE id = ?", [$id]);

        return $stmt->rowCount();
    }

    /**
     * Delete record by ids
     * 
     * @param  string $table table name
     * @param  string $column name of column
     * @param  string $ids ids of records
     */
    public function deleteByIds(string $table, string $column, string $ids)
    {
        $stmt = $this->run("DELETE FROM $table WHERE $column IN ($ids)");

        return $stmt->rowCount();
    }

    /**
     * truncate table
     * 
     * @param  string $table table name
     */
    public function truncate($table)
    {
        $stmt = $this->run("TRUNCATE TABLE $table");

        return $stmt->rowCount();
    }
}
?>