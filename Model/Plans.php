<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\Model\AbstractModel;

class Plans extends AbstractModel
{
    const PLAN_ID = 'entity_id'; // We define the id fieldname
 
    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'subscribed';
 
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'plan';
 
    /**
     * Name of object id field
     *
     * @var string
     */
    protected $_idFieldName = self::PLAN_ID;
 
    /**
     * Initialize resource model
     *
     * @return void
     */
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Razorpay\Subscription\Model\ResourceModel\Plans::class);
    }
    public function getEnableStatus() {
        return 1;
    }
 
    public function getDisableStatus() {
        return 0;
    }
 
    public function getAvailableStatuses() {
        return [$this->getDisableStatus() => __('Disabled'), $this->getEnableStatus() => __('Enabled')];
    }
}
