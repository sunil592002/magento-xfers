<?php


class Xfers_Block_Form extends Mage_Payment_Block_Form
{
    protected function _construct()
    {
        $this->setTemplate('xfers/form.phtml');
        parent::_construct();
    }
}
