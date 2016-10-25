<?php
namespace ICEShop\ICEAnalyzer\Model\Source;

class Performance implements \Magento\Framework\Option\ArrayInterface
{

    protected $_urlBuider;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder
    )
    {
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => $this->_urlBuilder->getUrl("iceshop_iceanalyzer/data/performance"),
                'label' => __('')
            ),
        );
    }

}