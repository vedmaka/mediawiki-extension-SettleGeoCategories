$(function(){

	if( !$('.settlecategories-dropdown').length ) {
		return true;
	}

	var $input = $('.settlecategories-dropdown');
	var $msg = $('.settlecategories-dropdown-span .category-input-text-description');

	$input.on('change', function() {

		if( !$(this).val() || !$(this).val().length ) {
			$msg.html('Please select a category.');
			return true;
		}

		var scope = $(this).find('option:selected').data('scope');
		$msg.html( getMsgScope(scope) );

	});

	if( $input.val() && $input.val().length && $input.find('option:selected').length ) {
		var scope = $input.find('option:selected').data('scope');
		$msg.html( getMsgScope(scope) );
	}

	function getMsgScope( scope ) {

		switch (scope) {
			case 0:
				return 'This category is <b>country</b>-wide.';
				break;
			case 1:
				return 'This category is <b>state</b>-wide.';
				break;
			case 2:
				return '';
				break;
			default:
				return '';
				break;
		}

		return '';

	}

});