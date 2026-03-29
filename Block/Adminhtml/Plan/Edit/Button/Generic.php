<?php

namespace Razorpay\Subscription\Block\Adminhtml\Plan\Edit\Button;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;

class Generic
{
    protected $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->context->getRequest()->getParam('entity_id');
    }

    /**
     * @param $route
     * @param $params
     * @return mixed
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
