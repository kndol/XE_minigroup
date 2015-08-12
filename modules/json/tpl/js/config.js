function doDisplayMenuItems(sel)
{
	var menu_srl = sel.options[sel.selectedIndex].value;

	var params = new Array();
	params.menu_srl = menu_srl;

	var response_tags = new Array('error', 'message', 'menu_items');

	exec_xml('json', 'getAdminMenuItems', params, completeGetMenuItems, response_tags, params);
}

function completeGetMenuItems(ret_obj, response_tags, params, fo_obj)
{
	var menu_items_div = document.getElementById('menu_item');
	var menu_items = ret_obj['menu_items']['item'];
	while (menu_items_div.hasChildNodes()) {
		menu_items_div.removeChild(menu_items_div.lastChild);
	}
	for(var i=0;i<menu_items.length;i++) {
		var newdiv = document.createElement('div');
		newdiv.innerHTML = "<label><input type='checkbox' value='" + menu_items[i]['menu_item_srl'] + "' name='show_menu_srls[]' /> <strong>" + menu_items[i]['name'] + "</strong> ("+ menu_items[i]['menu_item_srl']+")</label>";
		menu_items_div.appendChild(newdiv);
	}
}