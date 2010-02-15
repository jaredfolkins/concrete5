<?

defined('C5_EXECUTE') or die(_("Access Denied."));

class Marketplace {
	
	public function isConnected() {
		$token = Config::get('MARKETPLACE_SITE_TOKEN');
		return $token != '';
	}
	
	public function generateSiteToken() {
		$fh = Loader::helper('file');
		$token = $fh->getContents(MARKETPLACE_URL_CONNECT_TOKEN_NEW);
		return $token;	
	}
	
	public function getSitePageURL() {
		$token = Config::get('MARKETPLACE_SITE_URL_TOKEN');
		return MARKETPLACE_BASE_URL_SITE_PAGE . '/' . $token;
	}

	public static function downloadRemoteFile($file) {
		$fh = Loader::helper('file');
		$pkg = $fh->getContents($file);
		if (empty($pkg)) {
			return Package::E_PACKAGE_DOWNLOAD;
		}

		$file = time();
		// Use the same method as the Archive library to build a temporary file name.
		$tmpFile = $fh->getTemporaryDirectory() . '/' . $file . '.zip';
		$fp = fopen($tmpFile, "wb");
		if ($fp) {
			fwrite($fp, $pkg);
			fclose($fp);
		} else {
			return Package::E_PACKAGE_SAVE;
		}
		
		return $file;
	}
	
	/** 
	 * Runs through all packages on the marketplace, sees if they're installed here, and updates the available version number for them
	 */
	public static function checkPackageUpdates() {
		Loader::model('system_notification');
		$items = Marketplace::getAvailableMarketplaceItems(false);
		foreach($items as $i) {
			$p = Package::getByHandle($i->getHandle());
			$p->updateAvailableVersionNumber($i->getVersion());
			SystemNotification::add(SystemNotification::SN_TYPE_ADDON_UPDATE, t('%s version %s is now available.', $i->getName(), $i->getVersion()), t('Read more at <a target="_blank" href="%s">%s</a>', $i->getRemoteURL(), $i->getRemoteURL()), '', View::url('/dashboard/install', 'update'));
		}
	}

	public function getAvailableMarketplaceItems($filterInstalled=true) {
		Loader::model('marketplace_remote_item');
		if (!function_exists('mb_detect_encoding')) {
			return array();
		}
		
		if (!is_array($addons)) {
			$fh = Loader::helper('file'); 
			if (!$fh) return array();

			// Retrieve the URL contents 
			$csToken = Config::get('MARKETPLACE_SITE_TOKEN');
			$url = MARKETPLACE_PURCHASES_LIST_WS."?csToken={$csToken}";
			$xml = $fh->getContents($url);

			$addons=array();
			if( $xml || strlen($xml) ) {
				// Parse the returned XML file
				$enc = mb_detect_encoding($xml);
				$xml = mb_convert_encoding($xml, 'UTF-8', $enc); 
				
				try {
					libxml_use_internal_errors(true);
					$xmlObj = new SimpleXMLElement($xml);
					foreach($xmlObj->addon as $addon){
						$mi = new MarketplaceRemoteItem();
						$mi->loadFromXML($addon);
						$mi->isPurchase(1);
						$remoteCID = $mi->getRemoteCollectionID();
						if (!empty($remoteCID)) {
							$addons[$mi->getHandle()] = $mi;
						}
					}
				} catch (Exception $e) {}
			}

		}

		if ($filterInstalled && is_array($addons)) {
			Loader::model('package');
			$handles = Package::getInstalledHandles();
			if (is_array($handles)) {
				$adlist = array();
				foreach($addons as $key=>$ad) {
					if (!in_array($ad->getHandle(), $handles)) {
						$adlist[$key] = $ad;
					}
				}
				$addons = $adlist;
			}
		}

		return $addons;
	}

}

?>