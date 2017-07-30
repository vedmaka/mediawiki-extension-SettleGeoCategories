/* global $, mw, console */

$(function(){

    'use strict';

    var apiUrl = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?format=json';

    var country_id = $('#country-search-wrapper').data('country-id');

    var itemTemplate = mw.template.get('ext.settlegeocategories.search', 'category_item.ajax.mustache');
    var childTemplate = mw.template.get('ext.settlegeocategories.search', 'sub_category_item.ajax.mustache');
    var pageTemplate = mw.template.get('ext.settlegeocategories.search', 'ajax_article.mustache');

    var container = $('#csw-items');
    var containerSub = $('#csw-sub-items');
    var containerPages = $('#csw-pages-items');

    var responseCache = null;

	$.get(apiUrl + '&action=settlecategories&method=read&country_id='+country_id, function (response) {
		responseCache = response;
		displayCategories( responseCache );
	});

    function displayCategories( response ) {

			// Clear our container
			container.html('');
			containerSub.html('');
			containerPages.html('');

			// Render top categories
			$.each(response.response, function(i, item) {

				item.count = 0;
				$.each(item.children, function(t,g) {
					item.count += g.pages.length;
				});

				var html = itemTemplate.render(item);

				$(html).click(function(){

					if( $(this).hasClass('csw-active') ) {
						return false;
					}

					$('.csw-back').show();

					container.find('.csw-active').removeClass('csw-active');
					$('.csw-category-item').hide();
					$(this).show();
					$(this).addClass('csw-active');
					//$(this).parent().appendTo( container );

					containerSub.html('');
					containerPages.html('');

					if( !item.children.length ) {
						containerSub.html( '<div class="col-md-12">' +
							mw.msg('settlegeocategories-ajax-no-sub-categories') + "</div>" );
					}else{

						$.each( item.children, function(j, child){

							child.count = child.pages.length;

							var childHtml = childTemplate.render( child );

							$(childHtml).click(function(){

								if( $(this).hasClass('csw-active') ) {
									return false;
								}

								containerSub.find('.csw-active').removeClass('csw-active');
								$(this).addClass('csw-active');

								containerPages.html('');

								if( !child.pages.length ) {
									containerPages.html( '<div class="col-md-12">' +
										mw.msg('settlegeocategories-ajax-no-pages-categories') + "</div>" );
								}else{

									$.each( child.pages, function(k, page){

										var pageHtml = pageTemplate.render( page );
										containerPages.append( pageHtml );

									});

								}

							});

							containerSub.append( childHtml );

						});

					}

				});

				container.append(html);

			});
    }

    $('.csw-back').click(function(){
        displayCategories(responseCache);
        $(this).hide();
    });

});