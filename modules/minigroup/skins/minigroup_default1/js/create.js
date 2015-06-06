jQuery(function($){
	var asel = $("#minigroupLifespanLtd").find('input:radio:checked').val();
	if(asel !='Y') {
		$("#minigroup_limit_date").css('display','none');
	}
	$("#minigroupLifespanLtd").click(function() {
		var sel = $(this).find('input:radio:checked').val();
		if(sel =='Y') {
			$("#minigroup_limit_date").css('display','block');
		}
		else {
			$("#minigroup_limit_date").css('display','none');
		}
	});
});