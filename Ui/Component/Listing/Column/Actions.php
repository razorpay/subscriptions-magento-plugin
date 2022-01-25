<?php
namespace Razorpay\Subscription\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
 * Url Path 
*/
    const SUBSCRIPTION_VIEW_URL_PATH   = 'subscribed/subscription/view';
    const CANCEL_URL_PATH   = 'subscribed/subscription/cancel';
    const PAUSE_URL_PATH    = 'subscribed/subscription/pause';
    const RESUME_URL_PATH   = 'subscribed/subscription/resume';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = array(),
        UrlInterface $urlBuilder,
        array $data = array()
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param  array $dataSource
     * @return void
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item['entity_id'])) {
                    $item[$name]['view'] = [
                        'href' => $this->urlBuilder->getUrl(self::SUBSCRIPTION_VIEW_URL_PATH, ['subscription_id' => strip_tags($item['subscription_id']),'id' => $item['entity_id']]),
                        'label' => __('View'),
                        '__disableTmpl' => true,
                    ];
                    $item[$name]['pause'] = [
                        'href' => $this->urlBuilder->getUrl(self::PAUSE_URL_PATH, ['subscription_id' => strip_tags($item['subscription_id'])]),
                        'label' => __('Pause'),
                        '__disableTmpl' => true,
                    ];
                    $item[$name]['resume'] = [
                        'href' => $this->urlBuilder->getUrl(self::RESUME_URL_PATH, ['subscription_id' => strip_tags($item['subscription_id'])]),
                        'label' => __('Resume'),
                        '__disableTmpl' => true,
                    ];
                    $item[$name]['cancel'] = [
                        'href' => $this->urlBuilder->getUrl(self::CANCEL_URL_PATH, ['subscription_id' => strip_tags($item['subscription_id'])]),
                        'label' => __('Cancel'),
                        'confirm' => [
                            'title' => __('Cancel Subscription'),
                            'message' => __('Are you sure you want to cancel a subscription?'),
                            '__disableTmpl' => true,
                        ],
                        'post' => true,
                        '__disableTmpl' => true,
                    ];

                }
            }
        }

        return $dataSource;
    }
}