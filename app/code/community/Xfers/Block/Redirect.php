<?php


class Xfers_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {
        $xfers = Mage::getModel('xfers/xfers');

        $form = new Varien_Data_Form();
        $form->setAction($xfers->getUrl())
            ->setId('xfers_checkout')
            ->setName('xfers_checkout')
            ->setMethod('post')
            ->setUseContainer(true);
       
        foreach ($xfers->getCheckoutFormFields() as $field=>$value) {
            $form->addField($field, 'hidden', array('name'=>$field, 'value'=>$value));
        }
         $html = '<html><body>';
         $html.= $this->__('You will be redirected to the payment gateway in a few seconds.');
         $html.= $form->toHtml();
         $html.= '<script type="text/javascript">document.getElementById("xfers_checkout").submit();</script>';
         $html.= '</body></html>';
        return $html;
    }
}
