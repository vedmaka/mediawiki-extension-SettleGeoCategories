<?php

class SettleGeoCategoriesApi extends ApiBase {

	private $parsedParams = array();
	private $finalData = array(
		'status' => 'success'
	);

	public function execute() {

		$this->parsedParams = $this->extractRequestParams();

		$method = $this->parsedParams['method'];
		switch ($method) {
			case 'read':
				$this->methodRead();
				break;
			case 'write':
				$this->methodWrite();
				break;
			case 'move':
				$this->methodMove();
				break;
			case 'delete':
				$this->methodDelete();
				break;
			case 'add':
				$this->methodAdd();
				break;
		}

		$this->getResult()->addValue( null, 'response', $this->finalData );

	}

	protected function methodAdd() {

		if( !$this->getUser()->isAllowed('geocategories') ) {
			$this->dieUsage('No enough permissions', 500);
		}

		if( !array_key_exists('text', $this->parsedParams) || empty($this->parsedParams['text']) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		if( !array_key_exists('scope', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		$category = new SettleGeoCategory();
		$category->setTitleKey( $this->parsedParams['text'] );
		$category->setGeoScope( $this->parsedParams['scope'] );
		$category->save();

		$this->finalData['id'] = $category->getId();
		$this->finalData['status'] = 'success';

	}

	protected function methodDelete() {

		if( !$this->getUser()->isAllowed('geocategories') ) {
			$this->dieUsage('No enough permissions', 500);
		}

		if( !array_key_exists('category', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		$category = new SettleGeoCategory( $this->parsedParams['category'] );
		if( !$category ) {
			$this->dieUsage('Missing category', 500);
		}

		$category->delete();

		$this->finalData['status'] = 'success';

	}

	protected function methodMove() {

		if( !$this->getUser()->isAllowed('geocategories') ) {
			$this->dieUsage('No enough permissions', 500);
		}

		if( !array_key_exists('category', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		if( !array_key_exists('parent', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		$category = new SettleGeoCategory( $this->parsedParams['category'] );
		if( !$category ) {
			$this->dieUsage('Missing category', 500);
		}

		if( $this->parsedParams['parent'] > -1 ) {
			$newParent = new SettleGeoCategory( $this->parsedParams['parent'] );
			if ( ! $newParent ) {
				$this->dieUsage( 'Missing category parent', 500 );
			}
			$category->setParentId( $newParent->getId() );
		}else{
			$category->setParentId( null );
		}


		$category->save();

		$this->finalData['status'] = 'success';

	}

	protected function methodWrite() {

		if( !$this->getUser()->isAllowed('geocategories') ) {
			$this->dieUsage('No enough permissions', 500);
		}

		if( !array_key_exists('category', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		if( !array_key_exists('text', $this->parsedParams) || empty($this->parsedParams['text']) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		if( !array_key_exists('scope', $this->parsedParams) ) {
			$this->dieUsage('Missing required parameters.', 500);
		}

		$category = new SettleGeoCategory( $this->parsedParams['category'] );
		if( !$category ) {
			$this->dieUsage('Missing category', 500);
		}

		$category->setTitleKey( $this->parsedParams['text'] );
		$category->setGeoScope( $this->parsedParams['scope'] );
		$category->save();

		$this->finalData['status'] = 'success';

	}

	protected function methodRead() {

		$categoriesObjects = SettleGeoCategories::getAllCategories();
		$categoriesArray = array();
		foreach ($categoriesObjects as $categoriesObject) {
			$categoriesArray[] = array(
				'id' => $categoriesObject->getId(),
				'text' => $categoriesObject->getTitleKey(),
				'scope' => $categoriesObject->getGeoScope(),
				'state' => array(
					'opened' => 1
				),
				'li_attr' => array(
					'flag-scope' => $categoriesObject->getGeoScope()
				),
				'children' => $this->fetchCategoryRecursive( $categoriesObject )
			);
		}

		$this->finalData = $categoriesArray;

	}

	/**
	 * @param SettleGeoCategory $category
	 *
	 * @return string
	 */
	private function fetchCategoryRecursive( $category ) {
		$result = array();
		if( $category->getChildren() ) {
			foreach ( $category->getChildren() as $child ) {
				$result[] = array(
					'id' => $child->getId(),
					'text' => $child->getTitleKey(),
					'scope' => $child->getGeoScope(),
					'state' => array(
						'opened' => 1
					),
					'li_attr' => array(
						'flag-scope' => $child->getGeoScope()
					),
					'children' => $this->fetchCategoryRecursive( $child )
				);
			}
		}
		return $result;
	}

	protected function getAllowedParams( /* $flags = 0 */ ) {

		return array(
			'method' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'category' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'text' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => false
			),
			'scope' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			),
			'parent' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			)
		);

	}


}