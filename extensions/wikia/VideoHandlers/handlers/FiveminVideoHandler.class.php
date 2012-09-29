<?php

class FiveminVideoHandler extends VideoHandler {
	
	protected $apiName = 'FiveminApiWrapper';
	protected static $urlTemplate = 'http://www.5min.com/Embeded/$1';
	protected static $providerDetailUrlTemplate = 'http://www.5min.com/Video/$1';
	protected static $providerHomeUrl = 'http://www.5min.com/';
	protected static $autoplayParam = "autostart=true";
		
	public function getEmbed( $articleId, $width, $autoplay = false, $isAjax = false, $postOnload = false ) {
		$height =  $this->getHeight( $width );
		$sAutoPlay = $autoplay  ? 'true' : 'false';
		$url = $this->getEmbedUrl( $autoplay );
		$url .= '/&autostart='.$sAutoPlay;
		$embedCode = <<<EOT
<embed src='{$url}' type='application/x-shockwave-flash' width="{$width}" height="{$height}" allowfullscreen='true' allowScriptAccess='always'></embed>
EOT;
		return $embedCode;
	}

}