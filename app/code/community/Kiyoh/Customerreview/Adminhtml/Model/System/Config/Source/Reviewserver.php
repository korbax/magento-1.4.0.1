<?php
/**
 * My own options
 *
 */
class Kiyoh_Customerreview_Adminhtml_Model_System_Config_Source_Reviewserver
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'kiyoh.nl', 'label'=>Mage::helper('adminhtml')->__('Kiyoh Netherlands')),
            array('value' => 'kiyoh.com', 'label'=>Mage::helper('adminhtml')->__('Kiyoh International')),

        );
    }

}
?>