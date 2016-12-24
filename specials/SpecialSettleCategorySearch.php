<?php

class SpecialSettleCategorySearch extends SpecialPage {

    /** @var TemplateParser */
	private $templater;

	public function __construct() {
		parent::__construct( 'SettleCategorySearch', 'read' );
	}

	public function execute( $subPage ) {

		$this->templater = new TemplateParser( dirname(__FILE__).'/../templates/', true );

		if( $subPage === null ) {
		    // Display index list of countries
            $this->displayMain();
		}else{
            // Country specified, parse path
            $path = explode('/', $subPage);
            $this->displayCategories( $path );
		}

	}

	private function displayCategories( $path ) {

		$data = array(
			'categories' => array()
		);

	    // Fetch categories for pages within country
		$query = SphinxStore::getInstance()->getQuery();

		// Fetch list of all top-level categories
		$topCategoriesIds = array();
		$topCategoriesIdsFlat = array();
		$topCategories = SettleGeoCategories::getAllCategories();
		// Pass all sub-categories ids from top-level category for easier building of Sphinx query
		foreach ($topCategories as $topCategory) {
			$item = array(
				'title' => $topCategory->getTitleKey(),
				'id' => $topCategory->getId(),
				'deep_ids' => $topCategory->recursiveIds()
			);
			// TODO: is not very effective, but still possible to use
			$sql = "SELECT id, IN( properties.geocategoryid, ".implode( ',', $item['deep_ids'] )." ) AS p FROM ".SphinxStore::getInstance()->getIndex()." WHERE p = 1;";
			$result = $query->query( $sql )->execute();
			if( $result->count() ) {
				$topCategoriesIds[] = $item;
				$topCategoriesIdsFlat = array_merge( $topCategoriesIdsFlat, $item['deep_ids'] );
			}
		}

		// Imported from SettleGeoSearch
		// Fetch
		$pl1 = ", (";
		$pl1 .= "( IN( properties.geocodes, {$path[0]} ) ) AND properties.geocategory IS NOT NULL AND IN( properties.geocategoryid, ".implode(',', $topCategoriesIdsFlat)." )";
		$pl1 .= " )";
		$pl1 .= " AS p";
		$pl2 = " WHERE p=1";
		$pl3 = "";

		$sql = "SELECT *{$pl1} FROM ".SphinxStore::getInstance()->getIndex()."{$pl3}{$pl2} LIMIT 0,10000 OPTION ranker=matchany;";

		$result = $query->query( $sql )->execute();

		$categories = array();

		if( $result->count() ) {
			foreach ( $result as $r ) {

				$properties = json_decode( $r['properties'], true );
				$categories[ $properties['geocategoryid'][0] ] = $properties['geocategory'][0];

			}
		}

		$categories_html = '';

		// Prepare for mustache
		foreach ($categories as $id => $category) {
			/*$data['categories'][] = array(
				'id' => $id,
				'title' => $category
			);*/
			$categories_html .= $this->displayCategoryRecursive( new SettleGeoCategory( $id ) );
		}

		$data['categories_html'] = '<ul>' . $categories_html . '</ul>';

		$html = $this->templater->processTemplate( 'search_categories', $data );
		$this->getOutput()->addHTML( $html );

    }

	private function displayMain() {

	    // Fetch countries
        $countries = SettleGeoTaxonomy::getInstance()->getEntities(
            SettleGeoTaxonomy::TYPE_COUNTRY,
            null,
            $this->getLanguage()->getCode()
        );

        $data = array(
            'countries' => array(),
            'url_prefix' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL()
        );

        $sortedByChar = array();

        foreach ($countries as $country) {
            $name = $country['name'];
            $firstChar = mb_strtoupper( mb_substr( $name, 0, 1 ) );
            //TODO: fix unicode characters
            //$firstChar = iconv(mb_detect_encoding($firstChar, mb_detect_order(), true), "UTF-8", $firstChar);
            //$firstChar = mb_convert_encoding ($firstChar, 'US-ASCII');
            if( !array_key_exists($firstChar, $sortedByChar) ) {
                $sortedByChar[ $firstChar ] = array(
                    'title' => $firstChar,
                    'items' => array()
                );
            }
            $country['url_prefix'] = $data['url_prefix'];
            $sortedByChar[ $firstChar ]['items'][] = $country;
        }

        //TODO:
        array_pop( $sortedByChar );

        foreach ($sortedByChar as $s) {
            $data['countries'][] = $s;
        }

        $html = $this->templater->processTemplate( 'search', $data );
        $this->getOutput()->addHTML( $html );

    }

	/**
	 * @param SettleGeoCategory $category
	 *
	 * @return string
	 */
	private function displayCategoryRecursive( $category, $tagWrap = 'ul', $tagList = 'li' )
	{
		$html = '';
		$html .= '<'.$tagList.'><a href="#">'.$category->getTitleKey().'</a>';

		$articles = SettleGeoCategories::getPagesInCategory( $category->getId() );
		if( count($articles) ) {
			$html .= '<ul>';
			foreach ($articles as $article) {
				$html .= '<li><a href="#">'.$article->getBaseText().'</a>';
			}
			$html .= '</ul>';
		}

		if( $category->getChildren() ) {
			$html .= '<'.$tagWrap.'>';
			foreach ( $category->getChildren() as $child ) {
				$html .= $this->displayCategoryRecursive( $child );
			}
			$html .= '</'.$tagWrap.'>';
		}

		$html .= '</'.$tagList.'>';

		return $html;
	}

}