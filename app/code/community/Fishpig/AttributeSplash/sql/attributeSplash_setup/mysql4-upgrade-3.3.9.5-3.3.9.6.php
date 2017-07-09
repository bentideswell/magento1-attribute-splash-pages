<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */
	
	$this->startSetup();

	try {
		$conn = $this->getConnection();
		$oldTable = $this->getTable('attributesplash_group');
		$newTable = $this->getTable('attributeSplash/group');
		
		$oldGroupId = (int)$conn->fetchOne($conn->select()->from($oldTable, 'group_id')->limit(1));
		$newGroupId = (int)$conn->fetchOne($conn->select()->from($newTable, 'group_id')->limit(1));

		if ($oldGroupId && $newGroupId === 0) {
			$copyTables = array(
				$oldTable => $newTable,
				$this->getTable('attributesplash_group_store') => $this->getTable('attributeSplash/group_store'),
			);
			
			foreach($copyTables as $old => $new) {
				$conn->query(sprintf(
					'INSERT INTO %s (%s)', 
					$new, 
					(string)$conn->select()->from($old, '*')
				));
			}
		}
		
		Mage::getResourceModel('attributeSplash/group')->reindexAll();
	}
	catch (Exception $e) {
		Mage::logException($e);
	}
	
	$this->endSetup();
	