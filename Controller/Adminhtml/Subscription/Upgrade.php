<?php

namespace Razorpay\Subscription\Controller\Adminhtml\Subscription;

use Magento\Backend\App\Action;

class Upgrade extends Action
{
    /**
     * @var \Maxime\Jobs\Model\Department
     */
    protected $_model;

    /**
     * @param Action\Context                $context
     * @param \Maxime\Jobs\Model\Department $model
     **/
    public function __construct(
        Action\Context                        $context,
        \Razorpay\Subscription\Model\Subscrib $model
    ) {
        parent::__construct($context);
        $this->_model = $model;
    }

    /**
     * {@inheritdoc}
     **/
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Razorpay_Subscription::subscription_upgrade');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect 
*/
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($data) {
            /**
 * @var \Maxime\Jobs\Model\Department $model 
*/
            $model = $this->_model;
            $id = $this->getRequest()->getParam('id');


            if ($id) {
                $model->load($id);
            }
            $model->setData($data);
            $this->_eventManager->dispatch('subscribed_subscription_prepare_upgrade', ['subscrib' => $model, 'request' => $this->getRequest()]);

            try {
                $model->save();
                $this->messageManager->addSuccess(__('Subscription saved'));
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
                $this->messageManager->addException($e, __('Something went wrong while saving the department'));
            }
            $this->_getSession()->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}