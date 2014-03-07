<?php

/**
 * Class AlbumScraper
 *
 * Scrape Album page from Lyrics API
 */
class AlbumScraper extends BaseScraper {

	public function processArticle( Article $article ) {
		$albumData = [
			'article_id' => $article->getId(),
		];
		$albumData = array_merge( $albumData, $this->getHeader( $article ) );
		return array_merge( $albumData, $this->getFooter( $article ) );
	}

	protected function getHeader( Article $article ) {
		return $this->getTemplateValues( 'Album', $article->getContent() );
	}

	protected function getFooter( Article $article ) {
		return $this->getTemplateValues( 'AlbumFooter', $article->getContent() );
	}

	public function getDataMap() {
		return [
			'available' => 'available',
			'article_id' => 'article_id',
			'Cover' => 'image',
			'year' => 'year',
			'Album' => 'name',
			'iTunes' => 'itunes',
/* These fields are also captured but not needed now
			'Artist' => 'artist',
			'Length' => 'length',
			'Genre' => 'genres',
			'Wikipedia' => 'wikipedia',
			'romanizedAlbum' => 'romanized_name',
			'asin' => 'asin',
			'allmusic' => 'allmusic',
			'discogs' => 'discogs',
			'musicbrainz' => 'musicbrainz',
			'download' => 'download',
			'songs' => 'songs',
*/
		];
	}

} 