<?php
namespace Razorpay\Subscription\Model\ResourceModel;


/**
 * Class Subscrib
 * @package Razorpay\Subscription\Model\ResourceModel
 */
class Subscrib extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     *
     */
    protected function _construct() {
        $this->_init('razorpay_subscriptions', 'entity_id');
    }
}