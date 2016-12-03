<?php

/**
 * Class for SettleGeoCategories extension
 *
 * @file
 * @ingroup Extensions
 */
class SettleGeoCategories
{

	const GEO_SCOPE_DEFAULT     = 0; //TODO: remove
	const GEO_SCOPE_COUNTRY     = 0;
	const GEO_SCOPE_STATE       = 1;
	const GEO_SCOPE_CITY        = 2;

	static $table = 'settlegeocategories_links';

	/**
	 * @return SettleGeoCategory[]
	 */
	public static function getAllCategories() {

		$categories = array();
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select( SettleGeoCategory::$table, 'id', array('parent_id' => null) );
		while( $row = $result->fetchRow() ) {
			$categories[] = new SettleGeoCategory($row['id']);
		}

		return $categories;

	}

	/**
	 * @param Title $title
	 */
	public static function clearPageCategories( $title ) {
		$dbw = wfGetDB(DB_MASTER);
		$dbw->delete( self::$table, array('id_from' => $title->getArticleID()) );
	}

	/**
	 * @param Title $title
	 * @param int $category_id
	 */
	public static function addPageToCategory( $title, $category_id ) {
		// Search category by id
		$category = new SettleGeoCategory( (int)$category_id);
		$id = $category->getId();
		// Add to db
		$dbw = wfGetDB(DB_MASTER);
		$dbw->insert( self::$table, array(
			'id_from' => $title->getArticleID(),
			'id_to' => $id
		));
	}

	/**
	 * Parser function that adds categories to the page using their titles
	 * 
	 * @param Parser $parser
	 * @param $frame
	 * @param array $args
	 *
	 * @return bool|string
	 */
	public static function tag( $parser, $frame, $args ) {
		if( !count($args) ) {
			return false;
		}
		$categories_ids = array_shift($args);
		$categories_ids = explode(',', $categories_ids);
		if( !count($categories_ids) ) {
			return false;
		}
		$car = array();
		self::clearPageCategories( $parser->getTitle() );
		foreach ($categories_ids as $cid) {
			$cat = SettleGeoCategory::newFromTitleKey( $cid );
			if( !$cat || $cat === null ) {
				continue;
			}
			self::addPageToCategory( $parser->getTitle(), $cat->getId() );
			$car[] = $cat->getTitleKey();
		}
		return implode(',', $car);
	}

}
