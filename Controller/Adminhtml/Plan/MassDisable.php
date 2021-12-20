<?php
namespace Razorpay\Subscription\Controller\Adminhtml\Plan;
 
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Razorpay\Subscription\Model\ResourceModel\Plans\CollectionFactory;
 
class MassDisable extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    protected $filter;
 
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
 
 
    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(Context $context, Filter $filter, CollectionFactory $collectionFactory)
    {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        //$enableStatus= array('plan_status'=>'0');
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize();
        foreach ($collection as $item) {
           // $item->delete();
           
            $item->setData('plan_status',0);
            $item->save();
        }
 
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been Disabled.', $collectionSize));
 
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}