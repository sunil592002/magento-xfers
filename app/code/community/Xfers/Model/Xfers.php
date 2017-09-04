<?php


/**
 * Xfers payment model
 *
 */
class Xfers_Model_xfers extends Mage_Payment_Model_Method_Abstract
{
    const API_URL_TEST = 'https://sandbox.xfers.io/api/v2/payments';

    protected $_code  = 'xfers';
    protected $_formBlockType = 'xfers_block_form';
    protected $_allowCurrencyCode = array('SGD');
    
    public function getUrl()
    {
    	$url = $this->getConfigData('api_url');
    	
    	if(!$url)
    	{
    		$url = self::API_URL_TEST;
    	}
    	
    	return $url;
    }
    
    /**
     * Get session namespace
     *
     */
    public function getSession()
    {
        return Mage::getSingleton('xfers/xfers_session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
	public function getCheckoutFormFields()
	{
		$order = Mage::getSingleton('sales/order');
		$order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
		
		$grandTotalAmount = sprintf('%.2f', $order->getBaseGrandTotal());  
		
		$order_id = $this->getCheckout()->getLastRealOrderId();
		
		$memberPay_email = '';
		if (Mage::app()->isInstalled() && Mage::getSingleton('customer/session')->isLoggedIn()) {            
			$memberPay_email = Mage::getSingleton('customer/session')->getCustomer()->getEmail();
        }
		
		
		$currency = 'SGD';
		$api_key = $this->getConfigData('api_key');
		$api_secret = $this->getConfigData('api_secret');
				
		$items = Array();
			
		$i=0;
		$subTotal = 0;
		foreach ( $order->getAllItems() as $item ) {
			$i++;
			
			$quantity = round($item['qty_ordered'], 0);
			$price = round($item['price'],2);
			
			$items[ 'item_name_' . $i ] 	=  $item['name'];
			$items[ 'item_quantity_' . $i ] 	= $quantity;
			$items[ 'item_price_' . $i ] 		= $price;
			
			$subTotal += ($quantity*$price);
		}
		
		$shipment = $grandTotalAmount - $subTotal;		
		$signature = $this->generatePaymentSecureHash($api_key,$api_secret,$order_id,$grandTotalAmount,$currency);
		
		$fields = array_merge(array(
			'return_url'				=> Mage::getUrl('xfers/xfers/success'),
			'cancel_url'					=> Mage::getUrl('xfers/xfers/cancel'),
			'notify_url'					=> Mage::getUrl('xfers/xfers/notify'),
			'api_key'					=> $api_key,
            'api_secret'					=> $api_secret,
            'order_id'					=> $order_id,
            'shipping'					=> $shipment,
            'tax'					=> '0',
            'total_amount'					=> $grandTotalAmount,
            'currency'					=> $currency,
            'refundable'					=> 'true',
            'user_email'					=> $memberPay_email,
            'signature'					=> $signature,
            'reseller_api_key'                                  => "4714ec80e9d149488988fb8c8bfd7b83"
				
		),$items
		);

		// Run through fields and replace any occurrences of & with the word 
		// 'and', as having an ampersand present will conflict with the HTTP
		// request.
		$filtered_fields = array();
        foreach ($fields as $k=>$v) {
            $value = str_replace("&","and",$v);
            $filtered_fields[$k] =  $value;
        }
        return $filtered_fields;
	}

	public function generatePaymentSecureHash($apiKey, $apiSecret, $orderId, $amount, $currency) {
			$buffer = $apiKey . $apiSecret . $orderId . $amount . $currency;
			return sha1($buffer);
	}
	
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('xfers/xfers_form', $name)
            ->setMethod('xfers')
            ->setPayment($this->getPayment())
            ->setTemplate('xfers/form.phtml');

        return $block;
    }
		
    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {

    }
	
    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('xfers/xfers/redirect');
    }
	
}
