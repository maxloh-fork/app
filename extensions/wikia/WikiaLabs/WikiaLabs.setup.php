<?php
# Alert the user that this is not a valid entry point to MediaWiki if they try to access the special pages file directly.
if (!defined('MEDIAWIKI')) {
	echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/MyExtension/MyExtension.php" );
EOT;
	exit( 1 );
}


$wgExtensionCredits['specialpage'][] = array(
	'name' => 'WikiaLabs',
	'author' => array( "Tomasz Odrobny", "Adrain 'ADi' Wieczorek" ),
	'url' => '',
	'description' => '',
);

$dir = dirname(__FILE__) . '/';

/**
 * classes
 */
$wgAutoloadClasses['WikiaLabsSpecial'] = $dir . 'WikiaLabsSpecial.class.php';
$wgAutoloadClasses['WikiaLabsModule'] = $dir . 'WikiaLabsModule.class.php';
$wgAutoloadClasses['WikiaLabs'] = $dir . 'WikiaLabs.class.php';
$wgAutoloadClasses['WikiaLabsProject'] = $dir . 'WikiaLabsProject.class.php';
$wgAutoloadClasses['WikiaLabsHelper'] = $dir . 'WikiaLabsHelper.class.php';

/**
 * special pages
 */
$wgSpecialPages['WikiaLabs'] = 'WikiaLabsSpecial';

/**
* message files
*/
$wgExtensionMessagesFiles['WikiaLabs'] = $dir . 'WikiaLabs.i18n.php';

/**
 * alias files
 */
$wgExtensionAliasesFiles['WikiaLabs'] = $dir . 'WikiaLabs.alias.php';

$wgExtensionFunctions[] = 'WikiaLabsSetup';

function WikiaLabsSetup() {

	/**
	 * @var WikiaApp
	 */
	$app = F::app();

	/**
	 * Factory config
	 */
	F::addClassConstructor( 'WikiaLabs', array( 'app' => $app ) );
	F::addClassConstructor( 'WikiaLabsProject', array( 'app' => $app, 'id' => 0 ) );

	/**
	 * hooks
	 */
	$app->registerHook('GetRailModuleSpecialPageList', 'WikiaLabs', 'onGetRailModuleSpecialPageList' );
	$app->registerHook('MyTools::getDefaultTools', 'WikiaLabs', 'onGetDefaultTools' );

	/*
	 * set global 
	*/
	$logTypes = $app->getGlobal('wgLogTypes');
	$logTypes[] = 'wikialabs';
	$app->setGlobal('wgLogTypes', $logTypes);

	$logHeaders = $app->getGlobal('wgLogHeaders');
	$logHeaders['wikialabs'] = 'wikialabs';
	$app->setGlobal('wgLogHeaders', $logHeaders);
}

/*
 * ajax function
 */
$wgAjaxExportList[] = 'WikiaLabsHelper::getProjectModal';
$wgAjaxExportList[] = 'WikiaLabsHelper::saveProject';
$wgAjaxExportList[] = 'WikiaLabsHelper::getImageUrlForEdit';
$wgAjaxExportList[] = 'WikiaLabsHelper::switchProject';
$wgAjaxExportList[] = 'WikiaLabsHelper::saveFeedback';
