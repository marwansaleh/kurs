<?php if (!defined('APP_PATH')) exit('No direct script access allowed'); 

/**
 * Description of database
 *
 * @author Marwan Saleh <amazzura.biz@gamil.com>
 */
class Database {
    private static $objInstance; //keep obejct instance
    
    protected $connection = null;
    protected $host = '';
    protected $port = '';
    protected $db_name = '';
    protected $db_user = '';
    protected $db_pwd = '';
    
    //result
    protected $last_num_rows = 0;
    protected $last_affected_rows = 0;
    protected $last_inserted_id = 0;
    
    private $_thread_id = 0;
            
    private function __construct($db, $user, $pwd, $host='localhost', $port=3306) {
        $this->host = $host;
        $this->port = $port;
        $this->db_name = $db;
        $this->db_user = $user;
        $this->db_pwd = $pwd;
        
        //create connection
        $this->connection = new mysqli($this->host, $this->db_user, $this->db_pwd, $this->db_name, $this->port);
        
        if ($this->connection->connect_errno) {
            exit ("Failed to connect to MySQL: (" . $this->connection->connect_errno . ") " . $this->connection->connect_error);
        }
        
        $this->_thread_id = $this->connection->thread_id;
    }
    
    function __destruct() {
        if ($this->connection){
            $this->connection->close();
        }
    }
    
    /**
     * Get object instance of Database
     * @return object instance
     */
    public static function getInstance( $db, $user, $pwd, $host='localhost', $port=3306 ) { 
            
        if(!self::$objInstance){ 
            self::$objInstance = new Database($db, $user, $pwd, $host, $port);
        } 
        
        return self::$objInstance; 
    }
    
    function close(){  $this->connection->close(); }
    function get_mysql_thread_id(){ return $this->_thread_id; }
    
    function get($sql, $as_object = TRUE){
        $result = $this->connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
            $this->last_num_rows = $result->num_rows;
            
            $items = array();
            if ($as_object){
                while($row = $result->fetch_object()){
                    $items [] = $row;
                }
            }else{
                while($row = $result->fetch_assoc()){
                    $items [] = $row;
                }
            }
            
            //free memory
            $result->close();
            
            
            return $items;
        }
        
        return FALSE;
    }
    
    function get_where($table, $field='*', $where=NULL, $single=FALSE){
        $sql = 'SELECT '.$field.' FROM '.$table;
        if ($where){
            if (is_array($where)){
                $where_arr = array();
                foreach($where as $name=>$value){
                    $where_arr [] = '('. $name . '=\''. $this->connection->real_escape_string($value).'\')';
                }
                $where = implode('AND', $where_arr);
            }
            $sql.=' WHERE '.$where;
        }
        
        $result = $this->connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
            $this->last_num_rows = $result->num_rows;
            
            if ($single){
                return $result->fetch_object();
            }
            
            $items = array();
            while($row = $result->fetch_object()){
                $items [] = $row;
            }
            
            //free memory
            $result->close();
            
            
            return $items;
        }
        
        return FALSE;
    }
    
    function get_single_value($table, $field_name, $where=NULL, $def_value=FALSE){
        $sql = 'SELECT '.$field_name.' FROM '.$table;
        if ($where){
            if (is_array($where)){
                $where_arr = array();
                foreach($where as $name=>$value){
                    $where_arr [] = '('. $name . '=\''. $this->connection->real_escape_string($value).'\')';
                }
                $where = implode('AND', $where_arr);
            }
            $sql.=' WHERE '.$where;
        }
        
        $result = $this->connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
            $this->last_num_rows = $result->num_rows;
            
            $row = $result->fetch_object();
            
            //free memory
            $result->close();
            
            return $row->$field_name;
        }
        
        return $def_value;
    }
    
    function get_single_row($sql, $as_object=TRUE){
        $result = $this->connection->query($sql, MYSQLI_USE_RESULT);
        if ($result){
            $this->last_num_rows = $result->num_rows;
            
            if ($as_object){
                $row = $result->fetch_object();
            }else{
                $row = $result->fetch_assoc();
            }
            //free memory
            $result->close();
            
            return $row;
        }
        
        return FALSE;
    }
    
    function get_count($table_name, $where=NULL){
        $sql = 'SELECT COUNT(*) as total FROM '.$table_name;
        if ($where){
            if (is_array($where)){
                $where_arr = array();
                foreach($where as $name=>$value){
                    $where_arr [] = '('. $name . '=\''. $this->connection->real_escape_string($value).'\')';
                }
                $where = implode('AND', $where_arr);
            }
            $sql.=' WHERE '.$where;
        }
        
        $result = $this->connection->query($sql, MYSQLI_USE_RESULT);
        if ($result){
            $this->last_num_rows = $result->num_rows;
            
            $row = $result->fetch_object();
            //free memory
            $result->close();
            
            return $row->total;
        }
        
        return FALSE;
    }
    
    function insert (&$data, $table)
    {
        $cols = implode(',', array_keys($data));
        foreach (array_values($data) as $value)
        {
            isset($vals) ? $vals .= ',' : $vals = '';
            if (is_string($value)) {
                $vals .= '\''.$this->connection->real_escape_string($value).'\'';
            } else if (is_numeric($value)){
                $vals .= $value;
            }
        }
        $result = $this->connection->real_query('INSERT INTO '.$table.' ('.$cols.') VALUES ('.$vals.')');
        if ($result){
            $this->last_inserted_id = $this->connection->insert_id;
            return $this->last_inserted_id;
        }
        
        return $result;
    }
    
    function update($table, &$data, $condition){
        $result = FALSE;
        
        $sql = array();
        foreach ($data as $key => $value)
        {
            $sql [] = $key . '=\''. $this->connection->real_escape_string($value).'\'';
        }
        if (count($sql)){
            if (is_array($condition)){
                $where_arr = array();
                foreach($condition as $name=>$value){
                    $where_arr [] = '('. $name . '=\''. $this->connection->real_escape_string($value).'\')';
                }
                $condition = ' WHERE '. implode('AND', $where_arr);
            }
            $result = $this->connection->real_query("UPDATE $table SET ". implode(',', $sql).' '. $condition);
            
            if ($result){
                $this->last_affected_rows = $this->connection->affected_rows;
            }
        }
        
        return $result;
    }
    
    function delete($table, array $condition=NULL){
        
        $sql = 'DELETE FROM '. $table;
        
        if ($condition){
            $sql.= ' WHERE ' ;
            
            $condition_list = array();
            foreach ($condition as $key=>$value){
                $condition_list [] = $key .'=' . $this->connection->real_escape_string($value);
            }
            
            $sql .= implode(' AND ', $condition_list);
        }
        
        $result = $this->connection->query($sql);
        
        if ($result){
            $this->last_affected_rows = $this->connection->affected_rows;
        }
        
        return $result;
    }
    
    function simple_query($sql){
        $result = $this->connection->query($sql);
        if ($result){
            $this->last_affected_rows = $this->connection->affected_rows;
        }
        return $result;
    }
    function num_rows(){ return $this->last_num_rows; }
    function affected_rows(){ return $this->last_affected_rows; }
    function inserted_id(){ return $this->last_inserted_id; }
    function last_error() { return $this->connection->error;}
    function real_escape($value){
        return $this->connection->real_escape_string($value);
    }
}

