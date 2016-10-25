<?php
namespace ICEShop\ICEAnalyzer\Block;

/**
 * GridBlock block
 */
class GridBlock extends \Magento\Framework\View\Element\Template
{
    protected $_html = '';

    protected function _toHtml()
    {
        return 'asdzxc';
    }


    /**
     * @param $data
     * @return string
     */
    public function generateTable($data)
    {
        $return = [];
        $html = '';
        foreach ($data as $key => $value) {
            $html .= '<tr><td>' . $key . ':' . '</td><td>' . $value . '</td></tr>';
        }
        return $html;
    }


    /**
     * @param $data
     * @return string
     */
    public function generateRequirements($data)
    {
        $html = '';
        $html .= '<tr><td>Requirement</td><td>Current Value<td>Recommended Value</td></tr>';
        foreach ($data as $key => $value) {
            if ($value["result"] === true)
                $class = 'iceanalyzer_green';
            else
                $class = 'iceanalyzer_red';

            $html .= '<tr><td>' . $value["label"] . ':' . '</td><td class="' . $class . '">' . $value["current_value"] . '</td><td>' . $value["recommended_value"] . '</td></tr>';
        }
        return $html;
    }


    /**
     * @param $data
     * @return string
     */
    public function generateProblemsDigest($data)
    {
        $i = 1;
        $html = '';
        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $html .= '<tr class="has_problems"><td><span class="iceanalyzer_red">Problem ' . $i . '</span> : ' . '<b class="iceanalyzer_green">"' . $value["label"] . '" </b> current value is : <span class="iceanalyzer_red">' . $value["current_value"] . '</span> and recommended values is : ' . $value["recommended_value"] . '</td></tr>';
            }
        }
        return $html;
    }


    /**
     * @param $data
     * @return string
     */
    public function generateTests($data)
    {
        $html = '';
        $html .= '<tr><td>Requirement</td><td>Current Value<td>Recommended Value</td></tr>';
        foreach ($data as $key => $value) {
            if ($value["result"] === true)
                $class = 'iceanalyzer_green';
            else
                $class = 'iceanalyzer_red';

            $html .= '<tr><td>' . $key . ':' . '</td><td class="' . $class . '">' . $value["current"] . '</td><td>' . $value["recommended"] . '</td></tr>';
        }
        return $html;
    }
}