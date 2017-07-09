<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
	
	$this->startSetup();

	try {
		Mage::getResourceModel('attributeSplash/group')->fixTables();
	}
	catch (Exception $e) {}
	
	try {
		Mage::getResourceModel('attributeSplash/group')->fixIndexes();
	}
	catch (Exception $e) {}
	
	$this->endSetup();
	