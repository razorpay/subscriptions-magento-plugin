<?php

namespace Razorpay\Subscription\Ui\DataProvider\Plan;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

/**
 * Class Collection
 * @package MageDigest\Grid\Ui\DataProvider\Category\Listing
 */
class Collection extends SearchResult
{
    /**
     * Override _initSelect to add custom columns
     *
     * @return void
     */
    protected function _initSelect()
    {
//     SELECT `main_table`.*, `second`.`value`, `third`.`attribute_id` FROM `razorpay_plans` AS `main_table`
//  LEFT JOIN `catalog_product_entity_varchar` AS `second` ON main_table.magento_product_id = second.entity_id
//  LEFT JOIN `eav_attribute` AS `third` ON third.attribute_id = second.attribute_id WHERE (third.attribute_code='name' and second.entity_id=main_table.magento_product_id)
$this->addFilterToMap('entity_id', 'main_table.entity_id');
         $this->getSelect()->joinLeft(
            ['secondTable' => $this->getTable('catalog_product_entity_varchar')],
            'main_table.magento_product_id = secondTable.entity_id',
            ['value']
        );
      
        $this->getSelect()->joinLeft(
            ['thirdTable' => $this->getTable('eav_attribute')],
            'thirdTable.attribute_id = secondTable.attribute_id',
            ['attribute_id']
        );

        $this->getSelect()->where("thirdTable.attribute_code='name' and secondTable.entity_id=main_table.magento_product_id");
        // $this->getSelect()->joinLeft(
        //     ['fourthTable' => $this->getTable('customer_entity')],
        //     'fourthTable.entity_id = main_table.magento_user_id', ['firstname', 'lastname']
        // );

        //$this->addFilterToMap('entity_id', 'main_table.entity_id');
        // $this->addFilterToMap('subscription_id', 'main_table.subscription_id');
        // $this->addFilterToMap('firstname', 'fourthTable.firstname');
        // $this->addFilterToMap('lastname', 'fourthTable.lastname');

        parent::_initSelect();
    }
}