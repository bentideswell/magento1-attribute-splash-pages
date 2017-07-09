<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Model_Resource_Group extends Fishpig_AttributeSplash_Model_Resource_Abstract
{
	/**
	 *
	**/
	public function _construct()
	{
		$this->_init('attributeSplash/group', 'group_id');
	}
	
	/**
	 * Retrieve select object for load object data
	 * This gets the default select, plus the attribute id and code
	 *
	 * @param   string $field
	 * @param   mixed $value
	 * @return  Zend_Db_Select
	*/
	protected function _getLoadSelect($field, $value, $object)
	{
		return parent::_getLoadSelect($field, $value, $object)
			->join(
				array('_attribute_table' => $this->getTable('eav/attribute')),
				'`_attribute_table`.`attribute_id` = `main_table`.`attribute_id`',
				array('attribute_code', 'frontend_label')
			);
	}
	
	/**
	 * Retrieve the store table for the group model
	 *
	 * @return string
	 */
	public function getStoreTable()
	{
		return $this->getTable('attributeSplash/group_store');
	}

	/**
	 * Retrieve the name of the unique field
	 *
	 * @return string
	 */
	public function getUniqueFieldName()
	{
		return 'attribute_id';	
	}
	
	/**
	 * Retrieve the attribute model for the group
	 *
	 * @param Fishpig_AttributeSplash_Model_Group $group
	 * @return Mage_Eav_Model_Entity_Attribute
	 */
	public function getAttributeModel(Fishpig_AttributeSplash_Model_Group $group)
	{
		return $group->getAttributeId()
			? Mage::getModel('eav/entity_attribute')->load($group->getAttributeId())
			: false;
	}

	/**
	 * Retrieve a collection of splash pages that belong to this group
	 *
	 * @param Fishpig_AttributeSplash_Model_Group $group
	 * @return Fishpig_AttributeSplash_Model_Resource_Page_Collection
	 */
	public function getSplashPages(Fishpig_AttributeSplash_Model_Group $group)
	{
		$pages = Mage::getResourceModel('attributeSplash/page_collection')
			->addIsEnabledFilter();

		if ($group->getStoreId() > 0) {
			$pages->addStoreFilter($group->getStoreId());
		}
		else if (($storeId = Mage::app()->getStore()->getId()) > 0) {
			$pages->addStoreFilter($storeId);
		}
		
		 return $pages->addAttributeIdFilter($group->getAttributeId());
	}

	/**
	 * Get the index table name
	 *
	 * @return string
	 */
	public function getIndexTable()
	{
		return $this->getTable('attributeSplash/group_index');
	}
	
	/**
	 * Determine whether the group can be deleted
	 *
	 * @param Fishpig_AttributeSplash_Model_Group $group
	 * @return bool
	 */
	public function canDelete(Fishpig_AttributeSplash_Model_Group $group)
	{
		if (!$group->isGlobal()) {
			return true;
		}

		$select = $this->_getReadAdapter()->select()
			->from(array('main_table' => $this->getTable('eav/attribute_option')), 'option_id')
			->join(
				array('_splash' => $this->getTable('attributeSplash/page')),
				'_splash.option_id = main_table.option_id'
			)
			->where('main_table.attribute_id=?', $group->getAttributeModel()->getId())
			->limit(1);
			
			
		return $this->_getReadAdapter()->fetchOne($select) === false;
	}
	
	public function fixTables()
	{
		$this->_installTable();
	}
	
	/**
	 * If tables aren't installed, try and install them
	 *
	 * @return $this
	**/
	protected function _installTable()
	{
		try {
			$this->_getReadAdapter()->fetchOne(
				$this->_getReadAdapter()
					->select()
						->from($this->getMainTable(), $this->getIdFieldName())
						->limit(1)
			);
		}
		catch (Exception $e) {
			$groupTable = $this->getTable('group');
			$groupStoreTable = $this->getTable('group_store');
			$groupIndexTable = $this->getTable('group_index');

			$sql = "
				CREATE TABLE IF NOT EXISTS {$groupTable} 
				(
					`group_id` 					int(11) unsigned NOT NULL auto_increment,
					`attribute_id` 				smallint(5) unsigned NOT NULL default 0,
					`display_name` 			varchar(255) NOT NULL default '',
					`short_description` 		TEXT NOT NULL default '',
					`description`					TEXT NOT NULL default '',
					`url_key` 						varchar(180) NOT NULL default '',
					`page_title` 					varchar(255) NOT NULL default '',
					`meta_description` 		varchar(255) NOT NULL default '',
					`meta_keywords`			varchar(255) NOT NULL default '',
					`display_mode` 			varchar(40) NOT NULL default 'PRODUCTS',
					`cms_block` 					int(11) unsigned NOT NULL default 0,
					`layout_update_xml` 	TEXT NOT NULL default '',
					`page_layout` 				varchar(32) default NULL,
					`include_in_menu` 		int(1) unsigned NOT NULL default 1,
					`is_enabled` 				int(1) unsigned NOT NULL default 1,
					`created_at` 					TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
					`updated_at` 				TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
					
					PRIMARY KEY 				(`group_id`)
				)
				ENGINE=InnoDB
				DEFAULT CHARSET=utf8 
				COMMENT='AttributeSplash: Group';
	
				CREATE TABLE IF NOT EXISTS {$groupStoreTable} (
					`group_id` 						int(11) unsigned NOT NULL auto_increment,
					`store_id` 							smallint(5) unsigned NOT NULL default 0,
					
					PRIMARY KEY 					(`group_id`, `store_id`)
					
				)
				ENGINE=InnoDB 
				DEFAULT CHARSET=utf8 
				COMMENT='AttributeSplash: Group / Store';
				
				
				CREATE TABLE IF NOT EXISTS {$groupIndexTable} (
					`group_id` 						int(11) unsigned NOT NULL,
					`store_id`						 	smallint(5) unsigned NOT NULL,
					
					PRIMARY KEY 					(`group_id`, `store_id`)
				) 
				ENGINE=InnoDB 
				DEFAULT CHARSET=utf8 
				COMMENT='AttributeSplash: Group Index';
			";

			try {
				$this->_getWriteAdapter()->query($sql);
				$this->fixIndexes();
			}
			catch (Exception $e) {
				Mage::logException($e);
				
				throw $e;
			}
		}
	}
	
	public function fixIndexes()
	{
		$tables = array(
			'group',
			'group_index',
			'group_store',
		);

		$db = $this->_getWriteAdapter();

		try {
			foreach($tables as $table) {
				$indexesSql = 'SHOW INDEX FROM ' . $this->getTable('attributeSplash/' . $table);

				if ($indexes = $db->fetchAll($indexesSql)) {
					foreach($indexes as $index) {
						if ($index['Key_name'] === 'PRIMARY') {
							continue;
						}
						
						try {
							$db->query(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $index['Table'], $index['Key_name']));					
							$db->query(sprintf('DROP INDEX `%s` ON %s', $index['Key_name'], $index['Table']));
						}
						catch (Exception $e) {}
					}
				}
			}

			try {
				$db->query('ALTER TABLE ' . $this->getTable('attributeSplash/group') . ' DROP COLUMN `store_id`');
			}
			catch (Exception $e) {}
			
			// Splash Group: Attribute ID
			$this->_addForeginKey(
				'FK_FIX_SPLASH_GROUP_ATTRIBUTE_ID',
				$this->getTable('attributeSplash/group'),
				'attribute_id',
				$this->getTable('eav/attribute')
			);

			// Splash Group Store: Group ID
			$this->_addForeginKey(
				'FK_FIX_SPLASH_GROUP_STORE_GROUP_ID',
				$this->getTable('attributeSplash/group_store'),
				'group_id',
				$this->getTable('attributeSplash/group')
			);
			
			// Splash Group Store: Store ID
			$this->_addForeginKey(
				'FK_FIX_SPLASH_GROUP_STORE_STORE_ID',
				$this->getTable('attributeSplash/group_store'),
				'store_id',
				$this->getTable('core/store')
			);
			
//				ALTER TABLE {$groupStoreTable} ADD UNIQUE `UNIQUE_ATTRIBUTE_ID_STORE_ID_SPLASH_GROUP2_STORE` (`group_id`,`store_id`);

			// Splash Group Store: Group ID
			$this->_addForeginKey(
				'FK_FIX_SPLASH_GROUP_INDEX_GROUP_ID',
				$this->getTable('attributeSplash/group_index'),
				'group_id',
				$this->getTable('attributeSplash/group')
			);
			
			// Splash Group Store: Store ID
			$this->_addForeginKey(
				'FK_FIX_SPLASH_GROUP_INDEX_STORE_ID',
				$this->getTable('attributeSplash/group_index'),
				'store_id',
				$this->getTable('core/store')
			);
		}
		catch (Exception $e) {
			echo 'End: ' . $e->getMessage() . '<pre>' . $e->getTraceAsString();
			exit;
		}
	}
	
	protected function _addForeginKey($keyName, $target, $tfield, $source, $sfield = null)
	{
		try {
			$this->_getWriteAdapter()->query(sprintf(
				'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE CASCADE ON UPDATE CASCADE;', 
				$target,
				$keyName,
				$tfield,
				$source,
				$sfield ? $sfield : $tfield
			));
		}
		catch (Exception $e) {
#			echo $e->getMessage() . '<br/><br/>';
		}
	}
}
