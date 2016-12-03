$(function(){

	var api = mw.config.get('wgServer') + mw.config.get('wgScriptPath') + '/api.php?action=settlecategories&format=json';

	$.jstree.defaults.core.themes.variant = "large";

	$.get( api + '&method=read', function(response){
		var categories = response.response;
		$('#settle-categories-tree')
		.on('deselect_node.jstree', function(event, data){
			unloadEditor();
		})
		.on('move_node.jstree', function(event, data){
			moveNode( data.node, data.parent );
		}).on('select_node.jstree', function (event, data) {
			if( 'node' in data ) {
				loadNodeIntoEditor(data.node);
			}
		}).jstree({
			core: {
				data: categories,
				check_callback: true
			},
			plugins: [
				"dnd"
			],
			defaults: {
				core: {
					multiple: false
				},
				dnd: {
					drag_selection: false,
					inside_pos: 'last'
				}
			}
		});
	});

	function moveNode( node, parent )
	{

		startLoading();

		if( !parent || parent == undefined || !parent.length || parent == '#' ) {
			parent = -1;
		}

		$.get( api + '&method=move&category='+node.id+'&parent='+parent, function( response ) {
			stopLoading();
			if( response.response.status != 'success' ) {
				alert('Something went wrong, please contact developer!');
			}
		});
	}

	function unloadEditor()
	{
		var $form = $('#settle-category-properties');
		var $title = $form.find('input[name="category_name"]');
		var $scope = $form.find('select[name="category_scope"]');
		var $cid = $form.find('input[name="category_id"]');

		$title.val( '' );
		$scope.val( '' );
		$cid.val('');
	}

	function loadNodeIntoEditor( node )
	{
		var $form = $('#settle-category-properties');
		var $title = $form.find('input[name="category_name"]');
		var $scope = $form.find('select[name="category_scope"]');
		var $cid = $form.find('input[name="category_id"]');

		$title.val( node.text );
		$scope.val( node.original.scope );
		$cid.val( node.id );

	}

	function saveNodeProperties()
	{

		startLoading();

		var $form = $('#settle-category-properties');
		var $title = $form.find('input[name="category_name"]');
		var $scope = $form.find('select[name="category_scope"]');

		var newText = $title.val();
		var newScope = $scope.val();

		if( !newText || !newText.length || !newScope || !newScope.length ) {
			alert('Please fill category information before add/save.');
			stopLoading();
			return false;
		}

		var selectedNodeId = $.jstree.reference('#settle-categories-tree').get_selected();

		if( !selectedNodeId || !selectedNodeId.length ) {
			alert('Please select a node first!');
			stopLoading();
			return false;
		}

		selectedNodeId = selectedNodeId[0];
		var selectedNode = $.jstree.reference('#settle-categories-tree').get_node(selectedNodeId);

		$.get( api + '&method=write&category='+selectedNodeId+'&text='+newText+'&scope='+newScope, function( response ) {
			stopLoading();
			if( response.response.status != 'success' ) {
				alert('Something went wrong, please contact developer!');
			}else{
				selectedNode.original.scope = newScope;
				selectedNode.li_attr['flag-scope'] = newScope;
				$.jstree.reference('#settle-categories-tree').rename_node( selectedNode, newText );
			}
		});

	}

	function pushNewNode() {

		startLoading();

		var $form = $('#settle-category-properties');
		var $title = $form.find('input[name="category_name"]');
		var $scope = $form.find('select[name="category_scope"]');

		var newText = $title.val();
		var newScope = $scope.val();

		if( !newText || !newText.length || !newScope || !newScope.length ) {
			alert('Please fill category information before add/save.');
			stopLoading();
			return false;
		}

		$.get( api + '&method=add&text='+newText+'&scope='+newScope, function( response ) {

			stopLoading();
			if( response.response.status != 'success' ) {
				alert('Something went wrong, please contact developer!');
			}else {
				var newNode = $.jstree.reference('#settle-categories-tree').create_node(
					null,
					{
						id: response.response.id,
						text: newText,
						scope: newScope,
						li_attr: {
							'flag-scope': newScope
						}
					}
				);
			}
		});

	}

	function deleteNode() {

		startLoading();

		var $form = $('#settle-category-properties');
		var $title = $form.find('input[name="category_name"]');
		var $scope = $form.find('select[name="category_scope"]');

		var selectedNodeId = $.jstree.reference('#settle-categories-tree').get_selected();

		if( !selectedNodeId || !selectedNodeId.length ) {
			alert('Please select a node first!');
			stopLoading();
			return false;
		}

		selectedNodeId = selectedNodeId[0];
		var selectedNode = $.jstree.reference('#settle-categories-tree').get_node(selectedNodeId);
		$.get( api + '&method=delete&category='+selectedNodeId, function( response ) {
			stopLoading();
			if( response.response.status != 'success' ) {
				alert('Something went wrong, please contact developer!');
			}else {
				$.jstree.reference('#settle-categories-tree').delete_node(selectedNode);
				$title.val('');
				$scope.val('');
			}
		});
	}

	function startLoading() {
		$('#loading-overlay').css('display', 'flex');
	}

	function stopLoading() {
		$('#loading-overlay').css('display', 'none');
	}

	$('#save-node').click( saveNodeProperties );
	$('#delete-node').click( function() {
		if( !confirm('Are you sure? All children will be removed. All page will be unassigned from category.') ) {
			return false;
		}
		deleteNode();
	});
	$('#create-node').click( pushNewNode );

});