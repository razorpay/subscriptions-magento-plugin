<?php

namespace Razorpay\Subscription\Controller\Customer;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\View\Result\Page;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\Exception\LocalizedException;

class Index extends Action
{

	   /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     *
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct(
            $context
        );
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Prints the blog from informed order id
     * @return Page
     * @throws LocalizedException
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
//     protected $_pageFactory;

// 	protected $_subscribFactory;

// 	public function __construct(
// 		\Magento\Framework\App\Action\Context $context,
// 		\Magento\Framework\View\Result\PageFactory $pageFactory,
// 		\Razorpay\Subscription\Model\SubscribFactory $subscribFactory
// 		)
// 	{
// 		$this->_pageFactory = $pageFactory;
// 		$this->_subscribFactory = $subscribFactory;
// 		return parent::__construct($context);
// 	}

// 	public function execute()
// 	{
//         echo 1;
// 		$post = $this->_subscribFactory->create();
// 		//$collection = $post->getCollection();
// 		$collection = $post->getData();
// 		print_r($collection);
// 		foreach($collection as $item){
// 			echo "<pre>";
// 			print_r($item->getData());
// 			echo "</pre>";
// 		}
// 	//exit();
// 		return $this->_pageFactory->create();
// 	}
// }