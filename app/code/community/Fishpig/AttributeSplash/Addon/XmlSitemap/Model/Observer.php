<?php
/**
 * @category    Fishpig
 * @package    Fishpig_AttributeSplash
 * @license      http://fishpig.co.uk/license.txt
 * @author       Ben Tideswell <ben@fishpig.co.uk>
 */

class Fishpig_AttributeSplash_Addon_XmlSitemap_Model_Observer
{
	/**
	 * Inject links into the Magento topmenu
	 *
	 * @param Varien_Data_Tree_Node $topmenu
	 * @return bool
	 */	
	public function injectXmlSitemapLinksObserver(Varien_Event_Observer $observer)
	{
		$sitemap = $observer
			->getEvent()
				->getSitemap();

		$appEmulation = Mage::getSingleton('core/app_emulation');
		$initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($sitemap->getStoreId());

		$sitemapFilename = Mage::getBaseDir() . '/' . ltrim($sitemap->getSitemapPath() . $sitemap->getSitemapFilename(), '/' . DS);
		
		if (!file_exists($sitemapFilename)) {
			return $this;
		}
		
		$xml = trim(file_get_contents($sitemapFilename));
		
		// Trim off trailing </urlset> tag so we can add more
		$xml = substr($xml, 0, -strlen('</urlset>'));

		$methods = array(
			'page' => '_getSplashPages',
			'group' => '_getSplashGroups',
		);

		foreach($methods as $type => $method) {
			if (($objects = $this->$method()) !== false) {

				$changeFreq = Mage::getStoreConfig('attributeSplash/' . $type . '_xml_sitemap/frequency');
				$priority = (float)Mage::getStoreConfig('attributeSplash/' . $type . '_xml_sitemap/priority');
				
				foreach($objects as $object) {
					$xml .= sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%s</priority></url>', $object->getUrl(), $changeFreq, $priority);
				}
			}
		}
		
		$xml .= '</urlset>';
		
		file_put_contents($sitemapFilename, $xml);

		$appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);

		return $this;
	}

	/**
	 * Retrieve a collection of Splash Pages
	 *
	 * @return Fishpig_AttributeSplash_Model_Resource_Page_Collection
	 */
	protected function _getSplashPages()
	{
		if (!Mage::getStoreConfigFlag('attributeSplash/page_xml_sitemap/enabled')) {
			return false;
		}

		$pages = Mage::getResourceModel('attributeSplash/page_collection')
			->addStoreIdFilter(Mage::app()->getStore())
			->addIsEnabledFilter()
			->load();

		if (count($pages) === 0) {
			return false;
		}
		
		return $pages;
	}

	/**
	 * Retrieve a collection of Splash Groups
	 *
	 * @return Fishpig_AttributeSplash_Model_Resource_Group_Collection
	 */
	protected function _getSplashGroups()
	{
		if (!Mage::getStoreConfigFlag('attributeSplash/group_xml_sitemap/enabled')) {
			return false;
		}

		$pages = Mage::getResourceModel('attributeSplash/group_collection')
			->addStoreIdFilter(Mage::app()->getStore())
			->addIsEnabledFilter()
			->load();

		if (count($pages) === 0) {
			return false;
		}
		
		return $pages;
	}
	
	/**
	 * Inject the tab for QC into the Splash dashboard
	 *
	 * @param Varien_Event_Observer $observer
	 * @return $this
	 */
	public function injectXmlSitemapTabObserver(Varien_Event_Observer $observer)
	{
		$layout = Mage::getSingleton('core/layout');

		$observer->getEvent()
			->getTabs()
				->addTab('xmlsitemap', array(
					'label'     => Mage::helper('catalog')->__('XML Sitemap'),
					'content'   => $layout->createBlock('core/text')
						->setText('<p>The XML Sitemap add-on is installed. Splash pages and groups will be automatically added to your Magento sitemap XML file.</p>')
						->toHtml(),
				));

		return $this;
	}

}
