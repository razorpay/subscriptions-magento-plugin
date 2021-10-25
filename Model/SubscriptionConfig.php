<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Razorpay\Magento\Model\Config;

class SubscriptionConfig extends AbstractHelper
{
    const IS_SUBSCRIPTION_ACTIVE = 'active';

    /**
     * @var \Razorpay\Magento\Model\Config
     */
    private $config;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var int
     */
    protected $storeId = null;

    public function __construct(
        Config $config,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->config = $config;
    }

    public function isSubscriptionActive()
    {
        return (bool) (int) $this->getConfigData(self::IS_SUBSCRIPTION_ACTIVE);
    }

    /**
     * Retrieve information from payment configuration
     *
     * @param string $field
     * @param null|string $storeId
     *
     * @return mixed
     */
    public function getConfigData($field, $storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->storeId;
        }

        $path = 'payment/' . SubscriptionPaymentMethod::METHOD_CODE . '/' . $field;

        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * Set information from payment configuration
     *
     * @param string $field
     * @param string $value
     * @param null|string $storeId
     *
     * @return mixed
     */
    public function setConfigData($field, $value)
    {
        $path = 'payment/' . SubscriptionPaymentMethod::METHOD_CODE . '/' . $field;

        return $this->configWriter->save($path, $value);
    }
}
