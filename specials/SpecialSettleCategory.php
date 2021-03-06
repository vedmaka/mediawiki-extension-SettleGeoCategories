<?php

class SpecialSettleCategory extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'Category', 'read' );
	}

	public function execute( $subPage ) {

		if( $subPage === null ) {
			$this->displayAllCategories();
		}else{

			try {
				$category = new SettleGeoCategory( (int) $subPage );
			}catch (Exception $e) {
				$this->displayRestrictionError();
			}

			$this->getOutput()->setPageTitle( $category->getTitleKey() );

			$pages = SettleGeoCategories::getPagesInCategory( $category->getId() );
			$mustachePages = array();
			foreach ($pages as $page) {
				$mustachePages[] = array(
					'title' => $page->getBaseText(),
					'link' => $page->getFullURL()
				);
			}

			$subCategories = array();
			foreach ( $category->getChildren() as $subCategory ) {
				$subCategories[] = array(
					'title' => $subCategory->getTitleKey(),
					'link' => SpecialPage::getTitleFor('Category')->getFullURL().'/'.$subCategory->getId()
				);
			}

			$data = array(
				'title' => $category->getTitleKey(),
				'id' => $category->getId(),
				'description' => $category->getDescription(),
				'pages' => $mustachePages,
				'subs' => $subCategories,
				'scope_text' => wfMessage('settlegeocategories-scope-'.$category->getGeoScope())->plain()
			);

			if( $category->getParentId() ) {
				try {
					$parent = new SettleGeoCategory( $category->getParentId() );
					$data['parent'] = array(
						'title' => $parent->getTitleKey(),
						'link' => SpecialPage::getTitleFor('Category')->getFullURL().'/'.$parent->getId()
					);
				}catch (Exception $e) {
					// Nothing
				}
			}

			$templater = new TemplateParser( dirname(__FILE__).'/../templates', true );
			$this->getOutput()->addHTML( $templater->processTemplate('category', $data) );

		}

	}

	private function displayAllCategories() {

	}


}