<?php

class SpecialSettleCategorySearch extends SpecialPage {

    /** @var TemplateParser */
	private $templater;

	private $topCategoriesIdsFlat;

	public function __construct() {
		parent::__construct( 'SettleCategorySearch', 'read' );
	}

	public function execute( $subPage ) {

		$this->getOutput()->addModules('ext.settlegeocategories.search');

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
			'categories' => array(),
			'backlink' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL(),
			'have_results' => false
		);

		$country = null;
		try {
			$earth = new MenaraSolutions\Geographer\Earth();
			$country = $earth->findOne( array('geonamesCode' => $path[0]) );
			$country = $country->getShortName();
		}catch (Exception $e) {
			$country = '';
		}

		$data['country_text'] = $country;
		$this->getOutput()->setPageTitle( $country );

	    // Fetch categories for pages within a country
		$query = SphinxStore::getInstance()->getQuery();

		// Fetch list of all top-level categories
		$topCategoriesIds = array();
		$this->topCategoriesIdsFlat = array();
		$topCategories = SettleGeoCategories::getAllCategories();
		// Pass all sub-categories ids from top-level category for easier building of Sphinx query
		foreach ($topCategories as $topCategory) {
			$item = array(
				'title' => $topCategory->getTitleKey(),
				'id' => $topCategory->getId(),
				'deep_ids' => $topCategory->recursiveIds()
			);
			// TODO: is not very effective, but still possible to use
			$sql = "SELECT id, ( IN( properties.geocodes, {$path[0]} ) AND IN( properties.geocategoryid, ".implode( ',', $item['deep_ids'] )." ) ) AS p FROM ".SphinxStore::getInstance()->getIndex()." WHERE p = 1;";
			$result = $query->query( $sql )->execute();
			if( $result->count() ) {
				$topCategoriesIds[] = $topCategory->getId();
				$this->topCategoriesIdsFlat = array_merge( $this->topCategoriesIdsFlat, $item['deep_ids'] );
			}
		}

		if( count($topCategoriesIds) ) {

			$data['have_results'] = true;

			// Imported from SettleGeoSearch
			// Fetch
			$pl1 = ", (";
			$pl1 .= "( IN( properties.geocodes, {$path[0]} ) ) AND properties.geocategory IS NOT NULL AND IN( properties.geocategoryid, " . implode( ',', $topCategoriesIds ) . " )";
			$pl1 .= " )";
			$pl1 .= " AS p";
			$pl2 = " WHERE p=1";
			$pl3 = "";

			$sql = "SELECT *{$pl1} FROM " . SphinxStore::getInstance()->getIndex() . "{$pl3}{$pl2} LIMIT 0,10000 OPTION ranker=matchany;";

			$result = $query->query( $sql )->execute();

			$categories = array();

			if ( $result->count() ) {
				foreach ( $result as $r ) {

					$properties                                    = json_decode( $r['properties'], true );
					$categories[ $properties['geocategoryid'][0] ] = $properties['geocategory'][0];
				}
			}

			$categories_html = '';

			// Prepare for mustache
			foreach ( $categories as $id => $category ) {
				$categories_html .= $this->displayCategoryRecursive( new SettleGeoCategory( $id ) );
			}

			$data['categories_html'] = $categories_html;

		}

		$html = $this->templater->processTemplate( 'search_categories', $data );
		$this->getOutput()->addHTML( $html );

    }

	private function displayMain() {

		$this->getOutput()->setPageTitle('Search for categories');

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

		$query = SphinxStore::getInstance()->getQuery();
        $sortedByChar = array();

        foreach ($countries as $country) {

	        $result = $query->query('SELECT id, IN( properties.country_code, '.$country['geonamesCode'].' ) AS p FROM wiki_rt WHERE p = 1')->execute();
	        //if( !$result->count() ) {
		        // Do not include countries without articles
		    //    continue;
	        //}

	        $country['articles_count'] = $result->count() ? $result->count() : false;

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

        //TODO: ?
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
	private function displayCategoryRecursive( $category )
	{
		$html = '';

		// prepare data for Mustache template
		$data = array(
			'id' => $category->getId(),
			'articles' => array(),
			'title' => $category->getTitleKey(),
			'url' => SpecialPage::getTitleFor('Category')->getFullURL().'/'.$category->getId(),
			'innerHtml' => '',
			'articles_count' => false
		);

		$articles = SettleGeoCategories::getPagesInCategory( $category->getId() );
		if( count($articles) ) {
			foreach ($articles as $article) {
				$data['articles'][] = array(
					'title' => $article->getBaseText(),
					'url' => $article->getFullURL()
				);
			}
			//$data['articles_count'] = count($articles);
		}

		if( $category->getChildren() ) {
			foreach ( $category->getChildren() as $child ) {
				if( !$child->countArticles() ) {
					continue;
				}
				$data['innerHtml'] .= $this->displayCategoryRecursive( $child );
			}
		}

		$html = $this->templater->processTemplate('search_categories_category', $data);

		return $html;
	}

}