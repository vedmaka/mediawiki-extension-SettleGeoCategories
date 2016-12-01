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

	/**
	 * @param DatabaseUpdater $updater
	 */
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

	/**
	 * @param Parser $parser
	 */
	public static function onParserFirstCallInit( $parser )
	{
		$parser->setFunctionHook('settlecategories', 'SettleGeoCategories::tag', SFH_OBJECT_ARGS);
	}

}
