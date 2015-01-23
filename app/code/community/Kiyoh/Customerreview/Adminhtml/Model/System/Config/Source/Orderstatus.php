<?php
/**
 * My own options
 *
 */
class Kiyoh_Customerreview_Adminhtml_Model_System_Config_Source_Orderstatus
{

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $magentoVersion = Mage::getVersion();
        if (version_compare($magentoVersion, '1.5', '>=')) {
            //version is 1.5 or greater
            $data = Mage::getModel('sales/order_status')->getResourceCollection()->getData();
            foreach ($data as $key => $item) {
                $data[$key]['value'] = $item['status'];
            }
            return $data;
        } else {
            //version is below 1.5
            $data = Mage::getSingleton('sales/order_config')->getStatuses();
            $dataArray = array();
            $i = 0;
            foreach ($data as $key => $item) {
                $dataArray[$i]['value'] = $key;
                $dataArray[$i]['label'] = $item;
                $i++;
            }
            return $dataArray;
        }        
    }
}