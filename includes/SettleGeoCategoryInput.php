<?php

class SettleGeoCategoryInput extends SFDropdownInput {
    
    public static function getName() {
		return 'settlecategories';
	}
	
	public static function getHTML( $cur_value, $input_name, $is_mandatory, $is_disabled, $other_args ) {
		global $sfgTabIndex, $sfgFieldNum, $sfgShowOnSelect, $wgOut;

		if( !in_array( 'ext.settlegeocategories.input', $wgOut->getModules()) ) {
			$wgOut->addModules( 'ext.settlegeocategories.input' );
		}

		// Standardize $cur_value
		if ( is_null( $cur_value ) ) { $cur_value = ''; }

		$className = ( $is_mandatory ) ? 'mandatoryField' : 'createboxInput';
		$className .= ' settlecategories-dropdown';
		if ( array_key_exists( 'class', $other_args ) ) {
			$className .= ' ' . $other_args['class'];
		}
		$input_id = "input_$sfgFieldNum";
		$innerDropdown = '';
		// Add a blank value at the beginning, unless this is a
		// mandatory field and there's a current value in place
		// (either through a default value or because we're editing
		// an existing page).
		if ( !$is_mandatory || $cur_value === '' ) {
			$innerDropdown .= "	<option value=\"\"></option>\n";
		}
		
		// Fetch categories
		$possible_values = array();
		$allCategories = SettleGeoCategories::getAllCategories();
		foreach( $allCategories as $cat ) {
			$innerDropdown .= self::displayCategoryRecursiveInput( $cat, '', $cur_value );
		}
		
		$selectAttrs = array(
			'id' => $input_id,
			'tabindex' => $sfgTabIndex,
			'name' => $input_name,
			'class' => $className
		);
		if ( $is_disabled ) {
			$selectAttrs['disabled'] = 'disabled';
		}
		if ( array_key_exists( 'origName', $other_args ) ) {
			$selectAttrs['origname'] = $other_args['origName'];
		}
		$text = Html::rawElement( 'select', $selectAttrs, $innerDropdown );
		$spanClass = 'inputSpan settlecategories-dropdown-span';
		if ( $is_mandatory ) {
			$spanClass .= ' mandatoryFieldSpan';
		}
		if( $cur_value === '' ) {
			$text .= '<span class="category-input-text-description">Please select a category.</span>';
		}else{
			$text .= '<span class="category-input-text-description"></span>';
		}

		$text = Html::rawElement( 'span', array( 'class' => $spanClass ), $text );
		return $text;
	}

	/**
	 * @param SettleGeoCategory $category
	 * @param string $prefix
	 * @param string $cur_value
	 *
	 * @return string
	 */
	public static function displayCategoryRecursiveInput( $category, $prefix = '', $cur_value )
	{
		$html = '';
		$selected = '';
		if ( $cur_value == $category->getId() ) {
				$selected = "selected";
		}
		$html .= '<option data-scope="'.$category->getGeoScope().'" '.$selected.' value="'.$category->getId().'">'.$prefix.' '.$category->getTitleKey().'</option>';
		if( $category->getChildren() ) {
			foreach ( $category->getChildren() as $child ) {
				$html .= self::displayCategoryRecursiveInput( $child, $prefix.'--', $cur_value );
			}
		}
		return $html;
	}
    
}