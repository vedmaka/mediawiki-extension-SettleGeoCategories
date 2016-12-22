<?php

class SpecialSettleCategorySearch extends SpecialPage {

	private $templater;

	public function __construct() {
		parent::__construct( 'SettleCategorySearch', 'read' );
	}

	public function execute( $subPage ) {

		$this->templater = new TemplateParser( dirname(__FILE__).'/../templates/', true );

		if( $subPage === null ) {

		}else{

		}

	}

}