<?php

class SpecialSettleCategorySearch extends SpecialPage {

    /** @var TemplateParser */
	private $templater;

	public function __construct() {
		parent::__construct( 'SettleCategorySearch', 'read' );
	}

	public function execute( $subPage ) {

		$this->getOutput()->addModules('ext.settlegeocategories.search');
		$this->getOutput()->addModules('skins.settlein.animate.standalone');

		$this->templater = new TemplateParser( dirname(__FILE__).'/../templates/', true );

		if( $subPage === null ) {
		    // Display index list of countries
            $this->displayMain();
		}else{
            // Country specified, parse path
            $path = explode('/', $subPage);
            // Display main categories list
            if( count($path) === 1 ) {
	            $this->displayMainCategories( $path[0] );
            }
            // Display sub-category
            if( count($path) === 2 ) {
            	$this->displaySubCategories( $path[0], $path[1] );
            }
            // Display articles
			if( count($path) === 3 ) {
            	$this->displayArticles( $path[0], $path[1], $path[2] );
			}
		}

	}

	private function displayArticles( $c, $category, $subcategory ) {

		$data = array(
			'top_text' => wfMessage('settlegeocategories-articles-categories-intro')->plain(),
			'categories' => array(),
			'breads' => array(),
			'have_results' => false,
			'layout_articles' => true
		);

		$country = null;
		try {
			$earth = new MenaraSolutions\Geographer\Earth();
			$country = $earth->findOne( array('geonamesCode' => $c) );
			$countryText = $country->getShortName();
		}catch (Exception $e) {
			die('Something gone wrong, please contact site administrator.');
		}

		$sCategory = new SettleGeoCategory($category);
		$sSubCategory = new SettleGeoCategory($subcategory);

		$data['breads'] = array(
			array(
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL(),
				'title' => 'Countries',
				'active' => false
			),
			array(
				'title' => $countryText,
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$country['geonamesCode'],
				'active' => false
			),
			array(
				'title' => $sCategory->getTitleKey(),
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$country['geonamesCode'].'/'.$sCategory->getId(),
				'active' => false
			),
			array(
				'title' => $sSubCategory->getTitleKey(),
				'active' => true
			)
		);

		$query = SphinxStore::getInstance()->getQuery();
		$sql = "SELECT id, properties, ( IN( properties.geocodes, {$c} ) AND IN( properties.geocategoryid, {$subcategory}) ) AS p FROM ".SphinxStore::getInstance()->getIndex()." WHERE p = 1;";
		$result = $query->query( $sql )->execute();
		if( $result->count() ) {
			foreach ($result as $r) {

				$p = Title::newFromID( $r['id'] );
				$properties = json_decode($r['properties'], true);

				$data['categories'][] = array(
					'title' => $p->getBaseText(),
					'link' => $p->getFullURL(),
					'icon' => 'file',
					'desc' => array_key_exists('short_description', $properties) ? $properties['short_description'][0] : false,
					'location_text' => SettleGeoSearch::formatLocationBreadcrumbs( $properties )
				);

				$data['have_results'] = true;
			}
		}

		$this->getOutput()->setPageTitle( $countryText .' - '.$sCategory->getTitleKey().' - '.$sSubCategory->getTitleKey() );

		$html = $this->templater->processTemplate( 'search_categories', $data );
		$this->getOutput()->addHTML( $html );

	}

	private function displaySubCategories( $c, $category ) {

		$data = array(
			'top_text' => wfMessage('settlegeocategories-sub-categories-intro')->plain(),
			'categories' => array(),
			'breads' => array(),
			'have_results' => false
		);

		$country = null;
		try {
			$earth = new MenaraSolutions\Geographer\Earth();
			$country = $earth->findOne( array('geonamesCode' => $c) );
			$countryText = $country->getShortName();
		}catch (Exception $e) {
			die('Something gone wrong, please contact site administrator.');
		}

		$sCategory = new SettleGeoCategory($category);

		$data['breads'] = array(
			array(
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL(),
				'title' => 'Countries',
				'active' => false
			),
			array(
				'title' => $countryText,
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$country['geonamesCode'],
				'active' => false
			),
			array(
				'title' => $sCategory->getTitleKey(),
				'active' => true
			)
		);

		foreach ($sCategory->getChildren() as $child) {
			// TODO: blocking for hierarchy more than 3 levels deep
			if( !$child->countArticles() ) {
				continue;
			}
			$data['categories'][] = array(
				'title' => $child->getTitleKey(),
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$country['geonamesCode'].'/'.$category.'/'.$child->getId(),
				'icon' => 'tags'
			);
			$data['have_results'] = true;
		}

		$this->getOutput()->setPageTitle( $countryText .' - '.$sCategory->getTitleKey() );

		$html = $this->templater->processTemplate( 'search_categories', $data );
		$this->getOutput()->addHTML( $html );

	}

