<?php


/**
 * Xfers Controller
 *
 */
class Xfers_Adminhtml_XfersController extends Mage_Adminhtml_Controller_Action
{
	public function updateAction()
	{
		//retrieve order details
		$order_id = $this->getRequest()->getParam('order_id');
		$order_object = Mage::getSingleton('sales/order');
		$order_object->load($order_id);
		$increment_id = $order_object->getIncrementId()	;
		$store_id = $order_object->getData('store_id');
		$payment_method = $order_object->getPayment()->getMethodInstance();
		
		//retrieve plugin parameter values
		$api_url = $payment_method->getConfigData('api_url',$store_id);
		$api_key = $payment_method->getConfigData('api_key',$store_id);
		$api_secret = $payment_method->getConfigData('api_secret',$store_id);
		
		//order prefix handler
		$order_ref = $increment_id;
		
		//validate
		$error_msg = '';
		if($api_url == '')		$error_msg .= '- API URL is not set. <br/>';
		if($api_key == '')	$error_msg .= '- API Key is not set. <br/>';
		if($api_secret == '')	$error_msg .= '- API Secret is not set. <br/>';
		
		if($error_msg != ''){
			//display module parameter errors
			echo '<b>MODULE SETUP ERROR:</b><br/>' . $error_msg ;
			echo '<br/>';
		}else{
		}
			
		echo '<a href="' . Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view/', array('order_id'=>$order_object->getId())) . '">[ Go Back To Order Page ]</a>';
	}
	
	public function cancelAction()
	{
		//retrieve order details
		$order_id = $this->getRequest()->getParam('order_id');
		$order_object = Mage::getSingleton('sales/order');
		$order_object->load($order_id);
		$increment_id = $order_object->getIncrementId()	;
		$store_id = $order_object->getData('store_id');
		$payment_method = $order_object->getPayment()->getMethodInstance();
		
		$order_object->cancel()->save();
		$comment = "Your Order Has Been Canceled" ;
		$order_object->sendOrderUpdateEmail(true, $comment);	//for sending order email update to customer
		
		echo 'Order Has Been Cancelled. <br/><br/>';
			
		echo '<a href="' . Mage::helper('adminhtml')->getUrl('adminhtml/sales_order/view/', array('order_id'=>$order_object->getId())) . '">[ Go Back To Order Page ]</a>';
	}
}
