<?php

namespace Razorpay\Subscription\Model;

use \Magento\Framework\Model\AbstractModel;
use \Magento\Framework\DataObject\IdentityInterface;
use \Razorpay\Subscription\Api\Data\SubscribInterface;
/**
 * Class Subscrib
 * @package Razorpay\Subscription\Model
 */
class Subscrib extends AbstractModel implements SubscribInterface, IdentityInterface
{
    /**
     *
     */
    const CACHE_TAG = 'razorpay_subscriptions';
    /**
     * @var string
     */
    // protected $_cacheTag = 'razorpay_subscriptions';
    // /**
    //  * @var string
    //  */
    // protected $_eventPrefix = 'razorpay_subscriptions';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init('Razorpay\Subscription\Model\ResourceModel\Subscrib');
    }


/**
     * Get SubscriptionId
     *
     * @return string|null
     */
    public function getSubscriptionId()
    {
        return $this->getData(self::SUBSCRIPTION_ID);
    }

    /**
     * Get Status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Get Value
     *
     * @return string|null
     */
    public function getValue()
    {
        return $this->getData(self::VALUE);
    }

    /**
     * Get Next Charge At
     *
     * @return string|null
     */
    public function getNextChargeAt()
    {
        return $this->getData(self::NEXT_CHARGE_AT);
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Return identities
     * @return string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Set SubscriptionId
     *
     * @param string $SubscriptionId
     * @return $this
     */
    public function setSubscriptionId($SubscriptionId)
    {
        return $this->setData(self::SUBSCRIPTION_ID, $SubscriptionId);
    }

    /**
     * Set Status
     *
     * @param string $Status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }
 /**
     * Set Value
     *
     * @param string $value
     * @return $this
     */
    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }
    /**
     * Set Next Charge At
     *
     * @param string $nextChargeAt
     * @return $this
     */
    public function setNextChargeAt($nextChargeAt)
    {
        return $this->setData(self::NEXT_CHARGE_AT, $nextChargeAt);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    



}