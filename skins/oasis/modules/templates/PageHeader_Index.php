<?php
$runNjord = ( !empty( $wg->EnableNjordExt ) && WikiaPageType::isMainPage() );
if ( $runNjord ) {
	// edit button with actions dropdown
	if ( !empty( $action ) ) {
		echo F::app()->renderView(
			'MenuButton',
			'Index',
			[ 'action' => $action, 'image' => $actionImage, 'dropdown' => $dropdown, 'name' => $actionName ]
		);
	}

	echo $curatedContentToolButton;
} else {
	?>
	<header id="WikiaPageHeader" class="WikiaPageHeader wikia-page-header">
		<div class="header-container">
			<div class="header-column header-title">
				<h1><?= !empty( $displaytitle ) ? $title : htmlspecialchars( $title ) ?></h1>
				<?php if ( !empty( $pageSubtitle ) ): ?>
					<h2><?= $pageSubtitle ?></h2>
				<? endif;
				if ( !empty( $subtitle ) ): ?>
					<div class="subtitle"><?= $subtitle ?></div>
				<? endif; ?>
			</div>
			<div class="header-column header-tally">
				<?php if ( !empty( $pageExists ) ) { ?>
					<div id="PageShareContainer" class="page-share-container">
						<?= F::app()->renderView( 'PageShare', 'Index' ); ?>
					</div>
				<?php } ?>
				<? if ( !is_null( $tallyMsg ) ): ?>
					<div class="tally"><?= $tallyMsg ?></div>
				<? endif; ?>
				<? // TODO remove after XW-2226 is done ?>
				<a href="/wiki/Special:CreatePage?flow=create-page-contribute-button" class="wikia-button createpage add-new-page-experiment-element">Add New Page</a>
				<? // TODO remove end ?>
			</div>
		</div>
		<?php
		// Temp for CommunityPageExperiment
		if ( !empty( $wg->EnableCommunityPageExperiment ) ) {
			echo Html::openElement( 'div', [ 'class' => 'header-buttons' ] );
		}

		// edit button with actions dropdown
		if ( !empty( $action ) ) {
			echo F::app()->renderView(
				'MenuButton',
				'Index',
				[ 'action' => $action, 'image' => $actionImage, 'dropdown' => $dropdown, 'name' => $actionName ]
			);
		}

		echo $curatedContentToolButton;

		// TODO: use PageHeaderIndexExtraButtons hook for these buttons
		// "Add a photo" button
		if ( !empty( $isSpecialImages ) && !empty( $wg->EnableUploads ) ) {
			echo Wikia::specialPageLink(
				'Upload',
				'oasis-add-photo',
				'wikia-button upphotos',
				'blank.gif',
				'oasis-add-photo-to-wiki',
				'sprite photo'
			);
		}

		// "Add a video" button
		if ( !empty( $isSpecialVideos ) && !empty( $wg->EnableUploads ) && $showAddVideoBtn ): ?>
			<a class="button addVideo" href="#" rel="tooltip" title="<?= wfMessage( 'related-videos-tooltip-add' )->escaped(); ?>">
				<img src="<?= wfBlankImgUrl(); ?>" class="sprite addRelatedVideo"/> <?= wfMessage( 'videos-add-video' )->escaped(); ?>
			</a>
		<? endif;

		?>

		<? // TODO remove after XW-2226 is done ?>
		<a href="/wiki/Special:CreatePage?flow=create-page-contribute-button" class="wikia-button createpage add-new-page-experiment-element"><img class="sprite new" src="<?=wfBlankImgUrl()?>"> Add New Page</a>
		<? // TODO remove end ?>
		<?

		// comments & like button
		if ( !$isWallEnabled ) {
			echo F::app()->renderView( 'CommentsLikes', 'Index', [ 'comments' => $comments ] );
		}

		foreach ( $extraButtons as $button ) {
			echo $button;
		}

		// Temp for CommunityPageExperiment
		if ( !empty( $wg->EnableCommunityPageExperiment ) ) {
			echo Html::closeElement( 'div' );
		}
		?>
	</header>
	<?php
}
?>
