<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Subscription;
 
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
     * @var \Maxime\Jobs\Model\Department
     */
    protected $_model;
 
    /**
     * @param Action\Context                             $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry                $registry
     * @param \Maxime\Jobs\Model\Department              $model
     */
    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $registry,
        \Razorpay\Subscription\Model\Subscrib $model
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
        return $this->_authorization->isAllowed('Razorpay_Subscription::subscription_upgrade');
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
        $resultPage->setActiveMenu('Magento_Sales::sales')
            ->addBreadcrumb(__('Subscription'), __('Subscription'))
            ->addBreadcrumb(__('Manage Subscriptions'), __('Manage Subscriptions'));
        return $resultPage;
    }
 
    /**
     * Edit Department
     *
     * @return                                  \Magento\Backend\Model\View\Result\Page|\Magento\Backend\Model\View\Result\Redirect
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_model;
 
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This subscription not exists.'));
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
 
        $this->_coreRegistry->register('subscribed_subscription', $model);
 
        /**
 * @var \Magento\Backend\Model\View\Result\Page $resultPage 
*/
        $resultPage = $this->_initAction();
        $resultPage->addBreadcrumb(
            $id ? __('Edit Subscription') : __('New Subscription'),
            $id ? __('Edit Subscription') : __('New Subscription')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Subscriptions'));
        $resultPage->getConfig()->getTitle()
            ->prepend($model->getId() ? $model->getName() : __('New Subscription'));
 
        return $resultPage;
    }
}