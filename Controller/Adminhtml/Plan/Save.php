<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;

use Magento\Backend\App\Action;
// require in case of zip installation without composer
require_once __DIR__ . "../../../../../Razorpay/Razorpay.php";
use Razorpay\Api\Api;
use Razorpay\Magento\Model\Config;
class Save extends Action
{
    /**
     * @var \Razorpay\Subscription\Model\Plans
     */
    protected $_model;

    /**
     * @param Action\Context $context
     * @param \Razorpay\Subscription\Model\Plans $model
     */
    protected $_storeManager;
    public function __construct(
        Action\Context $context,
        \Razorpay\Subscription\Model\Plans $model,
        \Magento\Store\Model\StoreManagerInterface $storeManager

    ) {
        parent::__construct($context);
        $this->_model = $model;
        $this->_storeManager = $storeManager;



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
//        print_r($this->getRequest()->getPostValue());die;
        $objectManager =   \Magento\Framework\App\ObjectManager::getInstance();
        $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
        $key_id = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_id'");
        $key_secret = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_secret'");

            $rzpId = $key_id[0]['value'];
            $rzpSecret = $key_secret[0]['value'];
            $rzp = new Api($rzpId, $rzpSecret);


        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Razorpay\Subscription\Model\Plans $model */
            $model = $this->_model;

            $id = $this->getRequest()->getParam('id');
            $plan_bill_amount = $this->getRequest()->getParam('plan_bill_amount');

            $checkPlanData = $objectManager->get('Razorpay\Subscription\Model\Plans')
                ->getCollection()
                ->addFieldToFilter('magento_product_id', $this->getRequest()->getParam('magento_product_id'))
                ->addFieldToFilter('plan_type', $this->getRequest()->getParam('plan_type'))
                ->addFieldToFilter('plan_interval', $this->getRequest()->getParam('plan_interval'))
                ->addFieldToFilter('plan_bill_cycle', $this->getRequest()->getParam('plan_bill_cycle'))
                ->addFieldToFilter('plan_bill_amount', $this->getRequest()->getParam('plan_bill_amount'))
                ->addFieldToFilter('plan_trial', $this->getRequest()->getParam('plan_trial'))
                ->getData();

            if ($checkPlanData) {
                $this->messageManager->addError(__('Plan already exists'));
            } else {
                $planData = [
                    "period" => $this->getRequest()->getParam('plan_type'),
                    "interval" => $this->getRequest()->getParam('plan_interval'),//(int) $product->getRazorpaySubscriptionIntervalCount(),
                    "item" => [
                        "name" => $this->getRequest()->getParam('plan_name'),
                        "amount" => (int)(number_format($plan_bill_amount * 100, 0, ".", "")),
                        "currency" => $this->_storeManager->getStore()->getCurrentCurrency()->getCode(),
                        "description" => $this->getRequest()->getParam('plan_desc')
                    ],
                    "notes" => [
                        "source" => "magento"
                    ]
                ];

                $createplan = $rzp->plan->create($planData);
                $plan_id = array('plan_id' => $createplan->id);

                if ($id) {
                    $model->load($id);
                }

                $model->setData(array_merge($plan_id, $data));

                $this->_eventManager->dispatch(
                    'subscribed_plan_prepare_save',
                    ['plans' => $model, 'request' => $this->getRequest()]
                );

                try {
                    $model->save();
                    $this->messageManager->addSuccess(__('Plan saved'));
                    $this->_getSession()->setFormData(false);
                    if ($this->getRequest()->getParam('back')) {
                        return $resultRedirect->setPath('*/*/edit', ['id' => $model->getId(), '_current' => true]);
                    }
                    return $resultRedirect->setPath('*/*/');
                } catch (\Magento\Framework\Exception\LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\RuntimeException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (\Exception $e) {
                    $this->messageManager->addException($e, __('Something went wrong while saving the plan'));
                }
            }
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
