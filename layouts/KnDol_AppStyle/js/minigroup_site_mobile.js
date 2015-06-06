function doSiteSignUp() {
    exec_xml('minigroup','procMinigroupSiteSignUp', new Array(), function() { location.reload(); } );
}

function doSiteLeave(leave_msg) {
    if(!confirm(leave_msg)) return;
    exec_xml('minigroup','procMinigroupSiteLeave', new Array(), function() { location.reload(); } );
}

(function($){
	$('#tabs')
		.tabs();
})(jQuery);
