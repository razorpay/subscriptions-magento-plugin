<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Department;
 
use Magento\Backend\App\Action;
 
class Cancel extends Action
{
    protected $_model;
 
    /**
     * @param Action\Context $context
     * @param \Maxime\Jobs\Model\Department $model
     */
    public function __construct(
        Action\Context $context,
        \Razorpay\Subscription\Model\Subscrib $model
    ) {
        parent::__construct($context);
        $this->_model = $model;
    }
 
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Razorpay_Subscription::subscription_cancel');
    }
 
    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id'); //subscription_id
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */

        echo $id; die('inside single cancel');
        
        // $resultRedirect = $this->resultRedirectFactory->create();
        
        // if ($id) {
        //     try {
        //         $model = $this->_model;
        //         $model->load($id);
        //         $model->delete();
        //         $this->messageManager->addSuccess(__('Department deleted'));
        //         return $resultRedirect->setPath('*/*/');
        //     } catch (\Exception $e) {
        //         $this->messageManager->addError($e->getMessage());
        //         return $resultRedirect->setPath('*/*/edit', ['id' => $id]);
        //     }
        // }
        // $this->messageManager->addError(__('Department does not exist'));
        // return $resultRedirect->setPath('*/*/');
    }
}