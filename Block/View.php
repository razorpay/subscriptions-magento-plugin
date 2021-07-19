<?php

namespace Razorpay\Subscription\Block;

use \Magento\Framework\Exception\LocalizedException;
use \Magento\Framework\View\Element\Template;
use \Magento\Framework\View\Element\Template\Context;
use \Magento\Framework\Registry;
use \Razorpay\Subscription\Model\Subscrib;
use \Razorpay\Subscription\Model\SubscribFactory;
use \Razorpay\Subscription\Controller\Subscrib\View as ViewAction;

class View extends Template
{
    /**
     * Core registry
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * Subscrib
     * @var null|Subscrib
     */
    protected $_subscrib = null;

    /**
     * SubscribCollectionFactory
     * @var null|SubscribFactory
     */
    protected $_subscribFactory = null;

    /**
     * Constructor
     * @param Context $context
     * @param Registry $coreRegistry
     * @param SubscribFactory $SubscribCollectionFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        SubscribFactory $subscribFactory,
        array $data = []
    ) {
        $this->_subscribFactory = $subscribFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * Lazy loads the requested subscrib
     * @return Subscrib
     * @throws LocalizedException
     */
    public function getSubscrib()
    {

        //echo "hello";exit;
        if ($this->_subscrib === null) {
            /** @var subscrib $subscrib */
            $subscrib = $this->_subscribFactory->create();
            $subscrib->load($this->_getSubscribId());

            if (!$subscrib->getId()) {
                throw new LocalizedException(__('Post not found'));
            }

            $this->_subscrib = $subscrib;
        }
        return $this->_subscrib;
    }

    /**
     * Retrieves the subscrib id from the registry
     * @return int
     */
    protected function _getSubscribId()
    {
        return (int) $this->_coreRegistry->registry(
            ViewAction::REGISTRY_KEY_POST_ID
        );
    }
}