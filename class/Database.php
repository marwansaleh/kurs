<?php if (!defined('APP_PATH')) exit('No direct script access allowed'); 

class MYSQLI_DB implements IDatabase {
    private $_connection;
    
    public function type(){
        return 'MYSQLI';
    }
    public function connect($host,$db_name,$db_user,$db_pwd){
        $this->_connection = new mysqli($host, $db_user, $db_pwd, $db_name);
        if ($this->_connection->connect_errno) {
            trigger_error ("Failed to connect to MySQLi: (" . $this->_connection->connect_errno . ") " . $this->_connection->connect_error, E_USER_ERROR);
        }
    }
    public function close(){
        if ($this->_connection){
            $this->_connection->close();
        }
        $this->_connection = NULL;
    }
    public function query($sql){
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
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
    public function get($tablename,$offset=NULL,$limit=NULL){
        $sql = "SELECT * FROM $tablename";
        if ($limit){
            if ($offset){
                $sql .= " LIMIT $offset,$limit";
            }else{
                $sql .= " LIMIT $limit";
            }
        }
        
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
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
    public function get_by($tablename,$condition,$field='*'){
        $sql = 'SELECT '.$field.' FROM '.$tablename;
        if (is_array($condition)){
            $where_arr = array();
            foreach($condition as $name=>$value){
                $where_arr [] = '('. $name . '=\''. $this->_connection->real_escape_string($value).'\')';
            }
            $condition = implode('AND', $where_arr);
        }
        $sql.=' WHERE '.$condition;
        
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        
        if ($result){
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
    public function get_count($tablename,$condition=NULL){
        $sql = "SELECT COUNT(*) AS total_records FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach($condition as $key=>$val){
                $condition_array [] = $key .'=\''. $this->_connection->real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '.implode(' AND ', $condition_array);
        }
        
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        if ($result){
            $row = $result->fetch_object();
            //free memory
            $result->close();
            return $row->total_records;
        }
        return 0;
    }
    public function get_row($tablename,$condition=NULL){
        $sql = "SELECT * FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach($condition as $key=>$val){
                $condition_array [] = $key .'=\''. $this->_connection->real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '.implode(' AND ', $condition_array);
        }
        $sql .= ' LIMIT 1';
        
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        if ($result){
            $row = $result->fetch_object();
            //free memory
            $result->close();
            return $row;
        }
        return NULL;
    }
    public function get_value($tablename,$condition,$field){
        $sql = "SELECT $field FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach($condition as $key=>$val){
                $condition_array [] = $key .'=\''. $this->_connection->real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '.implode(' AND ', $condition_array);
        }
        $sql .= ' LIMIT 1';
        
        $result = $this->_connection->query($sql, MYSQLI_USE_RESULT);
        if ($result){
            $row = $result->fetch_object();
            //free memory
            $result->close();
            return $row->$field;
        }
        return NULL;
    }
    public function insert($tablename,&$data){
        $cols = implode(',', array_keys($data));
        foreach (array_values($data) as $value)
        {
            isset($vals) ? $vals .= ',' : $vals = '';
            if (is_string($value)) {
                $vals .= '\''.$this->_connection->real_escape_string($value).'\'';
            } else if (is_numeric($value)){
                $vals .= $value;
            }
        }
        $result = $this->_connection->real_query('INSERT INTO '.$tablename.' ('.$cols.') VALUES ('.$vals.')');
        if ($result){
            return $this->_connection->insert_id;
        }
        
        return FALSE;
    }
    public function update($tablename, &$data, $condition=NULL){
        $sql = array();
        foreach ($data as $key => $value)
        {
            $sql [] = $key . '=\''. $this->_connection->real_escape_string($value).'\'';
        }
        if (count($sql)){
            if ($condition && is_array($condition)){
                $where_arr = array();
                foreach($condition as $name=>$value){
                    $where_arr [] = '('. $name . '=\''. $this->_connection->real_escape_string($value).'\')';
                }
                $condition = ' WHERE '. implode('AND', $where_arr);
            }
            $result = $this->_connection->real_query("UPDATE $tablename SET ". implode(',', $sql).' '. $condition);
            
            if ($result){
                return $this->_connection->affected_rows;
            }
        }
        
        return FALSE;
    }
    public function delete($tablename, $condition){
        $sql = 'DELETE FROM '. $tablename;
        
        if ($condition && is_array($condition)){
            $sql.= ' WHERE ' ;
            
            $condition_list = array();
            foreach ($condition as $key=>$value){
                $condition_list [] = $key .'=' . $this->_connection->real_escape_string($value);
            }
            
            $sql .= implode(' AND ', $condition_list);
        }
        
        $result = $this->_connection->query($sql);
        
        if ($result){
            return $this->_connection->affected_rows;
        }
        
        return FALSE;
    }
}

class PDO_DB implements IDatabase {
    private $_connection;
    
