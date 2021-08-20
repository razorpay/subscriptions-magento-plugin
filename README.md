## Razorpay Payment Extension for subscriptions using magento

This is the official Razorpay Subscriptions payment gateway plugin for Magento. Allows you to accept recurring payments on Magento using the Razorpay Subscriptions API.

## Dependencies
1. Razorpay subscription module is dependent on [razorpay/magento](""https://github.com/razorpay/razorpay-magento/releases"). Make sure that this module is already existing before running the `enable` or `upgrade` command

### Installation

Install the extension through composer package manager.

```
composer require razorpay/subscriptions-magento-plugin
bin/magento module:enable Razorpay_Subscription
```

### Install through "code.zip" file

Extract the attached code.zip from release

Go to "app" folder

Overwrite content of "code" folder with step one "code" folder (Note: if code folder not exist just place the code folder from step-1).

Run from magento root folder.

```
bin/magento module:enable Razorpay_Magento
bin/magento module:enable Razorpay_Subscription
bin/magento setup:upgrade
```

You can check if the module has been installed using `bin/magento module:status`

You should be able to see `Razorpay_Magento` and `Razorpay_Subscription` in the module list


Go to `Admin -> Stores -> Configuration -> Payment Method -> Razorpay` to configure Razorpay


If you do not see Razorpay in your gateway list, please clear your Magento Cache from your admin
panel (System -> Cache Management).

### Note: Don't mix composer and zip install.

### Note: Make sure "zipcode" must be required field for billing and shipping address.**


### Support

Visit [https://razorpay.com](https://razorpay.com) for support requests or email contact@razorpay.com.
