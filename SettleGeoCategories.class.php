<?php

/**
 * Class for SettleGeoCategories extension
 *
 * @file
 * @ingroup Extensions
 */
class SettleGeoCategories
{

	const GEO_SCOPE_DEFAULT     = 2; //TODO: remove
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
	 *
	 * @return array
	 */
	public static function getPageCategories( $title ) {
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select( self::$table, 'id_to', array('id_from' => $title->getArticleID()) );
		$categories = array();
		if( $result ) {
			while( $row = $result->fetchRow() ) {
				try {
					$categories[] = new SettleGeoCategory( $row['id_to'] );
				}catch (Exception $e) {
					continue;
				}
			}
		}
		return $categories;
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
	 * @param int $category_id
	 *
	 * @return Title[]
	 */
	public static function getPagesInCategory( $category_id ) {
		$pages = array();
		$dbr = wfGetDB(DB_SLAVE);
		$result = $dbr->select( self::$table, '*', array(
			'id_to' => $category_id
		));
		while( $row = $result->fetchRow() ) {
			$pages[] = Title::newFromID( $row['id_from'] );
		}
		return $pages;
	}

	/**
	 * Parser function that adds categories to the page using their ids
	 * 
	 * @param Parser $parser
	 * @param $frame
	 * @param array $args
	 *
	 * @return bool|string
	 */
	public static function tag( $parser, $frame, $args ) {

		if( !$parser->getTitle() || !$parser->getTitle()->exists() ) {
			return true;
		}

		// Clear out all categories from the page
		self::clearPageCategories( $parser->getTitle() );

		if( !count($args) ) {
			return false;
		}

		$categories_ids = array_shift($args);
		$categories_ids = explode(',', $categories_ids);

		if( !count($categories_ids) ) {
			return false;
		}

		$catLink = SpecialPage::getTitleFor('Category')->getFullURL();

		$car = array();
		foreach ($categories_ids as $cid) {
			//$cat = SettleGeoCategory::newFromTitleKey( $cid );
			//if( !$cat || $cat === null ) {
			//	continue;
			//}
			if( $cid === null || $cid == "" ) {
				continue;
			}

			try {
				$cat = new SettleGeoCategory( $cid );
			}catch (Exception $e) {
				continue;
			}

			if( !$cat || $cat->getId() === null ) {
				continue;
			}
			
			self::addPageToCategory( $parser->getTitle(), $cid );
			
			$car[$cid] = $cat->getTitleKey();
		}

		$html = '';
		foreach ($car as $cK => $cV) {
			$html .= $parser->insertStripItem( '<span class="label label-default"><a href="'.$catLink.'/'.$cK.'">'.$cV.'</a></span>' );
		}

		// Print out categories names
		return array(
			$html,
			'markerType' => 'nowiki'
		);
	}

}
