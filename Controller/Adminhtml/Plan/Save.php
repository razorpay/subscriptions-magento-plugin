<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;

use Magento\Backend\App\Action;
use Razorpay\Magento\Controller\BaseController;

class Save extends BaseController
{
    /**
     * @param Action\Context $context
     * @param \Razorpay\Subscription\Model\Plans $model
     */
    protected $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        \Magento\Framework\App\Action\Context      $context,
        \Magento\Customer\Model\Session            $customerSession,
        \Magento\Checkout\Model\Session            $checkoutSession,
        \Razorpay\Magento\Model\Config             $config,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface                   $logger
    )
    {
        parent::__construct(
            $context,
            $customerSession,
            $checkoutSession,
            $config
        );
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->config = $config;
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

            $data = $this->getRequest()->getPostValue();

            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($data) {
                if ($data["entity_id"]) {

                    //edit part for status change
                    $plan = $objectManager->create('Razorpay\Subscription\Model\Plans')->load($data["entity_id"]);

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
                                "currency" => $this->storeManager->getStore()->getCurrentCurrency()->getCode(),
                                "description" => $this->getRequest()->getParam('plan_desc')
                            ],
                            "notes" => [
                                "source" => "magento-subscription"
                            ]
                        ];

                        $planResponse = $this->rzp->plan->create($planData);

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
