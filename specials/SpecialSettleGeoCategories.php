<?php

/**
 * SettleGeoCategories SpecialPage for SettleGeoCategories extension
 *
 * @file
 * @ingroup Extensions
 */
class SpecialSettleGeoCategories extends SpecialPage
{
    public function __construct()
    {
        parent::__construct( 'SettleGeoCategories' );
    }

    /**
     * Show the page to the user
     *
     * @param string $sub The subpage string argument (if any).
     *  [[Special:SettleGeoCategories/subpage]].
     */
    public function execute( $sub )
    {

    	if( !$this->getUser()->isAllowed('geocategories') ) {
    		$this->displayRestrictionError();
	    }

	    $out = $this->getOutput();
	    $out->addModules('ext.settlegeocategories.special');

	    $out->setPageTitle('Geographical categories');

	    $data = array();
	    $templater = new TemplateParser( dirname(__FILE__) .'/../templates/' );
	    $out->addHTML( $templater->processTemplate('default', $data) );

    	/*if( $this->getRequest()->wasPosted() ) {

    		$title = $this->getRequest()->getVal('title_key');
    		$parent = $this->getRequest()->getVal('parent');
    		$geo = $this->getRequest()->getVal('geo_scope');

    		$category = new SettleGeoCategory();
    		$category->setTitleKey($title);
    		$category->setDescription('');
    		$category->setImage('');
    		$category->setGeoScope( SettleGeoCategories::GEO_SCOPE_DEFAULT );

    		if( !empty($parent) && $parent ) {
				$category->setParentId( $parent );
		    }
		    
		    if( !empty($geo) && $geo != null ) {
		    	$category->setGeoScope($geo);
		    }

		    $category->save();

    		$out->addHTML('New category was added: '.$category->getId());

	    }

	    $categories = SettleGeoCategories::getAllCategories();

        $out->setPageTitle('Geo binding categories test page');
        $out->setHTMLTitle('Geo binding categories test page');

        $out->addHTML('<form method="post">');
        $out->addHTML('Title:<input type="text" name="title_key" value="" />');
        $out->addHTML('Parent:<select name="parent">');
            $out->addHTML('<option value="">-</option>');
		    foreach ( $categories as $category ) {
			    $out->addHTML( $this->displayCategoryRecursiveInput($category) );
		    }
        $out->addHTML('</select>');
	    $out->addHTML('<select name="geo_scope">');
	    	$out->addHTML('<option value="'.SettleGeoCategories::GEO_SCOPE_DEFAULT.'">default</option>');
	    	$out->addHTML('<option value="'.SettleGeoCategories::GEO_SCOPE_COUNTRY.'">country</option>');
	    	$out->addHTML('<option value="'.SettleGeoCategories::GEO_SCOPE_STATE.'">state</option>');
	    	$out->addHTML('<option value="'.SettleGeoCategories::GEO_SCOPE_CITY.'">city</option>');
	    $out->addHTML('</select>');
	    $out->addHTML('<input type="submit" />');
        $out->addHTML('</form>');

        $out->addHTML('<h2>Categories:</h2>');

		foreach ( $categories as $category ) {
		    $out->addHTML( $this->displayCategoryRecursive($category) );
		}*/

    }

	/**
	 * @param SettleGeoCategory $category
	 *
	 * @param string            $prefix
	 *
	 * @return string
	 */
	public static function displayCategoryRecursiveInput( $category, $prefix = '' )
	{
		$html = '';
		$html .= '<option value="'.$category->getId().'">'.$prefix.' '.$category->getTitleKey().'</option>';
		if( $category->getChildren() ) {
			foreach ( $category->getChildren() as $child ) {
				$html .= self::displayCategoryRecursiveInput( $child, $prefix.'--' );
			}
		}
		return $html;
	}

	/**
	 * @param SettleGeoCategory $category
	 *
	 * @return string
	 */
	public static function displayCategoryRecursive( $category, $tagWrap = 'ul', $tagList = 'li' )
    {
    	$html = '';
    	$html .= '<'.$tagWrap.'>';
    	    $html .= '<'.$tagList.'>'.$category->getTitleKey().' ('.$category->getId().') ['.$category->getGeoScope().']</'.$tagList.'>';
    	    if( $category->getChildren() ) {
    	    	foreach ( $category->getChildren() as $child ) {
					$html .= self::displayCategoryRecursive( $child );
		        }
	        }
    	$html .= '</'.$tagWrap.'>';
    	return $html;
    }

    protected function getGroupName()
    {
        return 'other';
    }
}
