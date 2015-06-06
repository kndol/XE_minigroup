function doMinigroupInsertAdmin() {
    var fo_obj = xGetElementById("minigroupFo");
    var sel_obj = fo_obj.admin_list;
    var admin_id = fo_obj.admin_id.value;
    if(!admin_id) return;

    var opt = new Option(admin_id,admin_id,true,true);
    sel_obj.options[sel_obj.options.length] = opt;

    fo_obj.admin_id.value = '';
    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;
}

function doMinigroupDeleteAdmin() {
    var fo_obj = xGetElementById("minigroupFo");
    var sel_obj = fo_obj.admin_list;
    sel_obj.remove(sel_obj.selectedIndex);

    sel_obj.size = sel_obj.options.length;
    sel_obj.selectedIndex = -1;
}

function doUpdateMinigroup(fo_obj, msg_select_date, msg_select_future) {
    var sel_obj = fo_obj.admin_list;
    var arr = new Array();
    for(var i=0;i<sel_obj.options.length;i++) {
        arr[arr.length] = sel_obj.options[i].value;
    }
    fo_obj.minigroup_admin.value = arr.join(',');

	var limited_Y = xGetElementById("limited_Y");
	var limit_date = xGetElementById("date_limit_date");
	var input_date = xGetElementById("input_date");
	
	if (limited_Y.checked) {
		if (!input_date.value.length) {
			alert(msg_select_date);
			return false;
		}
		var now = new Date(); 
		var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
		var arrDate = input_date.value.split("-");
		var inputDate = new Date(parseInt(arrDate[0]),parseInt(arrDate[1])-1,parseInt(arrDate[2]));
		if (inputDate.getTime() < today.getTime()) {
			alert(msg_select_future);
			return false;
		}
	}
	limit_date.value = input_date.value.replace(/-/g,"");
	
    return true;
}

function completeDeleteMinigroup(ret_obj) {
    alert(ret_obj['message']);
    location.href = current_url.setQuery('act','dispMinigroupAdminIndex').setQuery('site_srl','');
}

function nodeToggleAll(){
    jQuery("[class*=close]", simpleTreeCollection[0]).each(function(){
        simpleTreeCollection[0].nodeToggle(this);
    });
}

/* 모듈 생성 후 */
function completeInsertBoard(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];

    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);

    var url = current_url.setQuery('act','dispMinigroupBoardInfo');
    if(module_srl) url = url.setQuery('module_srl',module_srl);
    if(page) url.setQuery('page',page);
    location.href = url;
}

function completeInsertGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');
}

function completeDeleteGroup(ret_obj) {
    location.href = current_url.setQuery('group_srl','');
}

function completeInsertGrant(ret_obj) {
    var error = ret_obj['error'];
    var message = ret_obj['message'];
    var page = ret_obj['page'];
    var module_srl = ret_obj['module_srl'];

    alert(message);
}

function completeInsertPage(ret_obj) {
    alert(ret_obj['message']);
    location.reload();
}

function completeChangeLayout(ret_obj) {
    location.reload();
}

function doDeleteGroup(group_srl) {
    var fo_obj = xGetElementById('fo_group');
    fo_obj.group_srl.value = group_srl;
    procFilter(fo_obj, delete_group);
}

function changeMenuType(obj) {
    var sel = obj.options[obj.selectedIndex].value;
    if(sel == 'url') {
        jQuery('#urlForm').css("display","table-row");
    } else {
        jQuery('#urlForm').css("display","none");
    }
}

function doRemoveMember(confirm_msg) {
    var fo_obj = document.getElementById('siteMembers');
    var chk_obj = fo_obj.cart;
    if(!chk_obj) return;

    var values = new Array();
    if(typeof(chk_obj.length)=='undefined') {
        if(chk_obj.checked) values[values.length]=chk_obj.value;
    } else {
        for(var i=0;i<chk_obj.length;i++) {
            if(chk_obj[i].checked) values[values.length]=chk_obj[i].value;
        }
    }
    if(values.length<1) return;

    if(!confirm(confirm_msg)) return;

    params = new Array();
    params['member_srl'] = values.join(',');

    exec_xml('minigroup','procMinigroupDeleteMember', params, doCompleteRemoveMember);
}

function doCompleteRemoveMember(ret_obj) { 
    alert(ret_obj['message']); 
    location.reload(); 
}

function importModule(id) {
    popopen( request_uri.setQuery('module','module').setQuery('act','dispModuleSelectList').setQuery('id',id).setQuery('type','single'), 'ModuleSelect');
}

function insertSelectedModule(id, module_srl, mid, browser_title) {
    params = new Array();
    params['import_module_srl'] = module_srl;
    params['site_srl'] = xGetElementById('foImport').site_srl.value;
    exec_xml('minigroup','procMinigroupAdminImportModule', params, doComplenteInsertSelectedModule);
}

function doComplenteInsertSelectedModule(ret_obj) {
    location.reload();
}

jQuery(function($){
	$('#chkDomain, #chkVid').change(function(){
		if($('#chkDomain').is(':checked')){
			$('#accessDomain').show();
			$('#accessVid').hide();
		}else if($('#chkVid').is(':checked')){
			$('#accessDomain').hide();
			$('#accessVid').show();
		}
	});
	$('#chkMinigroupVid, #chkMinigroupDomain').change(function(){
		if($('#chkMinigroupVid').is(':checked')){
			$('#accessMinigroupDomain').hide();
		}else if($('#chkMinigroupDomain').is(':checked')){
			$('#accessMinigroupDomain').show();
		}
	});
});

jQuery(function($){
	var asel = $("#signup_method :selected").val();
	if(asel !='inviting') {
		$("#free_signup_help").css('display','inline');
		$("#inviting_help").css('display','none');
		$("#invitable_group").css('display','none');
		$("#minigroup_visibility").css('display','none');
	}
	else {
		$("#free_signup_help").css('display','none');
		$("#inviting_help").css('display','inline');
	}
	$("#signup_method").click(function() {
		var sel = $(this).val();
		if(sel =='inviting') {
			$("#free_signup_help").css('display','none');
			$("#inviting_help").css('display','inline');
			$("#invitable_group").css('display','block');
			$("#minigroup_visibility").css('display','block');
		}
		else {
			$("#free_signup_help").css('display','inline');
			$("#inviting_help").css('display','none');
			$("#invitable_group").css('display','none');
			$("#minigroup_visibility").css('display','none');
		}
	});
});

jQuery(function($){
	var asel = $("#lifespan_limited").find('input:radio:checked').val();
	if(asel !='Y') {
		$("#limit_date_ui").css('display','none');
		$("#limit_option").css('display','none');
	}
	$("#lifespan_limited").click(function() {
		var sel = $(this).find('input:radio:checked').val();
		if(sel =='Y') {
			$("#limit_date_ui").css('display','block');
			$("#limit_option").css('display','block');
		}
		else {
			$("#limit_date_ui").css('display','none');
			$("#limit_option").css('display','none');
		}
	});
});
