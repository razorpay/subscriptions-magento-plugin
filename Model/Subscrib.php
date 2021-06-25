<?php

namespace Razorpay\Subscription\Model;
/**
 * Class Subscrib
 * @package Razorpay\Subscription\Model
 */
class Subscrib extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    /**
     *
     */
    const CACHE_TAG = 'razorpay_subscriptions';
    /**
     * @var string
     */
    protected $_cacheTag = 'razorpay_subscriptions';
    /**
     * @var string
     */
    protected $_eventPrefix = 'razorpay_subscriptions';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Razorpay\Subscription\Model\ResourceModel\Subscrib');
    }

    /**
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return array
     */
    public function getDefaultValues()
    {
        $values = [];
        return $values;
    }
}