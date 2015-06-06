jQuery(function($){
	/*-- search box --*/
	var _this = this;
	$('.btn_srch').click(function(){
		$(this).next().toggle();
	});

	$('.searchkey').keydown(function(){
		$('.btn_cancel').css('display','inline-block');
	});

	$('.btn_cancel').click(function(){
		$('.searchkey')[0].value='';
		$(this).hide();
		$('.searchkey').focus();
	});

	// add a mini group to mine
	$('.btn_dislike').bind('click',function()
	{
	    var paramVid = this.getAttribute("data");
		doSiteSignUp(paramVid, this);
	});

	// remove a mini group from mine
	$('.btn_like').bind('click',function()
	{
		var paramVid = this.getAttribute("data");
		doSiteLeave(paramVid , this);
	});

	// sign up site
	$('.btn_sign_up').bind('click',function()
	{
	    var paramVid = this.getAttribute("data");
		var params = new Array();
        params['vid'] = paramVid;
		exec_xml('member','procMemberSiteSignUp',  params, function() { location.reload(); } );
	});

	// leave site
	$('.btn_leave').bind('click',function()
	{
		var paramVid = this.getAttribute("data");
		var params = new Array();
        params['vid'] = paramVid;
		exec_xml('member','procMemberSiteLeave',  params, function() { location.reload(); } );
	});

	// All mini groups && My mini groups pagination

	function changeData(linkObj)
	{		
		if(linkObj.className == 'btn_dislike')
	    {
	        $(linkObj).toggleClass('btn_dislike',false);
	        $(linkObj).toggleClass('btn_like');
            var mNumObj = $(linkObj).prev('.minigroup_info').find('.memberNumber');
            mNumObj.html(parseInt(mNumObj.html()) + 1)

	    }
	    else
	    {
	        $(linkObj).toggleClass('btn_like',false);
	        $(linkObj).toggleClass('btn_dislike');
	        var mNumObj = $(linkObj).prev('.minigroup_info').find('.memberNumber');
            mNumObj.html(parseInt(mNumObj.html()) - 1)
	    }
	}

	function doSiteSignUp(vid, cObj)
	{
	    var params = new Array();
        params['vid'] = vid;
        var response_tags = ['error','message'];
        var funcSuc = function(ret_obj, response_tags)
                     {
                        if(ret_obj['error']==='0')
                        {
                            var isAdd = true;
                            changeData(cObj, isAdd);
                            return;
                        }
                     };

        exec_xml('member',
                 'procMemberSiteSignUp',
                 params,
                 funcSuc,
                 response_tags
        );
    }

    function doSiteLeave(vid, cObj)
    {
        var params = new Array();
        params['vid'] = vid;
        var response_tags = ['error','message'];
        var funcSuc = function(ret_obj, response_tags)
         {
            if(ret_obj['error']==='0')
            {
                changeData(cObj);
                return;
            }
         };

        exec_xml('member',
                 'procMemberSiteLeave',
                 params,
                 funcSuc,
                 response_tags
        );
    }

	more_btn = $('#getMore');
	var list_view_rage = 4;
	listNewestDocs = 'ul.doc_list>:gt('+(list_view_rage-1)+')';
	var minigroup_view_rage = 5;
	minigroupItems = 'ul.minigroup_item>:gt('+ (minigroup_view_rage-1)+')';
	$(listNewestDocs).css('display','none');
	$(minigroupItems).css('display','none');
	more_btn.click(function(){
		if($(listNewestDocs).length>0){
			list_view_rage +=4;
			listNewestDocs = 'ul.doc_list>:lt('+(list_view_rage)+')';
			$(listNewestDocs).slideDown('slow');
		}
		if($(minigroupItems).length>0){
			minigroup_view_rage +=5;
			minigroupItems = 'ul.minigroup_item>:lt('+(minigroup_view_rage)+')';
			$(minigroupItems).slideDown('slow');
		}
	});
	
	var minigroup_list = $('ul.minigroup_lst');
	minigroup_list.first().css('margin-top','5px');
	
	var doc_list = $('ul.doc_list');
	doc_list.first().css('margin-top','5px');



	

})