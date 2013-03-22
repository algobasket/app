<li class="result">

	<?php
		$trackingData = 'class="result-link" data-pos="' . $pos . '" data-event="search_click_match"';
	?>

    <a href="<?= $resultSet->getHeader( 'url' ) ?>" title="<?= $resultSet->getHeader('wikititle'); ?>" <?= $thumbTracking
	    ?>>
	    <img src="<?= $imageURL ?>" alt="<?= $resultSet->getHeader('title'); ?>" class="wikiPromoteThumbnail" />
	</a>
    <div class="result-description">

        <h1>
            <a href="<?= $resultSet->getHeader( 'url' ) ?>" <?=$trackingData;?> ><?= $resultSet->getHeader
			('wikititle'); ?></a>
        </h1>

        <p class="hub subtle"><?= strtoupper( $resultSet->getHeader( 'hub' ) ); ?></p>
        <p class="description"><?= $resultSet->getHeader('description'); ?></p>

        <ul class="wiki-statistics subtle">
            <li><?= $pagesMsg ?></li>
            <li><?= $imgMsg ?></li>
            <li><?= $videoMsg ?></li>
        </ul>
    </div>
</li>