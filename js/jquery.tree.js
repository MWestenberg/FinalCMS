function isRightClick(event) {
     
 var rightclick;
 if (!event) var event = window.event;
 if (event.which) rightclick = (event.which == 3);
 else if (event.button) rightclick = (event.button == 2);
 
 return rightclick;
}
     
$(document).ready(function () {
	var tree =  $('#jqxTree');
	var theme ='finalcms';
	
		
	
	//set global variables
	var contextMenuItemAdd = null;
	var contextMenuItemAddRemove = null;
	var contextMenuAdd = null;
	var contextMenuAddRemove = null;
	var contextMenuRemove = null;
	var contextItemAdd = null;
	var contextItemAddRemove = null;
	var contextItemRemove = null;
	var clickedItem = null;
	var target = null;
	var rightClick = null;
	var lvl = null;
	var scrollTop = null;
	var scrollLeft = null;
	
	
	contextMenuItemAdd = $("#jqxMenuItemAdd").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextMenuItemAddRemove = $("#jqxMenuItemAddRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextMenuAdd = $("#jqxMenuAdd").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextMenuAddRemove = $("#jqxMenuAddRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextMenuRemove = $("#jqxMenuRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextItemAdd = $("#jqxItemAdd").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextItemAddRemove = $("#jqxItemAddRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	contextItemRemove = $("#jqxItemRemove").jqxMenu({ width: '120px', theme: theme, height: '86px', autoOpenPopup: false, mode: 'popup' });
	clickedItem = null;
	// open the context menu when the user presses the mouse right button.
	tree.loadJqxTree(tree,"websitetree",{'action' : 'getPageTree'},theme);		
	
		
	
	$("#jqxMenuItemAdd, #jqxMenuItemAddRemove,#jqxMenuAdd, #jqxMenuAddRemove,#jqxMenuRemove,#jqxItemAdd,#jqxItemAddRemove,#jqxItemRemove,#jqxItemAddMenuRemove").on('itemclick', function (event) {
		var item = $(event.args).text();
		var selectedItem = tree.jqxTree('selectedItem');
		var $element = $(event.args.element);
		
		
		switch (item) {
			case "Add Item":
				if (selectedItem != null) {
					var retVal = prompt("Give a new name to this item: ", "item name");
					tree.contextMenu(selectedItem,retVal,'addItem');
					
				}
				break;
			case "Add Page":
				if (selectedItem != null) {
					var retVal = prompt("Give a new name to this page: ", "Page name");
					tree.contextMenu(selectedItem,retVal,'addPage',$element);
				}	
				break;
			case "Remove Page":
				if (selectedItem != null) {
					if (confirm("Are you sure you want to delete this page and it's content permanently?"))
					{
						tree.contextMenu(selectedItem,retVal,'removePage',$element);
					}
				}	
				break;
			case "Remove Item":
				alert(selectedItem);
				if (selectedItem != null) {
					$("#jqxTree").jqxTree('removeItem', selectedItem.element);		
				}
				break;
		
		
		}
	});
	
	// disable the default browser's context menu.
	$(document).on('contextmenu', function (e) {
		if ($(e.target).parents('#jqxTree').length > 0) {
			return false;
		}
		return true;
	});
  
 
  
	//$('#main').jqxPanel({ theme: theme, height: 'auto', width: '600px' });
	tree.on('expand', function (event) {
		var args = event.args;
		var item = $(this).jqxTree('getItem', args.element);
		
		//$('#main').jqxPanel('prepend', '<div style="margin-top: 5px;">Expanded: ' + item.label +' level:'+ item.level+ '</div>');
	});
	tree.on('collapse', function (event) {
		var args = event.args;
		var item = $(this).jqxTree('getItem', args.element);
		//$('#main').jqxPanel('prepend', '<div style="margin-top: 5px;">Collapsed: ' + item.label +' level:'+ item.level+  '</div>');
	});
	tree.on('select', function (event) {
		
		
		var args = event.args;
		var item = $(this).jqxTree('getItem', args.element);
		
		
		
		var params = {'action':'getItem', 'message': $.getMessage().ajaxLoader.loader.msg,'title':$.getMessage().ajaxLoader.loader.title,
			'item' : item.value};
		$(this).ajaxForm("websitetree",params);
		
		
		//$('#main').jqxPanel('prepend', '<div style="margin-top: 5px;">Selected: '+ item.id+ ' label:' + item.label +' level:' + item.level+ ' parent:'+item.hasItems+ ' value:'+item.value+'</div>');
		
	});
	// Expand All
	$('#ExpandAll').click(function () {
		tree.jqxTree('expandAll');
	});
	// Collapse All
	$('#CollapseAll').click(function () {
		tree.jqxTree('collapseAll');
	}); 
	$('#RefreshTree').click(function () {
		tree.jqxTree('refresh');
	
	}); 
	
	
});