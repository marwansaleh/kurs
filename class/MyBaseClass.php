<?php if (!defined('APP_PATH')) exit('No direct script access allowed'); 
require APP_PATH . 'class/database.php';
/**
 * Description of MyBaseClass
 *
 * @author Marwan Saleh <amazzura.biz@gamil.com>
 */
class MyBaseClass {
    private $_log_path;
    private $_config = array();
    
    protected $_log_file = 'mylog.log';
    protected $db = NULL;
    protected $_lock_handle = NULL;
    
    function __construct() {
        $this->_log_path = rtrim(sys_get_temp_dir(), '/') .'/';
        
        $this->_config = $this->_read_config_files(APP_PATH .'config');
        
        $this->db = Database::getInstance(
            $this->config('db_dbname'),
            $this->config('db_dbuser'),
            $this->config('db_dbpassword'),
            $this->config('db_dbhost')
        );
    }
    
    /**
     * Get config value
     * @param string $item
     * @return mixed
     */
    public function config($item){
        if (isset($this->_config[$item])){
            return $this->_config[$item];
        }else{
            return NULL;
        }
    }
    
    /**
     * Read all config arrays from files in the specific folder
     * @param string $config_path
     * @return array
     */
    private function _read_config_files($config_path){
        $result_config = array();
        
        if (!$config_path) { $config_path = APP_PATH; }
        
        if ($handle = opendir($config_path)) {

            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".."){
                    include $config_path .'/'. $entry;
                    if (isset($config)){
                        $result_config = array_merge($result_config, $config);
                    }
                }
            }

            closedir($handle);
        }
        return $result_config;
    }
    
    /**
     * Write into log file
     * @param string $event_name log description
     * @throws Exception if failed
     */
    protected function _write_log($event_name=''){
        $content = array(
            date('Y-m-d H:i:s'), 
            getmypid(),
            $event_name
        );
        
        if ($fp = @fopen($this->_log_path . $this->_log_file, 'a')){
            fputcsv($fp, $content, "\t");
            fclose($fp);
        }
    }
    
    /**
     * Read log file using buffer
     * @param string $filepath
     * @param int $lines
     * @param bool $adaptive
     * @return string log if success or FALSE if failed
     */
    protected function read_log($filepath=NULL, $lines=5, $adaptive = true){
        if (!$filepath){
            $filepath = $this->_log_path . $this->_log_file;
        }
        // Open file
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;

        // Sets buffer size
        if (!$adaptive) $buffer = 4096;
        else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));

        // Jump to last character
        fseek($f, -1, SEEK_END);

        // Read it and adjust line number if necessary
        // (Otherwise the result would be wrong if file doesn't end with a blank line)
        if (fread($f, 1) != "\n") $lines -= 1;
        // Start reading
        $output = '';
        $chunk = '';

        // While we would like more
        while (ftell($f) > 0 && $lines >= 0) {

        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);

        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);

        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;

        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);

        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");

        }

        // While we have too many lines
        // (Because of buffer size we might have read too many)
        while ($lines++ < 0) {

        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);

        }

        // Close file and return
        fclose($f);
        return trim($output);
    }
    
    protected function _is_running_proccess($lock_file) { 
        # try exclusive lock
        $lockfile= rtrim(sys_get_temp_dir(), '/') .'/'.$lock_file;
        
        $this->_write_log('Locking '.$lockfile);
        
        $this->_lock_handle = @fopen($lockfile , 'w+');
        if (!$this->_lock_handle){
            $this->_write_log('Unable to fopen '. $lock_file.'. Please make sure the drive is writable');
            return TRUE; //proccess should not be continued;
        }

        if( !flock($this->_lock_handle, LOCK_EX | LOCK_NB) ) { 
            # Unable to obtain lock
            # another process locking the same file is running
            $this->_write_log('Unable to lock '.$lockfile.', another process is running');
            return TRUE; //proccess should not be continued;
        }
        else {
            $this->_write_log('Locked '.$lockfile);
            return FALSE; 
        }
    }
}

/* Filename: MyBaseClass.php */
/* Location: /class/MyBaseClass.php */
