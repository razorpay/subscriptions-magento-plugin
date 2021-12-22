<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;
use Razorpay\Subscription\Helper\Subscription;
 
use Magento\Backend\App\Action;
 
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
    public function __construct(
        Action\Context $context,
        \Razorpay\Subscription\Helper\Subscription $subscription, 
        \Razorpay\Subscription\Model\Plans $model
    ) {
        parent::__construct($context);
        $this->_model = $model;
        $this->subscription    = $subscription;
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
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            /** @var \Razorpay\Subscription\Model\Plans $model */
            $model = $this->_model;
 
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model->load($id);
            }
 
            $model->setData($data);
 
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
 
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}