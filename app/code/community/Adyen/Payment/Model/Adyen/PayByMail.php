<?php

/**
 * Adyen Payment Module
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category	Adyen
 * @package	Adyen_Payment
 * @copyright	Copyright (c) 2011 Adyen (http://www.adyen.com)
 * @license	http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
/**
 * @category   Payment Gateway
 * @package    Adyen_Payment
 * @author     Adyen
 * @property   Adyen B.V
 * @copyright  Copyright (c) 2014 Adyen BV (http://www.adyen.com)
 */
class Adyen_Payment_Model_Adyen_PayByMail extends Adyen_Payment_Model_Adyen_Abstract {

    protected $_code = 'adyen_pay_by_mail';
    protected $_formBlockType = 'adyen/form_payByMail';
    protected $_infoBlockType = 'adyen/info_payByMail';
    protected $_paymentMethod = 'pay_by_mail';
    protected $_canUseCheckout = true;
    protected $_canUseInternal = true;
    protected $_canUseForMultishipping = true;
    protected $_isInitializeNeeded = true;

    protected $_paymentMethodType = 'hpp';

    public function getPaymentMethodType() {
        return $this->_paymentMethodType;
    }

    /**
     * @var GUEST_ID , used when order is placed by guests
     */
    const GUEST_ID = 'customer_';

    public function __construct()
    {
        // check if this is adyen_cc payment method because this function is as well used for oneclick payments
        if($this->getCode() == "adyen_pay_by_mail") {
            $visible = Mage::getStoreConfig("payment/adyen_pay_by_mail/visible_type");
            if($visible == "backend") {
                $this->_canUseCheckout = false;
                $this->_canUseInternal = true;
            } else if($visible == "frontend") {
                $this->_canUseCheckout = true;
                $this->_canUseInternal = false;
            } else {
                $this->_canUseCheckout = true;
                $this->_canUseInternal = true;
            }
        }
        parent::__construct();
    }

    public function assignData($data)
    {

    }


    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus($this->_getConfigData('order_status'));


        $payment = $this->getInfoInstance();
        $payment->setLastTransId($this->getTransactionId())->setIsTransactionPending(true);

        // create payment link and add it to comment history and send to shopper
        $fields = $this->getFormFields();

        $url = $this->getFormUrl();
        $url .= '?' . http_build_query($fields, '', '&');

        $payment->setAdditionalInformation('payment_url', $url);
    }

    /**
     * @return mixed
     */
    public function getFormFields()
    {
        $this->_initOrder();
        /* @var $order Mage_Sales_Model_Order */
        $order             = $this->_order;
        $realOrderId       = $order->getRealOrderId();
        $orderCurrencyCode = $order->getOrderCurrencyCode();

        $billingCountryCode = (is_object($order->getBillingAddress()) && $order->getBillingAddress()->getCountry() != "") ?
            $order->getBillingAddress()->getCountry() :
            false ;

        $adyFields = Mage::helper('adyen_payment')->prepareFieldsForUrl(
            $orderCurrencyCode,
            $realOrderId,
            $order->getGrandTotal(),
            $order->getCustomerEmail(),
            $order->getCustomerId(),
            [],
            $order->getStoreId(),
            Mage::getStoreConfig('general/locale/code', $order->getStoreId()),
            $billingCountryCode
        );

        Mage::log($adyFields, self::DEBUG_LEVEL, 'adyen_http-request.log', true);

        return $adyFields;
    }

    /*
     * @desc The character escape function is called from the array_map function in _signRequestParams
     * $param $val
     * return string
     */
    protected function escapeString($val)
    {
        return str_replace(':','\\:',str_replace('\\','\\\\',$val));
    }

    public function getFormUrl()
    {
        $isConfigDemoMode = $this->getConfigDataDemoMode();
        switch ($isConfigDemoMode) {
            case true:
                $url = 'https://test.adyen.com/hpp/pay.shtml';
                break;
            default:
                $url = 'https://live.adyen.com/hpp/pay.shtml';
                break;
        }
        return $url;
    }
}