<?php

/**
 * Hooks for SettleGeoCategories extension
 *
 * @file
 * @ingroup Extensions
 */
class SettleGeoCategoriesHooks
{

	public static function onExtensionLoad()
	{
		//TODO: add extension tables into $wgSharedTables array to prevent it from clone categories on lang wikis
	}

	public static function onNameOfHook()
	{
		
	}

	public static function onLoadExtensionSchemaUpdates( $updater )
	{
		$updater->addExtensionTable('settlegeocategories', dirname(__FILE__).'/schema/settlegeocategories.sql');
		$updater->addExtensionTable('settlegeocategories_links', dirname(__FILE__).'/schema/settlegeocategories_links.sql');
	}

	public static function onUnitTestsList( &$paths )
	{
		$paths[] = __DIR__ . '/tests/phpunit/';
		return true;
	}

	public static function onParserFirstCallInit( $parser )
	{
		$parser->setFunctionHook('settlecategories', 'SettleGeoCategories::tag', SFH_OBJECT_ARGS);
	}
	
	public static function updateDataBefore( $store, $semanticData )
	{
		if( !$semanticData ) {
			return true;
		}
		
		// TODO: to be implemented
		// https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties/blob/master/src/Annotator/ExtraPropertyAnnotator.php
		//$di = new SMWDIBlob('Test12345');
		//$semanticData->addPropertyValue( 'Category', $di );
	}
	
	public static function sfFormPrinterSetup( $formPrinter )
	{
		$formPrinter->registerInputType('SettleGeoCategoryInput');
		return true;
	}

}