	private function displayMainCategories( $countryId ) {

		$data = array(
			'top_text' => wfMessage('settlegeocategories-main-categories-intro')->plain(),
			'categories' => array(),
			'breads' => array(),
			'have_results' => false
		);

		$country = null;
		try {
			$earth = new MenaraSolutions\Geographer\Earth();
			$country = $earth->findOne( array('geonamesCode' => $countryId) );
			$country = $country->getShortName();
		}catch (Exception $e) {
			die('Something gone wrong, please contact site administrator.');
		}


		$data['breads'] = array(
			array(
				'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL(),
				'title' => 'Countries',
				'active' => false
			),
			array(
				'title' => $country,
				'active' => true
			)
		);

		$this->getOutput()->setPageTitle( $country );

		$query = SphinxStore::getInstance()->getQuery();

		// Fetch list of all top-level categories
		//$topCategoriesWithArticles = array();
		$topCategories = SettleGeoCategories::getAllCategories();
		// Pass all sub-categories ids from top-level category for easier building of Sphinx query
		foreach ($topCategories as $topCategory) {

			$item = array(
				'title' => $topCategory->getTitleKey(),
				'id' => $topCategory->getId(),
				'deep_ids' => $topCategory->recursiveIds()
			);

			// Check this category and all its sub-categories ids for articles existence
			$sql = "SELECT id, ( IN( properties.geocodes, {$countryId} ) AND IN( properties.geocategoryid, "
			       .implode( ',', $item['deep_ids'] )." ) ) AS p FROM ".SphinxStore::getInstance()->getIndex()." WHERE p = 1;";

			$result = $query->query( $sql )->execute();

			if( $result->count() ) {
				// If any, store top-category:
				//$topCategoriesWithArticles[] = $topCategory;
				$data['categories'][] = array(
					'title' => $topCategory->getTitleKey(),
					'id' => $topCategory->getId(),
					'link' => SpecialPage::getTitleFor('SettleCategorySearch')->getFullURL().'/'.$countryId.'/'.$topCategory->getId(),
					'icon' => 'tag'
				);
				$data['have_results'] = true;
			}

		}

		$html = $this->templater->processTemplate( 'search_categories', $data );
		$this->getOutput()->addHTML( $html );

	}

	/**
	 * @param $path
	 * @deprecated
	 */
	private function _displayCategories( $path ) {

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
		$topCategoriesWithArticles = array();
		$topCategories = SettleGeoCategories::getAllCategories();
		// Pass all sub-categories ids from top-level category for easier building of Sphinx query
		foreach ($topCategories as $topCategory) {

			$item = array(
				'title' => $topCategory->getTitleKey(),
				'id' => $topCategory->getId(),
				'deep_ids' => $topCategory->recursiveIds()
			);

			// Check this category and all its sub-categories ids for articles existence
			$sql = "SELECT id, ( IN( properties.geocodes, {$path[0]} ) AND IN( properties.geocategoryid, "
			       .implode( ',', $item['deep_ids'] )." ) ) AS p FROM ".SphinxStore::getInstance()->getIndex()." WHERE p = 1;";

			$result = $query->query( $sql )->execute();

			if( $result->count() ) {
				// If any, store top-category:
				$topCategoriesWithArticles[] = $topCategory;
			}

		}

		if( count($topCategoriesWithArticles) ) {

			$data['have_results'] = true;

			$categories_html = '';
			foreach ( $topCategoriesWithArticles as $category ) {
				$categories_html .= $this->displayCategoryRecursive( $category );
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

	        $result = $query->query('SELECT id, IN( properties.country_code, '.$country['geonamesCode'].' ) AS p FROM '.SphinxStore::getInstance()->getIndex().' WHERE p = 1')->execute();

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