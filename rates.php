<?php
require 'root.php';
require APP_PATH . 'utils/simple_html_dom.php';

/* 
 * Description Stabilitas 
 * 
 * Migration Stabilitas Database
 */

class Rates extends MyBaseClass{ 
    private $_table_rate = 'nsc_rates';
    private $_table_bank = 'nsc_rates_update';
    
    public function get_bca_rates(){
        $url = 'http://www.bca.co.id/id/kurs-sukubunga/kurs_counter_bca/kurs_counter_bca_landing.jsp';
        //$rate_to_update = array('USD','AUD','CHF','EUR','GBP','HKD','JPY','SGD','USD','CNY','CAD');
        
        echo 'Try to get content from url: '.$url . PHP_EOL;
        //get the page contents
        $dom = @file_get_html($url);
        if (!$dom){
            echo 'Failed to get content'. PHP_EOL;
            return;
        }
        
        $tables = $dom->find('table');
        echo 'Found ' . count ($tables) . ' table elements'. PHP_EOL;
        
        $bca_rates = array();
        
        //mata uang table index 1
        $mata_uang = array();
        echo 'Extract mata uang...'.PHP_EOL;
        if (isset($tables[1])){
            $i=0;
            foreach ($tables[1]->find('td') as $nama){
                if ($i>0){
                    $mata_uang [] = $nama->plaintext;
                }
                $i++;
            }
        }
        if (!count($mata_uang)){
            echo 'Tidak ada mata uang ditemukan'.PHP_EOL;
            return;
        }
        echo 'Found '. count($mata_uang).'. Proses nilai jual dan beli..'.PHP_EOL;
        //Table Rate index 2
        if (isset($tables[2])){
            $i=0;
            foreach ($tables[2]->find('tr') as $baris){
                if ($i<=1){
                    $i++;
                    continue;
                }
                //get td content, 0:jual, 1:beli
                $nilai = array();
                foreach($baris->find('td') as $td){
                    $nilai [] = $td->plaintext;
                }
                
                $bca_rates[$mata_uang[$i-2]] = $nilai;
                $i++;
            }
        }
        
        //Now update database
        $bank_name = 'bca';
        $last_update = date('Y-m-d');
        
        if (count($bca_rates)){
            //Update rates
            foreach ($bca_rates as $rate=>$nilai){
                if ($this->db->get_count($this->_table_rate, array('bank'=>$bank_name,'name'=>$rate,'last_update'=>$last_update))==0){
                    $data = array(
                        'bank'          => $bank_name,
                        'last_update'   => $last_update,
                        'name'          => $rate,
                        'sell'          => $nilai[0],
                        'buy'           => $nilai[1]
                    );
                    $this->db->insert($data, $this->_table_rate);
                    echo 'Insert '.$rate.' for '.$bank_name.' '.$last_update. PHP_EOL;
                }
            }
            
            //update last update
            $bank_last_update = array(
                'last_update'   => $last_update
            );
            echo 'Update bank '.$bank_name.' last update for '. $last_update. PHP_EOL;
            if ($this->db->get_count($this->_table_bank, array('bank'=>$bank_name))){
                $this->db->update($this->_table_bank, $bank_last_update, array('bank'=>$bank_name));
            }else{
                $bank_last_update['bank'] = $bank_name;
                $this->db->insert($bank_last_update, $this->_table_bank);
            }
        }
    }
    
    public function get_mandiri_rates(){
        $url = 'http://www.bankmandiri.co.id/resource/kurs.asp';
        //$rate_to_update = array('USD','AUD','CHF','EUR','GBP','HKD','JPY','SGD','USD','CNY','CAD');
        
        echo 'Try to get content from url: '.$url . PHP_EOL;
        //get the page contents
        $dom = @file_get_html($url);
        if (!$dom){
            echo 'Failed to get content'. PHP_EOL;
            return;
        }
        
        $table = $dom->find('table.tbl-view',0);
        if (!$table){
            echo 'Failed get rate table'. PHP_EOL;
            return;
        }
        
        echo 'Proses table'. PHP_EOL;
        
        $bank_name = 'mandiri';
        $last_update = date('Y-m-d');
        
        $i=0;
        foreach ($table->find('tr') as $tr){
            if ($i==0){
                $i++;
                continue;
            }
            
            $columns = $tr->find('td');
            $rate = $columns[1]->plaintext;
            $buy = str_replace(array('.',','), array('','.'), $columns[2]->plaintext);
            $sell = str_replace(array('.',','), array('','.'), $columns[4]->plaintext);
            
            if ($this->db->get_count($this->_table_rate, array('bank'=>$bank_name,'name'=>$rate,'last_update'=>$last_update))==0){
                $data = array(
                    'bank'          => $bank_name,
                    'last_update'   => $last_update,
                    'name'          => $rate,
                    'sell'          => $sell,
                    'buy'           => $buy
                );
                $this->db->insert($data, $this->_table_rate);
                echo 'Insert '.$rate.' for '.$bank_name.' '.$last_update. PHP_EOL;
            }
            $i++;
        }
        
        //update last update
        $bank_last_update = array(
            'last_update'   => $last_update
        );
        echo 'Update bank '.$bank_name.' last update for '. $last_update. PHP_EOL;
        if ($this->db->get_count($this->_table_bank, array('bank'=>$bank_name))){
            $this->db->update($this->_table_bank, $bank_last_update, array('bank'=>$bank_name));
        }else{
            $bank_last_update['bank'] = $bank_name;
            $this->db->insert($bank_last_update, $this->_table_bank);
        }
    }
    
