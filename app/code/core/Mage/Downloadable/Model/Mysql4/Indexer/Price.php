<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Downloadable products Price indexer resource model
 *
 * @category    Mage
 * @package     Mage_Downloadable
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_Downloadable_Model_Mysql4_Indexer_Price
    extends Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Default
{
    /**
     * Reindex temporary (price result data) for all products
     *
     * @return Mage_Downloadable_Model_Mysql4_Indexer_Price
     */
    public function reindexAll()
    {
        $this->_prepareFinalPriceData();
        $this->_applyCustomOption();
        $this->_applyDownloadableLink();
        $this->_movePriceDataToIndexTable();

        return $this;
    }

    /**
     * Reindex temporary (price result data) for defined product(s)
     *
     * @param int|array $entityIds
     * @return Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Indexer_Price_Interface
     */
    public function reindexEntity($entityIds)
    {
        $this->_prepareFinalPriceData($entityIds);
        $this->_applyCustomOption();
        $this->_applyDownloadableLink();
        $this->_movePriceDataToIndexTable();

        return $this;
    }

    /**
     * Retrieve downloadable links price temporary index table name
     *
     * @see _prepareDefaultFinalPriceTable()
     * @return string
     */
    protected function _getDownloadableLinkPriceTable()
    {
        return $this->getIdxTable($this->getMainTable() . '_downloadable');
    }

    /**
     * Prepare downloadable links price temporary index table
     *
     * @return Mage_Downloadable_Model_Mysql4_Indexer_Price
     */
    protected function _prepareDownloadableLinkPriceTable()
    {
        $write = $this->_getWriteAdapter();
        $table = $this->_getDownloadableLinkPriceTable();

        $query = sprintf('DROP TABLE IF EXISTS %s', $write->quoteIdentifier($table));
        $write->query($query);

        $query = sprintf('CREATE TABLE %s ('
            . ' `entity_id` INT(10) UNSIGNED NOT NULL,'
            . ' `customer_group_id` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `website_id` SMALLINT(5) UNSIGNED NOT NULL,'
            . ' `min_price` DECIMAL(12,4) DEFAULT NULL,'
            . ' `max_price` DECIMAL(12,4) DEFAULT NULL,'
            . ' PRIMARY KEY (`entity_id`,`customer_group_id`,`website_id`)'
            . ') ENGINE=MYISAM DEFAULT CHARSET=utf8',
            $write->quoteIdentifier($table));
        $write->query($query);

        return $this;
    }

    /**
     * Calculate and apply Downloadable links price to index
     *
     * @return Mage_Downloadable_Model_Mysql4_Indexer_Price
     */
    protected function _applyDownloadableLink()
    {
        $write  = $this->_getWriteAdapter();
        $table  = $this->_getDownloadableLinkPriceTable();

        $this->_prepareDownloadableLinkPriceTable();

        $dlType = $this->_getAttribute('links_purchased_separately');

        $select = $write->select()
            ->from(
                array('i' => $this->_getDefaultFinalPriceTable()),
                array('entity_id', 'customer_group_id', 'website_id'))
            ->join(
                array('dl' => $dlType->getBackend()->getTable()),
                "dl.entity_id = i.entity_id AND dl.attribute_id = {$dlType->getAttributeId()}"
                    . " AND dl.store_id = 0",
                array())
            ->join(
                array('dll' => $this->getTable('downloadable/link')),
                'dll.product_id = i.entity_id',
                array())
            ->join(
                array('dlpd' => $this->getTable('downloadable/link_price')),
                'dll.link_id = dlpd.link_id AND dlpd.website_id = 0',
                array())
            ->joinLeft(
                array('dlpw' => $this->getTable('downloadable/link_price')),
                'dlpd.link_id = dlpw.link_id AND dlpw.website_id = i.website_id',
                array())
            ->where('dl.value = ?', 1)
            ->group(array('i.entity_id', 'i.customer_group_id', 'i.website_id'))
            ->columns(array(
                'min_price' => new Zend_Db_Expr('MIN(IF(dlpw.price_id, dlpw.price, dlpd.price))'),
                'max_price' => new Zend_Db_Expr('SUM(IF(dlpw.price_id, dlpw.price, dlpd.price))')
            ));

        $query = $select->insertFromSelect($table);
        $write->query($query);

        $select = $write->select()
            ->join(
                array('id' => $table),
                'i.entity_id = id.entity_id AND i.customer_group_id = id.customer_group_id'
                    .' AND i.website_id = id.website_id',
                array())
            ->columns(array(
                'min_price'  => new Zend_Db_Expr('i.min_price + id.min_price'),
                'max_price'  => new Zend_Db_Expr('i.max_price + id.max_price'),
                'tier_price' => new Zend_Db_Expr('IF(i.tier_price IS NOT NULL, i.tier_price + id.min_price, NULL)')
            ));

        $query = $select->crossUpdateFromSelect(array('i' => $this->_getDefaultFinalPriceTable()));
        $write->query($query);

        $write->truncate($table);

        return $this;
    }
}
