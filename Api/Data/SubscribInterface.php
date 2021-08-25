<?php

namespace Razorpay\Subscription\Api\Data;

interface SubscribInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const ENTITY_ID               = 'entity_id';
    const SUBSCRIPTION_ID                 = 'subscription_id';
    const NEXT_CHARGE_AT               = 'next_charge_at';
    const STATUS            = 'status';
    const VALUE         = 'value';
    
    /**#@-*/


    /**
     * Get SubscriptionId
     *
     * @return string|null
     */
    public function getSubscriptionId();

    /**
     * Get Status
     *
     * @return string|null
     */
    public function getStatus();
/**
     * Get Value
     *
     * @return string|null
     */
    public function getValue();
    /**
     * Get next charge at
     *
     * @return string|null
     */
    public function getNextChargeAt();

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set SubscriptionId
     *
     * @param string $SubscriptionId
     * @return $this
     */
    public function setSubscriptionId($SubscriptionId);

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Set Value
     *
     * @param string $value
     * @return $this
     */
    public function setValue($value);
    /**
     * Set Crated At
     *
     * @param int $nextChargeAt
     * @return $this
     */
    public function setNextChargeAt($nextChargeAt);

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);
}