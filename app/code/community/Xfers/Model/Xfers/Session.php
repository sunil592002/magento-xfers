<?php


class Xfers_Model_Xfers_Session extends Mage_Core_Model_Session_Abstract
{
    public function __construct()
    {
        $this->init('xfers');
    }
}
