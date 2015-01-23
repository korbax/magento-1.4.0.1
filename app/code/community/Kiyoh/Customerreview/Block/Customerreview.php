<?php
class Kiyoh_Customerreview_Block_Customerreview extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getCustomerreview()     
     { 
        if (!$this->hasData('customerreview')) {
            $this->setData('customerreview', Mage::registry('customerreview'));
        }
        return $this->getData('customerreview');
        
    }
}