<?php

namespace Razorpay\Subscription\Controller\Adminhtml\Plan;

use Magento\Backend\App\Action;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Razorpay\Api\Api;
use Razorpay\Magento\Model\Config;

class Save extends Action
{
    /**
     * @param Action\Context $context
     * @param \Razorpay\Subscription\Model\Plans $model
     */
    protected $_storeManager;

    private \Psr\Log\LoggerInterface $logger;

    public function __construct(
        Action\Context                             $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface                   $logger
    )
    {
        parent::__construct($context);
        $this->_storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Razorpay_Subscription::plan_save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->logger->info("-------------------Plan creation------------------");

        try {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');

            // get config data for razorpay key
            $key_id = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_id'");
            $key_secret = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_secret'");

            $rzpId = $key_id[0]['value'];
            $rzpSecret = $key_secret[0]['value'];
            $rzp = new Api($rzpId, $rzpSecret);

            $data = $this->getRequest()->getPostValue();

            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($data) {
                $id = $this->getRequest()->getParam('id');
                if ($id) {
                    //edit part for status change
                    $plan = $objectManager->create('Razorpay\Subscription\Model\Plans')->load($id);
                    $plan->setPlanStatus($this->getRequest()->getParam('plan_status'))
                            ->save();
                    $this->logger->info("Plan update successfully");
                    $this->messageManager->addSuccess(__('Plan update successfully'));
                    return $resultRedirect->setPath('*/*/index');

                } else {
                    $this->logger->info("Checking for existing plan");
                    $checkPlanData = $objectManager->get('Razorpay\Subscription\Model\Plans')
                        ->getCollection()
                        ->addFieldToFilter('plan_type', $this->getRequest()->getParam('plan_type'))
                        ->addFieldToFilter('magento_product_id', $this->getRequest()->getParam('magento_product_id'))
                        ->addFieldToFilter('plan_interval', $this->getRequest()->getParam('plan_interval'))
                        ->addFieldToFilter('plan_bill_cycle', $this->getRequest()->getParam('plan_bill_cycle'))
                        ->addFieldToFilter('plan_bill_amount', $this->getRequest()->getParam('plan_bill_amount'))
                        ->getData();

                    if ($checkPlanData) {
                        $this->logger->info("Plan already exists");
                        $this->messageManager->addError(__('Plan already exists'));
                    } else {
                        $this->logger->info("Creating new plan ");
                        $planData = [
                            "period" => $this->getRequest()->getParam('plan_type'),
                            "interval" => $this->getRequest()->getParam('plan_interval'),
                            "item" => [
                                "name" => $this->getRequest()->getParam('plan_name'),
                                "amount" => (int)(number_format($this->getRequest()->getParam('plan_bill_amount') * 100, 0, ".", "")),
                                "currency" => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
                                "description" => $this->getRequest()->getParam('plan_desc')
                            ],
                            "notes" => [
                                "source" => "magento"
                            ]
                        ];

                        $planResponse = $rzp->plan->create($planData);

                        $plan = $objectManager->create('Razorpay\Subscription\Model\Plans');
                        $plan->setPlanName($this->getRequest()->getParam('plan_name'))
                            ->setPlanId($planResponse->id)
                            ->setPlanDesc($this->getRequest()->getParam('plan_desc'))
                            ->setMagentoProductId($this->getRequest()->getParam('magento_product_id'))
                            ->setPlanBillAmount($this->getRequest()->getParam('plan_bill_amount'))
                            ->setPlanInterval($this->getRequest()->getParam('plan_interval'))
                            ->setPlanType($this->getRequest()->getParam('plan_type'))
                            ->setPlanBillCycle($this->getRequest()->getParam('plan_bill_cycle'))
                            ->setPlanTrial($this->getRequest()->getParam('plan_trial'))
                            ->setPlanAddons($this->getRequest()->getParam('plan_addons'))
                            ->setPlanStatus($this->getRequest()->getParam('plan_status'))
                            ->save();

                        $this->logger->info("Plan saved successfully");
                        $this->messageManager->addSuccess(__('Plan saved successfully'));
                        return $resultRedirect->setPath('*/*/index');
                    }

                }
            }

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->logger->info("Plan error localized exception: {$e->getMessge()}");
            $this->messageManager->addError($e->getMessage());
        } catch (\RuntimeException $e) {
            $this->logger->info("Plan error run time exception: {$e->getMessge()}");
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->info("Plan error: {$e->getMessge()}");
            $this->messageManager->addException($e, __('Something went wrong while saving the plan'));
        }
    }
}
