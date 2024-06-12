<?php

namespace Razorpay\Subscription\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action;
require_once __DIR__ . "../../../../../Razorpay/Razorpay.php";
use Razorpay\Api\Api;
class Save extends Action
{
    /**
     * @var \Maxime\Jobs\Model\Department
     */
    protected $_model;

    /**
     * @param Action\Context $context
     * @param \Maxime\Jobs\Model\Department $model
     **/
    public function __construct(
        Action\Context                        $context,
        \Razorpay\Subscription\Model\Subscrib $model
    )
    {
        parent::__construct($context);
        $this->_model = $model;
    }

    /**
     * {@inheritdoc}
     **/
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Razorpay_Subscription::subscription_save');
    }

    /**
     * Save action
     * @return \Magento\Framework\Controller\ResultInterface
     * */
    public function execute()
    {
        $objectManager =   \Magento\Framework\App\ObjectManager::getInstance();

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $this->getRequest()->getPostValue();
        if ($data) {
            /** @var \Razorpay\Subscription\Model\Subscrib $model */
            $model = $this->_model;
            $id = $this->getRequest()->getParam('entity_id');
            $subscriptionId = $this->getRequest()->getParam('s_id');

            if ($id) {
                $model->load($id);
            }

            unset($data['entity_id']);
            unset($data['form_key']);
            unset($data['s_id']);

            $planData = $objectManager->get('Razorpay\Subscription\Model\Plans')
                ->getCollection()
                ->addFieldToFilter('plan_id', $data['plan_id'])
                ->getFirstItem()
                ->getData();

            try {

                $entity_id = $planData['entity_id'];
                $data['schedule_change_at'] = 'cycle_end';

                $connection = $objectManager->get('Magento\Framework\App\ResourceConnection')->getConnection('\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION');
                $key_id = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_id'");
                $key_secret = $connection->fetchAll("SELECT * FROM core_config_data WHERE `path` LIKE 'payment/razorpay/key_secret'");

                $rzpId = $key_id[0]['value'];
                $rzpSecret = $key_secret[0]['value'];
                $rzp = new Api($rzpId, $rzpSecret);

                $subscriptionResponse = $rzp->subscription->fetch($subscriptionId)->update($data);
                $subscription = $objectManager->create('Razorpay\Subscription\Model\Subscriptions');
                $postUpdate = $subscription->load($subscriptionId, 'subscription_id');
                $postUpdate->setPlanEntityId($entity_id);
                $postUpdate->save();

                $this->messageManager->addSuccess(__("Subscription updated"));

                $this->_getSession()->setFormData(false);

                return $resultRedirect->setPath('subscribed/index/index/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
                return $resultRedirect->setPath('subscribed/index/index/');
            }

            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

}