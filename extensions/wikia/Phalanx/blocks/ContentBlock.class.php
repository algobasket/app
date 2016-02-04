<?php

/**
 * ContentBlock
 *
 * This filter blocks an edit from being saved,
 * if its content or the summary given
 * matches any of the blacklisted phrases.
 */

class ContentBlock {
	private static $whitelist = null;

	public static function onEditFilter( $editpage ) {
		global $wgOut, $wgTitle;
		wfProfileIn( __METHOD__ );

		//allow blocked words to be added to whitelist
		if ($wgTitle->getPrefixedText() == 'MediaWiki:Spam-whitelist') {
			wfProfileOut( __METHOD__ );
			return true;
		}

		// here we get only the phrases for blocking in summaries...
		$blocksData = Phalanx::getFromFilter( Phalanx::TYPE_SUMMARY );
		$summary = $editpage->summary;
		if ( !empty($blocksData) && $summary != '' ) {
			$summary = self::applyWhitelist($summary);

			$blockData = null;
			$result = Phalanx::findBlocked($summary, $blocksData, true, $blockData);
			if ( $result['blocked'] ) {

				$wgOut->setPageTitle( wfMsg( 'spamprotectiontitle' ) );
				$wgOut->setRobotPolicy( 'noindex,nofollow' );
				$wgOut->setArticleRelated( false );
				$wgOut->addHTML( '<div id="spamprotected_summary">' );
				$wgOut->addWikiMsg( 'spamprotectiontext' );
				$wgOut->addHTML( '<p>( Call #3 )</p>' );
				$wgOut->addWikiMsg( 'spamprotectionmatch', "<nowiki>{$result['msg']}</nowiki>" );
				$wgOut->addWikiMsg( 'phalanx-content-spam-summary' );

				$wgOut->returnToMain( false, $wgTitle );
				$wgOut->addHTML( '</div>' );
				Wikia::log(__METHOD__, __LINE__, "Block '{$result['msg']}' blocked '$summary'.");
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		$blocksData = Phalanx::getFromFilter( Phalanx::TYPE_CONTENT );
		$textbox = $editpage->textbox1;
		if ( !empty($blocksData) && $textbox != '' ) {
			$textbox = self::applyWhitelist($textbox);

			$blockData = null;
			$result = Phalanx::findBlocked($textbox, $blocksData, true, $blockData);
			if ( $result['blocked'] ) {
				$editpage->spamPageWithContent( $result['msg'] );
				Wikia::log(__METHOD__, __LINE__, "Block '{$result['msg']}' blocked '$textbox'.");
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		//no spam detected
		wfProfileOut( __METHOD__ );
		return true;
	}

	/*
	 * onAbortMove
	 *
	 * Aborts a page move if the summary given matches
	 * any blacklisted phrase.
	 */
	public static function onAbortMove( $oldtitle, $newtitle, $user, &$error ) {
		global $wgRequest;
		wfProfileIn( __METHOD__ );

		$reason = $wgRequest->getText( 'wpReason' );
		$blocksData = Phalanx::getFromFilter( Phalanx::TYPE_SUMMARY );
		if ( !empty($blocksData) && $reason != '' ) {
			$reason = self::applyWhitelist($reason);

			$blockData = null;
			$result = Phalanx::findBlocked($reason, $blocksData, true, $blockData);
			if ( $result['blocked'] ) {
				$error .= wfMsgExt( 'phalanx-title-move-summary', 'parseinline' );
				$error .= wfMsgExt( 'spamprotectionmatch', 'parseinline', "<nowiki>{$result['msg']}</nowiki>" );
				Wikia::log(__METHOD__, __LINE__, "Block '{$result['msg']}' blocked '$reason'.");
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/*
	 * genericContentCheck
	 *
	 * @author Macbre
	 *
	 * Generic content checking to be used by extensions
	 */
	static public function genericContentCheck( $content ) {
		wfProfileIn( __METHOD__ );

		$blocksData = Phalanx::getFromFilter( Phalanx::TYPE_CONTENT );
		if ( !empty($blocksData) && $content != '' ) {
			$content = self::applyWhitelist($content);

			$blockData = null;
			$result = Phalanx::findBlocked($content, $blocksData, true, $blockData);
			if ( $result['blocked'] ) {
				Wikia::log(__METHOD__, __LINE__, "Block '{$result['msg']}' blocked '$content'.");
				wfProfileOut( __METHOD__ );
				return false;
			}
		}

		wfProfileOut( __METHOD__ );
		return true;
	}

	/*
	 * applyWhitelist
	 *
	 * @author Marooned <marooned at wikia-inc.com>
	 *
	 * @param $
	 * @return
	 */
	private static function applyWhitelist($text) {
		wfProfileIn( __METHOD__ );

		//TODO: add short memcache here?
		if (is_null(self::$whitelist)) {
			$whitelist = wfMsgForContent('Spam-whitelist');
			if (wfEmptyMsg('Spam-whitelist', $whitelist)) {
				wfProfileOut( __METHOD__ );
				return $text;
			}
			$whitelist = array_filter(array_map('trim', preg_replace('/#.*$/', '', explode("\n", $whitelist))));

			foreach ($whitelist as $regex) {
				$regex = str_replace('/', '\/', preg_replace('|\\\*/|', '/', $regex));
				$regex = "/https?:\/\/+[a-z0-9_.-]*$regex/i";
				wfSuppressWarnings();
				$regexValid = preg_match($regex, '');
				wfRestoreWarnings();
				if ($regexValid === false) {
					continue;
				}
				//escape slashes uses as regex delimiter
				self::$whitelist[] = $regex;
			}

			Wikia::log(__METHOD__, __LINE__, count(self::$whitelist) . ' whitelist entries loaded.');
		}

		if (!empty(self::$whitelist)) {
			$text = preg_replace(self::$whitelist, '', $text);
		}

		wfProfileOut( __METHOD__ );
		return $text;
	}
	
	# hooks added after Phalanx redesign - this hooks is used in CreateWiki extension
	public static function onCheckContent( $text, &$blockedKeyword ) {
		wfProfileIn( __METHOD__ );
		
		$keywords = array();
		$filters = Phalanx::getFromFilter( Phalanx::TYPE_CONTENT );
		foreach( $filters as $filter ) {
			$result = Phalanx::isBlocked( $text, $filter );
			if($result['blocked']) {
				$keywords[] = $result['msg'];
			}
		}

		if ( count($keywords) > 0 ) {
			$blockedKeyword = '';
			for ($i = 0; $i < count($keywords); $i++) {
				if($i != 0) {
					$blockedKeyword .= ', ';
				}
				$blockedKeyword .= $keywords[$i];
			}
		}
		
		wfProfileOut( __METHOD__ );
		return true;
	}
	
	public static function onSpamFilterCheck($text, $type, &$blockData) {
		wfProfileIn( __METHOD__ );
		
		if (!empty($text)) {
			$filters = Phalanx::getFromFilter($type);

			foreach ($filters as $filter) {
				$result = Phalanx::isBlocked($text, $filter);

				if ($result['blocked']) {
					wfProfileOut(__METHOD__);
					return false;
				}
			}
		}
		wfProfileOut( __METHOD__ );
		
		return true;
	}
	
	public static function onEditPhalanxBlock( &$data ) {
		wfProfileIn( __METHOD__ );
		unset( $data['id'] );
		$data['id'] = PhalanxHelper::save( $data, false /* do not rebuild the cache */ );
		wfProfileOut( __METHOD__ );
		return true;
	}
	
	public static function onDeletePhalanxBlock( $block_id ) {
		wfProfileIn( __METHOD__ );
		$ret = PhalanxHelper::removeFilter( $block_id, false /* do not touch Phalanx's cache */ );
		wfProfileOut( __METHOD__ );
		return ( $ret['error'] == true ) ? false : true ;
	}
}