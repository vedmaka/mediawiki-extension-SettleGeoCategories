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
		switch (scope) {
			case 0:
				$msg.html('This category is <b>country</b>-wide.');
				break;
			case 1:
				$msg.html('This category is <b>state</b>-wide.');
				break;
			case 2:
				$msg.html('');
				break;
		}

	});

});