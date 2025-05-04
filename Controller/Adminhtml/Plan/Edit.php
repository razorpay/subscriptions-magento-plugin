<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;
 
use Magento\Backend\App\Action;
 
class Edit extends Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
 
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;
 
    /**
     * @var \Razorpay\Subscription\Model\Plans
     */
    protected $_model;
 
    /**
     * @param Action\Context                             $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry                $registry
     * @param \Razorpay\Subscription\Model\Plans         $model
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Razorpay\Subscription\Model\Plans $model
    ) {
        $this->_resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        $this->_model = $model;
        parent::__construct($context);
    }
 
    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Razorpay_Subscription::plan_save');
    }
 
    /**
     * Init actions
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Razorpay_Subscription::rzp_subscriptions')
            ->addBreadcrumb(__('Plan'), __('Plan'))
            ->addBreadcrumb(__('Manage Plans'), __('Manage Plans'));
        return $resultPage;
    }
 
    /**
     * Edit Plan
     *
     * @return                                  \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_model;
 
        // If you have got an id, it's edition
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This plan not exists.'));
                /**
 * \Magento\Backend\Model\View\Result\Redirect $resultRedirect 
*/
                $resultRedirect = $this->resultRedirectFactory->create();
 
                return $resultRedirect->setPath('*/*/');
            }
        }
 
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $model->setData($data);
        }
 
        $this->_coreRegistry->register('subscribed_plan', $model);
 
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Plan') : __('New Plan'),
            $id ? __('Edit Plan') : __('New Plan')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Plans'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getName() : __('New Plan'));
 
        return $resultPage;
    }
}