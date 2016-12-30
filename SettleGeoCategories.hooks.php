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

	/**
	 * @param \SMW\PropertyRegistry $registry
	 */
	public static function initProperties( $registry )
	{
		// Category name
		$registry->registerProperty('___GCT', '_txt', 'Geocategory', true, true );
		// Category ID
		$registry->registerProperty('___GCTID', '_num', 'GeocategoryId', true, true );
		// Category Scope
		$registry->registerProperty('___GCTSC', '_num', 'GeocategoryScope', true, true );
	}

	/**
	 * @param SMWSQLStore3 $store
	 * @param \SMW\SemanticData $semanticData
	 *
	 * @return bool
	 */
	public static function updateDataBefore( $store, $semanticData )
	{
		if( !$semanticData ) {
			return true;
		}

		$title = $semanticData->getSubject()->getTitle();
		if( !$title || !$title->exists() ) {
			return true;
		}

		$categories = SettleGeoCategories::getPageCategories( $title );
		if( !$categories || !count($categories) ) {
			return true;
		}

		/** @var SettleGeoCategory $category */
		foreach ( $categories as $category ) {

			// Category name
			$propertyDI = new \SMW\DIProperty( '___GCT' );
			$dataItem  = new SMWDIString( $category->getTitleKey() );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );

			// Category ID
			$propertyDI = new \SMW\DIProperty( '___GCTID' );
			$dataItem = new SMWDINumber( $category->getId() );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );

			// Category Scope
			$propertyDI = new \SMW\DIProperty( '___GCTSC' );
			$dataItem = new SMWDINumber( $category->getGeoScope() );
			$semanticData->addPropertyObjectValue( $propertyDI, $dataItem );

		}

		// https://github.com/SemanticMediaWiki/SemanticExtraSpecialProperties/blob/master/src/Annotator/ExtraPropertyAnnotator.php

	}
	
	public static function sfFormPrinterSetup( $formPrinter )
	{
		$formPrinter->registerInputType('SettleGeoCategoryInput');
		return true;
	}

	public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, Content $content = null, LogEntry $logEntry )
	{
		SettleGeoCategories::clearPageCategories( $id );
	}

}
