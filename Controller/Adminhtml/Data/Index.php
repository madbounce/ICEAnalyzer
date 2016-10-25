<?php
namespace ICEShop\ICEAnalyzer\Controller\Adminhtml\Data;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ProductMetadata;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Indexer\StateInterface;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    public $connection;

//    public $objectManager;

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


    protected function getConnection()
    {
        if (!$this->connection) {
            $resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
            $this->connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        }
        return $this->connection;
    }


    public function execute()
    {
        $this->getConnection();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();


        $php = array(
            'version' => @phpversion(),
            'server_api' => @php_sapi_name(),
            'memory_limit' => @ini_get('memory_limit'),
            'max_execution_time' => @ini_get('max_execution_time')
        );


        if (version_compare($php['version'], '5.3.0', '>=')) {
            $safeMode['current_value'] = 'Deprecated';
            $safeMode['result'] = false;
        } else {
            $safeMode['result'] = (@ini_get('safe_mode')) ? true : false;
            $safeMode['current_value'] = $this->renderBooleanField($safeMode['result']);
        }
        $memoryLimit = $php['memory_limit'];
        $memoryLimit = substr($memoryLimit, 0, strlen($memoryLimit) - 1);
        $phpCurl = @extension_loaded('curl');
        $phpDom = @extension_loaded('dom');
        $phpGd = @extension_loaded('gd');
        $phpHash = @extension_loaded('hash');
        $phpIconv = @extension_loaded('iconv');
        $phpMcrypt = @extension_loaded('mcrypt');
        $phpPcre = @extension_loaded('pcre');
        $phpPdo = @extension_loaded('pdo');
        $phpPdoMysql = @extension_loaded('pdo_mysql');
        $phpSimplexml = @extension_loaded('simplexml');

        // Get MySQL vars
        $sqlQuery = "SHOW VARIABLES;";
        $sqlResult = $this->connection->fetchAll($sqlQuery);
        $mysqlVars = array();
        foreach ($sqlResult as $mysqlVar) {
            $mysqlVars[$mysqlVar['Variable_name']] = $mysqlVar['Value'];
        }


        $deployConfig = $objectManager->get('Magento\Framework\App\DeploymentConfig');
        $prefix = (string)$deployConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
        );
        $db_config_deafult = $deployConfig->get(
            ConfigOptionsListConstants::CONFIG_PATH_DB_CONNECTION_DEFAULT
        );

        $tablesListQuery = "SELECT COUNT(*) as tables_in_magento FROM information_schema.tables WHERE table_schema = '" . $db_config_deafult['dbname'] . "';";
        $tablesList = $this->connection->fetchAll($tablesListQuery);


        $mysql = array(
            'Version' => $mysqlVars['innodb_version'],
            'Database name' => $db_config_deafult['dbname'],
            'Database tables' => (isset($tablesList[0]['tables_in_magento'])) ? (string)$tablesList[0]['tables_in_magento'] : 'N/A',
            'Table prefix' => $prefix,
            'Connection timeout' => $mysqlVars['connect_timeout'] . ' sec.',
            'Wait timeout' => $mysqlVars['wait_timeout'] . ' sec.',
        );

        $server_information = array(
            'Info' => php_uname(),
            'Domain' => isset($_SERVER['HTTP_HOST']) ? str_replace('www.', '', $_SERVER['HTTP_HOST']) : null,
            'IP' => isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : ''),
            'Server Directory' => BP,
            'CPU' => $this->getCpuInfo(),
            'HDD' => $this->getHddCapacity(),

        );

        $requirements = array(
            'php_version' => array(
                'label' => 'PHP Version',
                'recommended_value' => '>= 5.3.0',
                'current_value' => $php['version'],
                'result' => version_compare($php['version'], '5.3.0', '>='),
                'advice' => array(
                    'label' => __('PHP version at least 5.3.0 required, recommended to use latest stable release.'),
                    'link' => 'http://php.net/downloads.php'
                )
            ),
            'mysql_version' => array(
                'label' => 'MySQL Version',
                'recommended_value' => '>= 4.1.20',
                'current_value' => $mysql['Version'],
                'result' => version_compare($mysql['Version'], '4.1.20', '>='),
                'advice' => array(
                    'label' => __('MySQL version at least 4.1.20 required, recommended to use latest stable release.'),
                    'link' => 'http://dev.mysql.com/downloads/mysql/'
                )
            ),
            'safe_mode' => array(
                'label' => 'Safe Mode',
                'recommended_value' => $this->renderBooleanField(false),
                'current_value' => $safeMode['current_value'],
                'result' => !$safeMode['result'],
                'advice' => array(
                    'label' => __('The PHP safe mode is an attempt to solve the shared-server security problem. Deprecated since PHP 5.3.0'),
                    'link' => 'http://www.php.net/manual/en/features.safe-mode.php'
                )
            ),
            'memory_limit' => array(
                'label' => 'Memory Limit',
                'recommended_value' => '>= 512M (recommended value = 768M)',
                'current_value' => $php['memory_limit'],
                'result' => $this->checkMemoryLimit($php['memory_limit'], (int)512),
                'advice' => array(
                    'label' => __('Maximum amount of memory in bytes that a script is allowed to allocate.'),
                    'link' => 'http://ua2.php.net/manual/en/ini.core.php#ini.memory-limit'
                )
            ),
            'max_execution_time' => array(
                'label' => 'Max. Execution Time',
                'recommended_value' => '>= 360 sec.',
                'current_value' => $php['max_execution_time'],
                'result' => ($php['max_execution_time'] >= 360),
                'advice' => array(
                    'label' => __('This sets the maximum time in seconds a script is allowed to run before it is terminated by the parser.'),
                    'link' => 'http://ua2.php.net/manual/en/info.configuration.php#ini.max-execution-time'
                )
            ),
            'curl' => array(
                'label' => 'curl',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpCurl),
                'result' => $phpCurl,
                'advice' => array(
                    'label' => __('CURL is a library that allows to connect and communicate via a variety of different protocols such as HTTP, HTTPS, FTP, Telnet etc.'),
                    'link' => 'http://www.tomjepson.co.uk/enabling-curl-in-php-php-ini-wamp-xamp-ubuntu/'
                )
            ),
            'dom' => array(
                'label' => 'dom',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpDom),
                'result' => $phpDom,
                'advice' => array(
                    'label' => __('The DOM extension allows to operate on XML documents through the DOM API with PHP 5.'),
                    'link' => 'http://www.php.net/manual/en/dom.setup.php'
                )
            ),
            'gd' => array(
                'label' => 'gd',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpGd),
                'result' => $phpGd,
                'advice' => array(
                    'label' => __('GD is a library which supports a variety of formats, below is a list of formats supported by GD and notes to their availability including read/write support.'),
                    'link' => 'http://www.php.net/manual/en/image.installation.php'
                )
            ),
            'hash' => array(
                'label' => 'hash',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpHash),
                'result' => $phpHash,
                'advice' => array(
                    'label' => __('Hash is a library which allows direct or incremental processing of arbitrary length messages using a variety of hashing algorithms.'),
                    'link' => 'http://www.php.net/manual/en/hash.setup.php'
                )
            ),
            'iconv' => array(
                'label' => 'iconv',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpIconv),
                'result' => $phpIconv,
                'advice' => array(
                    'label' => __('Iconv is a module which contains an interface to iconv character set conversion facility.'),
                    'link' => 'http://ua1.php.net/manual/en/iconv.installation.php'
                )
            ),
            'mcrypt' => array(
                'label' => 'mcrypt',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpMcrypt),
                'result' => $phpMcrypt,
                'advice' => array(
                    'label' => __('Mcrypt library supports a wide variety of block algorithms such as DES, TripleDES, Blowfish (default), 3-WAY, SAFER-SK64, SAFER-SK128, TWOFISH, TEA, RC2 and GOST in CBC, OFB, CFB and ECB cipher modes.'),
                    'link' => 'http://www.php.net/manual/en/mcrypt.installation.php'
                )
            ),
            'pcre' => array(
                'label' => 'pcre',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPcre),
                'result' => $phpPcre,
                'advice' => array(
                    'label' => __('The PCRE library is a set of functions that implement regular expression pattern matching using the same syntax and semantics as Perl 5, with just a few differences.'),
                    'link' => 'http://www.php.net/manual/en/pcre.installation.php'
                )
            ),
            'pdo' => array(
                'label' => 'pdo',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPdo),
                'result' => $phpPdo,
                'advice' => array(
                    'label' => __('The PHP Data Objects (PDO) extension defines a lightweight, consistent interface for accessing databases in PHP.'),
                    'link' => 'http://www.php.net/manual/en/pdo.installation.php'
                )
            ),
            'pdo_mysql' => array(
                'label' => 'pdo_mysql',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpPdoMysql),
                'result' => $phpPdoMysql,
                'advice' => array(
                    'label' => __('PDO_MYSQL is a driver that implements the PHP Data Objects (PDO) interface to enable access from PHP to MySQL 3.x, 4.x and 5.x databases.'),
                    'link' => 'http://ua1.php.net/pdo_mysql#ref.pdo-mysql.installation'
                )
            ),
            'simplexml' => array(
                'label' => 'simplexml',
                'recommended_value' => $this->renderBooleanField(true),
                'current_value' => $this->renderBooleanField($phpSimplexml),
                'result' => $phpSimplexml,
                'advice' => array(
                    'label' => __('The SimpleXML extension provides a very simple and easily usable toolset to convert XML to an object that can be processed with normal property selectors and array iterators.'),
                    'link' => 'http://www.php.net/manual/en/simplexml.installation.php'
                )
            ),
            'Thread Stack' => array(
                'current_value' => $mysqlVars['thread_stack'] . ' bytes',
                'recommended_value' => '>= ' . (192 * 1024),
                'result' => ($mysqlVars['thread_stack'] >= (192 * 1024)),
                'label' => 'Thread Stack',
                'advice' => array(
                    'label' => __('The stack size for each thread.'),
                    'link' => 'https://dev.mysql.com/doc/refman/5.1/en/server-system-variables.html#sysvar_thread_stack'
                )
            ),
            'Max Allowed Packet' => array(
                'current_value' => $mysqlVars['max_allowed_packet'] . ' bytes',
                'recommended_value' => '>= ' . (16 * 1024 * 1024),
                'result' => ($mysqlVars['max_allowed_packet'] >= (16 * 1024 * 1024)),
                'label' => 'Max Allowed Packet',
                'advice' => array(
                    'label' => __('The maximum size of one packet or any generated/intermediate string.'),
                    'link' => 'https://dev.mysql.com/doc/refman/5.1/en/server-system-variables.html#sysvar_max_allowed_packet'
                )
            ),
        );


        $urlInterface = $objectManager->get('\Magento\Backend\Model\UrlInterface');
        $urlPhpInfo = $urlInterface->getUrl('iceshop_iceanalyzer/data/info');

        $php_info = array(
            'Version ' => @phpversion(),
            'Server Api' => @php_sapi_name(),
            'Memory Limit' => @ini_get('memory_limit'),
            'Max. Execution Time' => @ini_get('max_execution_time'),
            'phpinfo()' => '<a href ="' . $urlPhpInfo . '" target="_blank" >&raquoMore info</a>'
        );

        $urlMySQLInfo = $urlInterface->getUrl('iceshop_iceanalyzer/data/info', ['section' => 'mysql']);
        $mysql['MySQL Configuration'] = '<a href ="' . $urlMySQLInfo . '" target="_blank" >&raquoMore info</a>';

        $resultPage = $this->resultPageFactory->create();
        $block['server_info'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTable($server_information);

        $block['system_requirements'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateRequirements($requirements);

        $block['magento_info'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTable($this->getMagentoInfo());

        $block['magento_core_api_info'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTable($this->getMagentoCoreApiInfo());

        $block['php_info'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTable($php_info);

        $block['mysql_info'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateTable($mysql);

        $block['performance_tests'] = '<tr><td></td><td></td><td><a href="javascript:void(0)" class="action-default page-actions-buttons" id="testsRun">' . __("Start") . '</a></td></tr>';

        $problems = [];
        foreach ($requirements as $key => $value) {
            if ($value["result"] === false) {
                $problems[$key] = $value;
            }
        }

        $block['problems_digest'] = $resultPage->getLayout()
            ->createBlock('ICEShop\ICEAnalyzer\Block\GridBlock')
            ->generateProblemsDigest($problems);


        $jsonData = json_encode($block);
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody($jsonData);
    }

    public function getCpuInfo()
    {
        $processor = '';
        $processor_model = '';
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('wmic cpu get name', $processor);
            if (!empty($processor)) {
                if (isset($processor[1])) {
                    $processor_model = $processor[1];
                }
            }
        } else {
            $data = explode("\n", file_get_contents("/proc/cpuinfo"));
            $cpuinfo = array();
            foreach ($data as $line) {
                if (trim($line) != '') {
                    list($key, $val) = explode(":", $line);
                    $cpuinfo[trim($key)] = trim($val);
                }
            }
            $processor_model = (isset($cpuinfo['model name'])) ? $cpuinfo['model name'] : 'Can`t get CPU info';
        }
        return $processor_model;
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

        return 'Total: ' . $hdd . ' GB; Available: ' . $hdd_free . ' GB (' . $percent . '%)';
//        return array('total' => $hdd, 'free' => $hdd_free, 'status' => $status, 'percent' => $percent);
    }

    /**
     * @param boolean $value
     * @return string
     */
    public function renderBooleanField($value)
    {
        if ($value) {
            return __('Enabled');
        }
        return __('Disabled');
    }

    /**
     * Calculate and compare needed value of memory limit
     * @param string $memoryLimit
     * @param integer $compare
     * @return boll
     */
    protected function checkMemoryLimit($memoryLimit, $compare)
    {
        $ml = false;
        if (strripos($memoryLimit, 'G')) {
            $ml = ((int)$memoryLimit) * 1024;
        } else {
            $ml = (int)$memoryLimit;
        }
        if ($ml) {
            if ($ml >= $compare)
                return true;
            else
                return false;
        }
        return false;
    }

    /**
     * @return array
     */
    protected function getMagentoInfo()

    {

        //Updated to use object manager
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productMetadata = $objectManager->get('Magento\Framework\App\ProductMetadataInterface');
        $version = $productMetadata->getVersion(); //will return the magento version
        $scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $flat_catalog_category = $scopeConfig->getValue('catalog/frontend/flat_catalog_category');
        $flat_catalog_product = $scopeConfig->getValue('catalog/frontend/flat_catalog_product');


        $cache_object = $objectManager->get('Magento\Framework\App\Cache\TypeList');
        $cache_types = $cache_object->getTypes();
        $active = 0;
        $inactive = 0;
        if (!empty($cache_types)) {
            foreach ($cache_types as $key => $value) {
                if (!empty($value->getData('status'))) {
                    $active++;
                } else {
                    $inactive++;
                }
            }
        }
        $msg_cache = $active . ' caches active, ' . $inactive . ' caches inactive ';
        $urlInterface = $objectManager->get('\Magento\Backend\Model\UrlInterface');
        $urlCacheManagement = $urlInterface->getUrl('adminhtml/cache/index');

//indexer
        $ready = 0;
        $processing = 0;
        $reindex = 0;
        $resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
        $tableName = $resource->getTableName('indexer_state');
        $sqlIndexerQuery = "SELECT * FROM  " . $tableName . " WHERE 1;";
        $sqlIndexerResult = $this->connection->fetchAll($sqlIndexerQuery);
        foreach ($sqlIndexerResult as $indexer) {
            if ($indexer['status'] == StateInterface::STATUS_VALID) {
                $ready++;
            } elseif ($indexer['status'] == StateInterface::STATUS_WORKING) {
                $processing++;
            } else {
                $reindex++;
            }
        }
        $urlInterface = $objectManager->get('\Magento\Backend\Model\UrlInterface');
        $urlIndexerManagement = $urlInterface->getUrl('indexer/indexer/list');

        $msg_indexer = __($ready . ' indexes are ready, ' . $processing . ' indexes are working, ' . $reindex . ' indexes need reindex');

        $magentoInfo = array(
            'Edition' => ProductMetadata::EDITION_NAME,
            'Version' => $version,
            'Developer Mode' => $objectManager->get('Magento\Framework\App\State')->getMode(),
            'Add Secret Key to URLs' => ($objectManager->get('Magento\Backend\Model\Url')->useSecretKey()) ? __('Yes') : __('No'),
            'Use Flat Catalog Category' => (empty($flat_catalog_category)) ? __('No') : __('Yes'),
            'Use Flat Catalog Product' => (empty($flat_catalog_product)) ? __('No') : __('Yes'),
            'Cache Status' => $msg_cache . '<br><a href="' . $urlCacheManagement . '" target = "_blank" >&raquo' . __('Cache Management') . '</a>',
            'Index Status' => $msg_indexer . '<br><a href="' . $urlIndexerManagement . '" target = "_blank" >&raquo' . __('Index Management') . '</a>',

        );

        return $magentoInfo;
    }

    protected function getMagentoCoreApiInfo()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig = $objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $admin_security_session_lifetime = $scopeConfig->getValue('admin/security/session_lifetime');
        $webapi_soap_charset = $scopeConfig->getValue('webapi/soap/charset');

        $magentoCoreApiInfo = array(
            'Default Response Charset' => (!isset($webapi_soap_charset)) ? __('UTF-8') : (string)$webapi_soap_charset,
            'Admin Session Timeout' => (empty($admin_security_session_lifetime)) ? __('') : $admin_security_session_lifetime,
//            __('WS-I Compliance') => '',
//            __('WSDL Cache') => '',
        );

        return $magentoCoreApiInfo;
    }

}