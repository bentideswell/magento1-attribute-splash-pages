<?php
/**
 * @category    Fishpig
 * @package     Fishpig_AttributeSplash
 * @license     http://fishpig.co.uk/license.txt
 * @author      Ben Tideswell <help@fishpig.co.uk>
 */

/**
 * Magento, if you're listing, you should have already added
 * the 2 variables below to this model (and every model!!)
 */

class Fishpig_AttributeSplash_Addon_XmlSitemap_Model_Sitemap_Sitemap extends Mage_Sitemap_Model_Sitemap
{
	/**
	 * Event data
	 *
	 * @var string
	*/
	protected $_eventPrefix = 'sitemap_sitemap';
	protected $_eventObject = 'sitemap';
}
