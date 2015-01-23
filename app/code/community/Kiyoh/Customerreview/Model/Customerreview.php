<?php

class Kiyoh_Customerreview_Model_Customerreview extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('customerreview/customerreview');
    }
}