<?php

class GametrailersVideoHandler extends VideoHandler {
	protected $apiName = 'GametrailersApiWrapper';
	protected static $urlTemplate = 'http://media.mtvnservices.com/mgid:moses:video:gametrailers.com:$1';
	protected static $providerDetailUrlTemplate = 'http://www.gametrailers.com/video/play/$1';
	protected static $providerHomeUrl = 'http://www.gametrailers.com/';
	protected static $autoplayParam = "autoplay=true";
	
	public function getEmbed($articleId, $width, $autoplay = false, $isAjax = false, $postOnload = false) {
		$height = $this->getHeight($width);
		$url = $this->getEmbedUrl();
		$autoplayStr = $autoplay ? 'true' : 'false';

		$html = <<<EOT
<embed src="$url" width="$width" height="$height" type="application/x-shockwave-flash" allowFullScreen="true" allowScriptAccess="always" base="." flashVars="autoplay=$autoplayStr"></embed>
EOT;
		
		return $html;
	}

}