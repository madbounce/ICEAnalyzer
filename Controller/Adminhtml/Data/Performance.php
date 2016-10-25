<?php
namespace ICEShop\ICEAnalyzer\Controller\Adminhtml\Data;
class Performance extends \Magento\Framework\App\Action\Action
{
    public $connection;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory resultPageFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {

        $this->getConnection();

        $html = '';

        $mysql_attributes = array(
            'read_buffer_size',
            'read_rnd_buffer_size',
            'sort_buffer_size',
            'thread_stack',
            'join_buffer_size',
            'binlog_cache_size',
            'max_connections',
            'innodb_buffer_pool_size',
            'innodb_additional_mem_pool_size',
            'innodb_log_buffer_size',
            'key_buffer_size',
            'query_cache_size'
        );
        $MySQLAdvices = array(
            'maximum_possible_memory_usage' => array(
                'advice' => array(
                    'label' => __('MySQL maximum possible memory usage'),
                    'link' => ''
                )
            ),
            'network' => array(
                'advice' => array(
                    'label' => __('Network speed'),
                    'link' => ''
                )
            ),
            'copy' => array(
                'advice' => array(
                    'label' => __('HDD write speed'),
                    'link' => ''
                )
            ),
        );


        //check tmp dir
        $tmp_dir = BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp';
        $error_msg = '';
        try {
            if (!is_dir($tmp_dir)) {
                mkdir($tmp_dir);
            }
        } catch (Exception $e) {
            $error_msg = 'We can`t create a required directory in "[Magento store root]/var/tmp", or directory has not enough rights (0777 needed). ';
            $error_msg .= 'Error: ' . $e->getMessage();
        }
        //files that we create for tests and after will delete
        $local_file = $tmp_dir . DIRECTORY_SEPARATOR . 'ftp_test_iceshop.csv';
        $test_file = $tmp_dir . DIRECTORY_SEPARATOR . 'test.txt';

        try {
            // Mysql test #1 MySQL maximum possible memory usage
            $sqlQuery = "SHOW VARIABLES;";
            $sqlResult = $this->connection->fetchAll($sqlQuery);
            $mysql_vars = array();
            foreach ($sqlResult as $mysqlVar) {
                $mysql_vars[$mysqlVar['Variable_name']] = $mysqlVar['Value'];
            }


            $flag_errors = false;
            //check all needed attributes from MySQL
            foreach ($mysql_attributes as $my_attrbute) {
                if (!isset($mysql_vars[$my_attrbute])) {
                    $return['maximum_possible_memory_usage']['name'] = __('MySQL maximum possible memory usage');
                    $return['maximum_possible_memory_usage']['current'] = __('You don`t have rights to get needed MySQL parameters');
                    $return['maximum_possible_memory_usage']['result'] = false;
                    $return['maximum_possible_memory_usage']['recommended'] = '>=10%';
                    $return['maximum_possible_memory_usage']['advice'] = $MySQLAdvices['maximum_possible_memory_usage'];
                    $flag_errors = true;
                    break;
                }
            }
            if (!$flag_errors) {
                $max_possible_memory_usage = ($mysql_vars['read_buffer_size'] + $mysql_vars['read_rnd_buffer_size'] + $mysql_vars['sort_buffer_size'] + $mysql_vars['thread_stack'] + $mysql_vars['join_buffer_size'] + $mysql_vars['binlog_cache_size']) * $mysql_vars['max_connections'] +
                    $mysql_vars['innodb_buffer_pool_size'] + $mysql_vars['innodb_additional_mem_pool_size'] + $mysql_vars['innodb_log_buffer_size'] + $mysql_vars['key_buffer_size'] + $mysql_vars['query_cache_size'];

                $totalMemory = $this->getTotalMemory(false);

                if ((isset($max_possible_memory_usage)) && (!empty($totalMemory))) {
                    $result = ($max_possible_memory_usage * 100) / $totalMemory;
                    $result = round($result, 2);
                    $return['maximum_possible_memory_usage']['name'] = __('MySQL maximum possible memory usage');
                    $return['maximum_possible_memory_usage']['current'] = $result . ' %';
                    $return['maximum_possible_memory_usage']['result'] = ((int)$result >= 10) ? true : false;
                    $return['maximum_possible_memory_usage']['recommended'] = '>=10%';
                    $return['maximum_possible_memory_usage']['advice'] = $MySQLAdvices['maximum_possible_memory_usage'];
                } else {
                    $return['maximum_possible_memory_usage']['name'] = __('MySQL maximum possible memory usage');
                    $return['maximum_possible_memory_usage']['current'] = __('We can`t get total memory of your RAM');
                    $return['maximum_possible_memory_usage']['result'] = false;
                    $return['maximum_possible_memory_usage']['recommended'] = '>=10%';
                    $return['maximum_possible_memory_usage']['advice'] = $MySQLAdvices['maximum_possible_memory_usage'];
                }
            }


            // #2 Network test

            $ftp_server = 'ftp.iceshop.nl';
            $ftp_user_name = 'test_magento';
            $ftp_user_pass = 'test_magento';
            // define some variables
            $server_file = 'example_large_50000.csv';

            // set up basic connection
            $conn_id = ftp_connect($ftp_server);

            // login with username and password
            $login_result = @ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
            ftp_pasv($conn_id, true);
            $start_time = microtime(true);
            if ($conn_id && $login_result) {
                // try to download $server_file and save to $local_file
                if (ftp_get($conn_id, $local_file, $server_file, FTP_ASCII)) {
                    $end_time = microtime(true) - $start_time;
                    $fs = (filesize($local_file)) / 1048576; // thinking that file would be in Kb
                    $speed = ($fs / $end_time) * 8;;
                    $return['network']['name'] = __('Network speed');
                    $return['network']['current'] = round($speed, 2) . ' (MBit/s)';
                    $return['network']['result'] = true;
                    $return['network']['recommended'] = '-';
                    $return['network']['advice'] = $MySQLAdvices['network'];
                } else {
                    $return['network']['name'] = __('Network speed');
                    $return['network']['current'] = __('Can`t download file. ') . $error_msg;
                    $return['network']['result'] = false;
                    $return['network']['recommended'] = '-';
                    $return['network']['advice'] = $MySQLAdvices['network'];
                }
            } else {
                $return['network']['name'] = __('Network speed');
                $return['network']['current'] = __('Can`t connect to server. ');
                $return['network']['result'] = false;
                $return['network']['recommended'] = '-';
                $return['network']['advice'] = $MySQLAdvices['network'];
            }
            // close the connection
            ftp_close($conn_id);

            // #3 Hdd copy test
            clearstatcache();
            $hdd_capacity_size = $this->getHddCapacity();
            if (!empty($hdd_capacity_size['status']) && ($hdd_capacity_size['status'] == true)) {
                $start_generate_file = microtime(true);
                $fh = fopen($test_file, 'w');
                $needed_size = 300; //we generate file with size 300 MB
                $size = 1024 * 1024 * $needed_size;
                $chunk = 1024;
                while ($size > 0) {
                    fputs($fh, str_pad('', min($chunk, $size)));
                    $size -= $chunk;
                }
                fclose($fh);
                $end_generate_file = microtime(true) - $start_generate_file;

                if (file_exists($test_file)) {
                    $result = ((filesize($test_file)) / 1048576) / $end_generate_file;
                    $return['copy']['name'] = __('HDD write speed');
                    $return['copy']['current'] = round($result, 2) . ' (MB/s)';
                    $return['copy']['result'] = true;
                    $return['copy']['recommended'] = '-';
                    $return['copy']['advice'] = $MySQLAdvices['copy'];
                } else {
                    $return['copy']['name'] = __('HDD write speed');
                    $return['copy']['current'] = __('Can`t create test file. ') . $error_msg;
                    $return['copy']['result'] = false;
                    $return['copy']['recommended'] = '-';
                    $return['copy']['advice'] = $MySQLAdvices['copy'];
                }
            } else {
                $return['copy']['name'] = __('HDD write speed');
                $return['copy']['current'] = __('Not enough hdd free space');
                $return['copy']['result'] = false;
                $return['copy']['recommended'] = '-';
                $return['copy']['advice'] = $MySQLAdvices['copy'];
            }
            if (file_exists($local_file))
                unlink($local_file);
            if (file_exists($test_file))
                unlink($test_file);


        } catch (Exception $e) {
            if (file_exists($local_file))
                unlink($local_file);
            if (file_exists($test_file))
                unlink($test_file);
            $message = $e->getMessage();
            $return['exception']['message'] = $message;
        }

        //rename labels to correct
        $result = array();
        if (isset($return['copy']))
            $result['HDD write speed'] = $return['copy'];
        if (isset($return['network']))
            $result['Network speed'] = $return['network'];
        if (isset($return['maximum_possible_memory_usage']))
            $result['MySQL maximum possible memory usage'] = $return['maximum_possible_memory_usage'];

        $resultPage = $this->resultPageFactory->create();
        $block['performance_tests'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTests($result);

        $jsonData = json_encode($block);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);

    }

