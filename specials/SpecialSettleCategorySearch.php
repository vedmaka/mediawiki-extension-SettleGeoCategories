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
            $this->displayCategories();
		}

	}

	private function displayCategories() {

	    // Fetch categories for pages within country


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

}