    public function type(){
        return 'PDO';
    }
    public function connect($host,$db_name,$db_user,$db_pwd){
        try {
            $this->_connection = new PDO('mysql:host='.$host.';dbname='.$db_name, $db_user, $db_pwd);
        } catch (PDOException $e) {
            trigger_error("Failed to connect to MySQL PDO: " . $e->getMessage(), E_USER_ERROR);
        }
    }
    public function close(){
        $this->_connection = NULL;
    }
    public function query($sql){
        $result = $this->_connection->query($sql);
        if ($result){
            $items = array();
            while ($item = $result->fetch(PDO::FETCH_OBJ)){
                $item [] = $item;
            }
            
            return $items;
        }
        
        return FALSE;
    }
    public function get($tablename,$offset=NULL,$limit=NULL){
        
        $sql = 'SELECT * FROM '.$tablename;
        if ($limit){
            if (!$offset){
                $offset = 0;
            }
            $sql .= ' LIMIT ?,?';
        }
        
        $result = $this->_connection->prepare($sql);
        if ($limit){
            $result->bindParam(1, $offset, PDO::PARAM_INT);
            $result->bindParam(2, $limit, PDO::PARAM_INT);
        }
        if (!$result->execute()){
            return FALSE;
        }
        
        $items = array();
        while ($item = $result->fetch(PDO::FETCH_OBJ)){
            $items [] = $item;
        }
        
        return $items;
    }
    public function get_by($tablename,$condition,$fields='*'){
        $sql = 'SELECT '.$fields.' FROM '.$tablename.' WHERE ';
        
        $condition_arr = array();
        $param_values = array();
        foreach ($condition as $key => $val){
            $condition_arr [] = $key.'=?';
            $param_values[] = $val;
        }
        $sql .= implode(' AND ', $condition_arr);
        
        $result = $this->_connection->prepare($sql);
        if (!$result->execute($param_values)){
            return FALSE;
        }
        
        $items = array();
        while ($item = $result->fetch(PDO::FETCH_OBJ)){
            $items [] = $item;
        }
        
        return $items;
    }
    public function get_count($tablename,$condition=NULL){
        $sql = "SELECT COUNT(*) AS total_records FROM $tablename";
        $bind_params = array();
        
        if ($condition && is_array($condition)){
            $condition_keys = array();
            foreach (array_keys($condition) as $key){
                $condition_keys [] = $key . '=?';
            }
            $sql .= ' WHERE ' . implode(' AND ', $condition_keys);
            $bind_params = array_values($condition);
        }
        
        $result = $this->_connection->prepare($sql);
        count($bind_params)==0 ? $result->execute() : $result->execute($bind_params);
        $row = $result->fetch(PDO::FETCH_OBJ);
        if ($row){
            return $row->total_records;
        }else{
            return 0;
        }
    }
    public function get_row($tablename,$condition=NULL){
        $sql = "SELECT * FROM $tablename";
        $bind_params = array();
        
        if ($condition && is_array($condition)){
            $condition_keys = array();
            foreach (array_keys($condition) as $key){
                $condition_keys [] = $key . '=?';
            }
            $sql .= ' WHERE ' . implode(' AND ', $condition_keys);
            $bind_params = array_values($condition);
        }
        $sql.= ' LIMIT 1';
        
        $result = $this->_connection->prepare($sql);
        count($bind_params)==0 ? $result->execute() : $result->execute($bind_params);
        $row = $result->fetch(PDO::FETCH_OBJ);
        if ($row){
            return $row;
        }else{
            return NULL;
        }
    }
    public function get_value($tablename,$condition,$field){
        $sql = "SELECT $field FROM $tablename";
        $bind_params = array();
        
        if ($condition && is_array($condition)){
            $condition_keys = array();
            foreach (array_keys($condition) as $key){
                $condition_keys [] = $key . '=?';
            }
            $sql .= ' WHERE ' . implode(' AND ', $condition_keys);
            $bind_params = array_values($condition);
        }
        $sql.= ' LIMIT 1';
        
        $result = $this->_connection->prepare($sql);
        count($bind_params)==0 ? $result->execute() : $result->execute($bind_params);
        $row = $result->fetch(PDO::FETCH_OBJ);
        if ($row){
            return $row->$field;
        }else{
            return NULL;
        }
    }
    public function insert($tablename,&$data){
        $sql = 'INSERT INTO '.$tablename.' (' . implode(',', array_keys($data)) .') VALUES (';
        
        $value_var = array();
        for ($i=0; $i<count($data);$i++){
            $value_var [] = '?';
        }
        
        $sql .= implode(',', $value_var) . ')';
        
        $result = $this->_connection->prepare($sql);
        
        if (!$result->execute(array_values($data))){
            return FALSE;
        }
        
        return $this->_connection->lastInsertId();
    }
    public function update($tablename, &$data, $condition=NULL){
        $bind_params = array_values($data);
        
        $sql = 'UPDATE '.$tablename.' SET ';
        
        $data_keys = array();
        foreach (array_keys($data) as $key){
            $data_keys [] = $key .'=?';
        }
        $sql .= implode(', ', $data_keys);
        
        if ($condition && is_array($condition)){
            $condition_keys = array();
            foreach (array_keys($condition) as $key){
                $condition_keys [] = $key . '=?';
            }
            $sql .= ' WHERE ' . implode(' AND ', $condition_keys);
            $bind_params = array_merge($bind_params, array_values($condition));
        }
        
        
        $result = $this->_connection->prepare($sql);
        
        if (!$result->execute($bind_params)){
            return FALSE;
        }
        
        return $result->rowCount();
    }
    public function delete($tablename, $condition){
        $sql = "DELETE FROM $tablename ";
        
        $bind_params = array_values($condition);
        
        $condition_keys = array();
        foreach (array_keys($condition) as $key){
            $condition_keys [] = $key . '=?';
        }
        
        $sql .= 'WHERE '. implode(' AND ', $condition_keys);
        
        $result = $this->_connection->prepare($sql);
        
        if (!$result->execute($bind_params)){
            return FALSE;
        }
        
        return $result->rowCount();
    }
}

class MYSQL_DB implements IDatabase {
    private $_connection;
    public function type(){
        return 'MYSQL';
    }
    public function connect($host,$db_name,$db_user,$db_pwd){
        $this->_connection = mysql_connect($host, $db_user, $db_pwd);
        if (!$this->_connection){
            trigger_error('Failed to connect to create database connection', E_USER_ERROR);
        }else{
            mysql_select_db($db_name, $this->_connection);
        }
    }
    public function close(){
        if ($this->_connection){
            mysql_close($this->_connection);
        }
    }
    public function query($sql){
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $items = array();
        while ($item = mysql_fetch_object($result)){
            $items [] = $item;
        }
        mysql_free_result($result);
        
        return $items;
    }
    public function get($tablename,$offset=NULL,$limit=NULL){
        $sql = "SELECT * FROM $tablename";
        if ($limit){
            if (!$offset){
                $offset = 0;
            }
            $sql .= " LIMIT $offset,$limit";
        }
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $items = array();
        while ($item = mysql_fetch_object($result)){
            $items [] = $item;
        }
        mysql_free_result($result);
        
        return $items;
    }
    public function get_by($tablename,$condition,$field='*'){
        $sql = "SELECT $field FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key .'=\''.mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $items = array();
        while ($item = mysql_fetch_object($result)){
            $items [] = $item;
        }
        mysql_free_result($result);
        
        return $items;
    }
    public function get_count($tablename,$condition=NULL){
        $sql = "SELECT COUNT(*) AS total_records FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key .'=\''.mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        $sql .= ' LIMIT 1';
        
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $row = mysql_fetch_object($result);
        mysql_free_result($result);
        
        return $row->total_records;
    }
    public function get_row($tablename,$condition=NULL){
        $sql = "SELECT * FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key . '=\''. mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        $sql .= ' LIMIT 1';
        
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $row = mysql_fetch_object($result);
        mysql_free_result($result);
        
        return $row;
    }
    public function get_value($tablename,$condition,$field){
        $sql = "SELECT $field FROM $tablename";
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key .'=\''.mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        $sql .= ' LIMIT 1';
        
        $result = mysql_query($sql, $this->_connection);
        if (!$result){
            return FALSE;
        }
        
        $row = mysql_fetch_object($result);
        mysql_free_result($result);
        
        return $row->$field;
    }
    public function insert($tablename,&$data){
        $sql = "INSERT INTO $tablename (". implode(',', array_keys($data)) .') VALUES ';
        
        $value_array = array();
        foreach (array_values($data) as $value){
            $value_array [] = '\''.mysql_real_escape_string($value).'\'';
        }
        $sql .= '('. implode(',', $value_array) .')';
        
        if (mysql_query($sql, $this->_connection)){
            return mysql_insert_id($this->_connection);
        }else{
            return FALSE;
        }
    }
    public function update($tablename, &$data, $condition=NULL){
        $sql ="UPDATE $tablename SET ";
        
        $update_array = array();
        foreach ($data as $key=>$value){
            $update_array [] = $key .'=\''.mysql_real_escape_string($value).'\'';
        }
        $sql .= implode(',', $update_array);
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key .'=\''.mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        if (mysql_query($sql, $this->_connection)){
            return mysql_affected_rows($this->_connection);
        }else{
            return FALSE;
        }
    }
    public function delete($tablename, $condition){
        $sql ="DELETE FROM $tablename";
        
        if ($condition && is_array($condition)){
            $condition_array = array();
            foreach ($condition as $key => $val){
                $condition_array [] = $key .'=\''.mysql_real_escape_string($val).'\'';
            }
            $sql .= ' WHERE '. implode(' AND ', $condition_array);
        }
        if (mysql_query($sql, $this->_connection)){
            return mysql_affected_rows($this->_connection);
        }else{
            return FALSE;
        }
    }
}

interface IDatabase {
    public function type();
    public function connect($host,$db_name,$db_user,$db_pwd);
    public function close();
    public function query($sql);
    public function get($tablename,$offset=NULL,$limit=NULL);
    public function get_by($tablename,$condition,$field='*');
    public function get_count($tablename,$condition=NULL);
    public function get_row($tablename,$condition=NULL);
    public function get_value($tablename,$condition,$field);
    public function insert($tablename,&$data);
    public function update($tablename, &$data, $condition=NULL);
    public function delete($tablename, $condition);
}