    public function get_bni_rates(){
        $url = 'http://www.bni.co.id/informasivalas.aspx';
        //$rate_to_update = array('USD','AUD','CHF','EUR','GBP','HKD','JPY','SGD','USD','CNY','CAD');
        
        echo 'Try to get content from url: '.$url . PHP_EOL;
        //get the page contents
        $dom = @file_get_html($url);
        if (!$dom){
            echo 'Failed to get content'. PHP_EOL;
            return;
        }
        
        $table = $dom->find('table.valas',0);
        if (!$table){
            echo 'Failed get rate table'. PHP_EOL;
            return;
        }
        
        echo 'Proses table'. PHP_EOL;
        
        $bank_name = 'bni';
        $last_update = date('Y-m-d');
        
        $i=0;
        foreach ($table->find('tr') as $tr){
            if ($i==0){
                $i++;
                continue;
            }
            
            $columns = $tr->find('td');
            $rate = $columns[0]->plaintext;
            $buy = $columns[2]->plaintext;
            $sell = $columns[1]->plaintext;
            
            if ($this->db->get_count($this->_table_rate, array('bank'=>$bank_name,'name'=>$rate,'last_update'=>$last_update))==0){
                $data = array(
                    'bank'          => $bank_name,
                    'last_update'   => $last_update,
                    'name'          => $rate,
                    'sell'          => $sell,
                    'buy'           => $buy
                );
                $this->db->insert($data, $this->_table_rate);
                echo 'Insert '.$rate.' for '.$bank_name.' '.$last_update. PHP_EOL;
            }
            $i++;
        }
        
        //update last update
        $bank_last_update = array(
            'last_update'   => $last_update
        );
        echo 'Update bank '.$bank_name.' last update for '. $last_update. PHP_EOL;
        if ($this->db->get_count($this->_table_bank, array('bank'=>$bank_name))){
            $this->db->update($table_bank, $bank_last_update, array('bank'=>$bank_name));
        }else{
            $bank_last_update['bank'] = $bank_name;
            $this->db->insert($bank_last_update, $this->_table_bank);
        }
    }
    
    public function get_bri_rates(){
        $url = 'https://eform.bri.co.id/info/kurs';
        //$rate_to_update = array('USD','AUD','CHF','EUR','GBP','HKD','JPY','SGD','USD','CNY','CAD');
        
        echo 'Try to get content from url: '.$url . PHP_EOL;
        //get the page contents
        $dom = @file_get_html($url);
        if (!$dom){
            echo 'Failed to get content'. PHP_EOL;
            return;
        }
        
        $table = $dom->find('table#hor-minimalist-b',0);
        if (!$table){
            echo 'Failed get rate table'. PHP_EOL;
            return;
        }
        
        echo 'Proses table'. PHP_EOL;
        
        $table_rates = 'ns_rates';
        $table_bank = 'ns_rates_update';
        $bank_name = 'bri';
        $last_update = date('Y-m-d');
        
        $i=0;
        foreach ($table->find('tr') as $tr){
            if ($i==0){
                $i++;
                continue;
            }
            
            $columns = $tr->find('td');
            $rate = $columns[0]->plaintext;
            $buy = $columns[3]->plaintext;
            $sell = $columns[2]->plaintext;
            
            if ($this->db->get_count($this->_table_rate, array('bank'=>$bank_name,'name'=>$rate,'last_update'=>$last_update))==0){
                $data = array(
                    'bank'          => $bank_name,
                    'last_update'   => $last_update,
                    'name'          => $rate,
                    'sell'          => $sell,
                    'buy'           => $buy
                );
                $this->db->insert($data, $this->_table_rate);
                echo 'Insert '.$rate.' for '.$bank_name.' '.$last_update. PHP_EOL;
            }
            $i++;
        }
        
        //update last update
        $bank_last_update = array(
            'last_update'   => $last_update
        );
        echo 'Update bank '.$bank_name.' last update for '. $last_update. PHP_EOL;
        if ($this->db->get_count($this->_table_bank, array('bank'=>$bank_name))){
            $this->db->update($table_bank, $bank_last_update, array('bank'=>$bank_name));
        }else{
            $bank_last_update['bank'] = $bank_name;
            $this->db->insert($bank_last_update, $this->_table_bank);
        }
    }
}

echo 'Calling script...'.PHP_EOL;
$run = new Rates();
$run->get_bca_rates();
$run->get_mandiri_rates();
$run->get_bni_rates();
$run->get_bri_rates();

/* 
 * filename : rates.php
 * location: /rates.php
*/