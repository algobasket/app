<?php

/**
 * Translation Statistics
 */

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Translation Statistics',
	'author' => "[http://www.wikia.com/wiki/User:TOR Lucas 'TOR' Garczewski]",
	'descriptionmsg' => 'transstats-desc',
	'description' => 'Provides statistics for translations done using the Translate extension',
);

$dir = dirname( __FILE__ ) . '/';

// The core
$wgAutoloadClasses['MessageGroupStatistics'] = $dir . 'MessageGroupStatistics.php';
$wgAutoloadClasses['SpecialTranslationCount'] = $dir . 'SpecialTranslationCount.body.php';
$wgAutoloadClasses['SpecialNewLanguageStats'] = $dir . 'SpecialLanguageStats.php';
$wgAutoloadClasses['SpecialNewTranslationStats'] = $dir . 'SpecialTranslationStats.php';
$wgAutoloadClasses['SpecialGroupStats'] = $dir . 'SpecialGroupStats.body.php';


// Attach hooks
$wgHooks['ArticleSaveComplete'][] = 'MessageGroupStats::invalidateStats';

// i18n
$wgExtensionMessagesFiles['TranslationStatistics'] = $dir . 'TranslationStatistics.i18n.php';

// Special pages
$wgSpecialPages['TranslationCount'] = 'SpecialTranslationCount';
$wgSpecialPages['NewLanguageStats'] = 'SpecialNewLanguageStats';
$wgSpecialPages['NewTranslationStats'] = 'SpecialNewTranslationStats';
$wgSpecialPages['GroupStats'] = 'SpecialGroupStats';
