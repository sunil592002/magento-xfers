<?php


/**
 * Xfers Checkout Controller
 *
 */
class Xfers_XfersController extends Mage_Core_Controller_Front_Action
{
	const PARAM_NAME_REJECT_URL = 'reject_url';
	
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * When a customer chooses xfers on Checkout/Payment page
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setxfersQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('xfers/redirect')->toHtml());
        $session->unsQuoteId();
    }

    /**
     * When a customer cancels payment from xfers.
     */
    public function cancelAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getxfersQuoteId(true));
        
    	// cancel order
        if ($session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();                
                $comment = "You have canceled your order" ;
				$order->sendOrderUpdateEmail(true, $comment);	//for sending order email update to customer                
            }
        }
        $this->_redirect('checkout/cart');
     }

    /**
     * Where xfers returns.
     * xfers currently always returns the same code so there is little point
     * in attempting to process it.
     */
    public function successAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getxfersQuoteId(true));
        
        // Set the quote as inactive after returning from xfers
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
		
        //$order = Mage::getModel('sales/order');
        //$order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
    	
         //$order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
         //$order->save();
        
	    // Send a confirmation email to customer
        //if($order->getId()){
            //$order->sendNewOrderEmail();
        //}

        Mage::getSingleton('checkout/session')->unsQuoteId();


        $this->_redirect('checkout/onepage/success');
    }
    
    public function notifyAction()
    {
    	/*$session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getxfersQuoteId(true));*/
        
        //Mage::log("zzz".Mage::getModel('xfers/xfers')->getUrl());
        $verify_url = strpos(strtolower(Mage::getModel('xfers/xfers')->getUrl()), "sandbox") == false ? "https://www.xfers.io/api/v2/payments/validate/" : "https://sandbox.xfers.io/api/v2/payments/validate/";
        $data = $this->getRequest()->getParams();
        
        if ($data['status'] != "paid") {
                $this->getResponse()->setBody("Failed");
                return;
        }
        //Mage::log($data);

        // use key 'http' even if you send the request to https://...
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ),
        );
        //Mage::log("before");
        $context  = stream_context_create($options);
        $result = file_get_contents($verify_url, false, $context);
        //Mage::log("after");
        
        //Mage::log("aaaaa");
        //Mage::log($result);

        if ($result != "VERIFIED") {
                $this->getResponse()->setBody("Invalid");
                return;
        }
        
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($this->getRequest()->getParam('order_id'));

         $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true);
         $order->save();
         if(!$order->getEmailSent()){
                $order->sendNewOrderEmail();
                $historyItem = Mage::getResourceModel('sales/order_status_history_collection')
                    ->getUnnotifiedForInstance($order, Mage_Sales_Model_Order::HISTORY_ENTITY_NAME);
                //track history
                if ($historyItem) {
                    $historyItem->setIsCustomerNotified(1);
                    $historyItem->save();
                }
        }
         
         Mage::log("xfers order successfully processed! id: ".$this->getRequest()->getParam('order_id'));

    	// cancel order
        //if ($session->getLastRealOrderId()) {
            //$order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            //if ($order->getId()) {
                //$order->cancel()->save();
            //}
        //}
        $this->getResponse()->setBody("Ok");
    }

}
