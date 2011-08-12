<section id="hub-top-contributors"<?php echo $hidetopeditors ?> class="module">
	<h1>
		<?= wfMsg('hub-topusers-header', $data['title']) ?>
	</h1>
	<?php
		if( $data['is_manager'] ) {
			if( isset($data['var_feeds']['topeditors'] )) {
	?>
	<span class="toggleContainer" id="topeditors">[<a class="toggleFeed" href="#"><?php echo wfMsg('hide') ?></a>]</span>
	<?php
			} else {
	?>
	<span class="toggleContainer" id="topeditors">[<a class="toggleFeed" href="#"><?php echo wfMsg('unhide') ?></a>]</span>
	<?php
			}
		}
	?>

	<ul>
		<?php foreach( $data['topEditors'] as $value ): ?>
		<li>
			<?php if (isset($value['avatar'])) echo $value['avatar']; ?>
			<div class="editor-details">
				<a href="<?php echo empty($value['userpage']) ? '#':$value['userpage'] ?>" class="username h2"><?php echo $value['username'];	?></a>
				<div class="edit-count">
					<?= wfMsgExt('hub-topusers-editpoints-nonformatted', 'parseinline', $wgLang->formatNum( $value['all_count'] ) ) ?>
				</div>
			</div>
		</li>
		<?php endforeach; ?>
	</ul>
</section><!-- END: #hub-blogs -->