    protected function getConnection()
    {
        if (!$this->connection) {
            $resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
            $this->connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        }
        return $this->connection;
    }

    /**
     * Returns total RAM memory in unix or windows servers
     * @return integer
     */
    public function getTotalMemory($gigabytes = false)
    {
        $totalMemory = 0;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('wmic memorychip get capacity', $totalMemory);
            $totalMemory = array_sum($totalMemory);
            if ($gigabytes) {
                $totalMemory = round(($totalMemory / 1073741824), 2); // convert B to GB
            }
        } else {
            $data = explode("\n", file_get_contents("/proc/meminfo"));
            $meminfo = array();
            if (!empty($data)) {
                foreach ($data as $line) {
                    if (trim($line) != '') {
                        list($key, $val) = explode(":", $line);
                        $meminfo[trim($key)] = trim($val);
                    }
                }
            }
            $totalMemory = (isset($meminfo['MemTotal'])) ? $meminfo['MemTotal'] : 0;
            if ($totalMemory == 0) {
                $meminfo_exec = array();
                exec('cat /proc/meminfo', $minf);
                if (!empty($minf)) {
                    foreach ($minf as $k => $v) {
                        list($key_meminfo, $val_meminfo) = explode(":", $v);
                        $meminfo_exec[trim($key_meminfo)] = trim(str_replace('kB', '', $val_meminfo));
                    }
                }
                $totalMemory = (isset($meminfo_exec['MemTotal'])) ? $meminfo_exec['MemTotal'] : 0;
            }
            //or maybe you can use
            //$totalMemory = shell_exec("free -m -b | grep Mem | awk '{print$2}'");
            if ($gigabytes) {
                $totalMemory = round(($totalMemory / 1048576), 2); // convert KB to GB
            } else {
                $totalMemory = round(($totalMemory * 1024), 2); // convert KB to B
            }
        }
        return $totalMemory;
    }

    /**
     * Get hdd capacity
     * @return string
     */
    public function getHddCapacity()
    {
        $hdd = 0;
        $hdd_free = 0;
        $hdd_sections = 0;
        $status = true;
        $recommended = 3;
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('wmic logicaldisk get size', $hdd_sections);
            $hdd = array_sum($hdd_sections);
            if (!empty($hdd)) {
                $hdd = round($hdd / 1073741824, 2);
            }

            $hdd_sections = 0;
            exec('wmic logicaldisk get FreeSpace', $hdd_sections);
            $hdd_free = array_sum($hdd_sections);
            if (!empty($hdd_free)) {
                $hdd_free = round($hdd_free / 1073741824, 2);
            }

        } else {
            $directory = BP;
            $hdd = disk_total_space($directory);
            $hdd_free = disk_free_space($directory);
            if (!empty($hdd))
                $hdd = round(($hdd / 1073741824), 2);
            if (!empty($hdd_free))
                $hdd_free = round(($hdd_free / 1073741824), 2);
        }
        if ((!empty($hdd_free)) && (!empty($hdd))) {
            $percent = round((($hdd_free * 100) / $hdd), 2);
            if ($percent < $recommended)
                $status = false;
        }
        return array('total' => $hdd, 'free' => $hdd_free, 'status' => $status, 'percent' => $percent);
    }
}