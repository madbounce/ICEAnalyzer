<?php
namespace ICEShop\ICEAnalyzer\Controller\Adminhtml\Data;
class Info extends \Magento\Framework\App\Action\Action
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

        $section = $this->getRequest()->getParam('section');
        if ($section == 'mysql') {
            // Get MySQL vars
            $sqlQuery = "SHOW VARIABLES;";
            $sqlResult = $this->connection->fetchAll($sqlQuery);
            $mysqlVars = array();
            foreach ($sqlResult as $mysqlVar) {
                $mysqlVars[$mysqlVar['Variable_name']] = $mysqlVar['Value'];
            }
            print('<div class="fieldset iceanalyzer-hidden">');
            print('<div class="hor-scroll">');
            print('<table class="form-list" cellspacing="0" cellpadding="0">');
            foreach ($mysqlVars as $mysql_var_key => $mysql_var_value) {
                print '<tr>';
                print '<td><strong>' . $mysql_var_key . ':</strong></td>';
                print '<td class="value">' . $mysql_var_value . '</td>';
                print '</tr>';
            }
            print('</table>');
            print('</div>');
            print('</div>');
            die;
        } else {
            phpinfo();
            die;
        }
    }

    protected function getConnection()
    {
        if (!$this->connection) {
            $resource = $this->_objectManager->create('\Magento\Framework\App\ResourceConnection');
            $this->connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        }
        return $this->connection;
    }
}