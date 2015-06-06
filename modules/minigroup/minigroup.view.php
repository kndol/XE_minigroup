<?php
/**
 * @class  minigroupView
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의 view class
 **/

class minigroupView extends minigroup
{

	var $site_module_info = null;
	var $site_srl = 0;
	var $minigroup_info = null;

	/**
	 * @brief 초기화
	 **/
	function init()
	{
		$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->skin = 'minigroup_default';
			$template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
		}
		$this->setTemplatePath($template_path);
	}

	/**
	 * @brief 소모임 목록 출력
	 **/
	function dispMinigroupIndex()
	{
		$oMinigroupAdminModel = &getAdminModel('minigroup');
		$oMinigroupModel = &getModel('minigroup');
		$oModuleModel = &getModel('module');
		$oDocumentModel = &getModel('document');
		$oCommentModel = &getModel('comment');

		// 소모임 목록을 구함
		$minigroup_srls = array();
		$args = new stdClass();
		$args->page = Context::get('page');
		$output = $oMinigroupAdminModel->getMinigroupList($args);
		if($output->data && count($output->data))
		{
			foreach($output->data as $key => $val)
			{
				$icon_src = 'files/attach/minigroup_icon/'.$val->site_srl.'.png';
				if(file_exists(_XE_PATH_.$icon_src)) $output->data[$key]->minigroup_icon = $icon_src.'?rnd='.filemtime(_XE_PATH_.$icon_src);
				else $output->data[$key]->minigroup_icon = '';

				$url = getSiteUrl($val->domain,'');
				if(substr($url,0,1)=='/') $url = substr(Context::getRequestUri(),0,-1).$url;
				$output->data[$key]->url = $url;
				$minigroup_srls[$val->site_srl] = $key;
				// 소모임의 회원 수 추출
				$mc_args->site_srl = $val->site_srl;
				$mc_output = executeQuery('minigroup.getSiteMemberCount', $mc_args);
				$member_count = $mc_output->data;
				$output->data[$key]->memberCount = $member_count->member_count;
			}
		}

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('minigroup_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		// 소모임 생성 권한 세팅
		if($oMinigroupModel->isCreationGranted()) Context::set('isEnableCreateMinigroup', true);

		// 소모임의 최신 글 추출
		$output = executeQueryArray('minigroup.getNewestDocuments');
		if($output->data)
		{
			foreach($output->data as $key => $attribute)
			{
				$document_srl = $attribute->document_srl;
				if(!$GLOBALS['XE_DOCUMENT_LIST'][$document_srl])
				{
					unset($oDocument);
					$oDocument = new documentItem();
					$oDocument->setAttribute($attribute, false);
					$GLOBALS['XE_DOCUMENT_LIST'][$document_srl] = $oDocument;
				}
				$output->data[$key] = $GLOBALS['XE_DOCUMENT_LIST'][$document_srl];
			}
		}
		Context::set('newest_documents', $output->data);

		// 소모임의 최신 댓글 추출
		$output = executeQueryArray('minigroup.getNewestComments');
		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				unset($oComment);
				$oComment = new commentItem(0);
				$oComment->setAttribute($val);
				$output->data[$key] = $oComment;
			}
		}
		Context::set('newest_comments', $output->data);

/*
		$logged_info = Context::get('logged_info');
		if($logged_info->member_srl)
		{
			$myargs->member_srl = $logged_info->member_srl;
			$output = executeQueryArray('minigroup.getMyMinigroups', $myargs);
			Context::set('my_minigroups', $output->data);
		}
*/
// 가입하지 않은 소모임 목록만 표시하게 바꿀 것

		$minigroup_info = $oModuleModel->getModuleConfig('minigroup');

		$this->setTemplateFile('index');
	}

	/**
	 * @brief 가입한 소모임 표시
	 **/
	function dispMinigroupMyMinigroup()
	{
		$logged_info = Context::get('logged_info');
		$oModuleModel = &getModel('module');

		// 기본 사이트에 등록된 게시판 가져오기
		$args->site_srl = 0;
		$args->module = 'board';
		$board_list = $oModuleModel->getMidList($args);
		Context::set('board_list', $board_list);

		if($logged_info->member_srl)
		{
			$myargs->member_srl = $logged_info->member_srl;
			$myargs->page = Context::get('page');
			$output = executeQueryArray('minigroup.getMyMinigroupList', $myargs);

			if($output->data && count($output->data))
			{
				foreach($output->data as $key => $val)
				{
					$icon_src = 'files/attach/minigroup_icon/'.$val->site_srl.'.png';
					if(file_exists(_XE_PATH_.$icon_src)) $output->data[$key]->minigroup_icon = $icon_src.'?rnd='.filemtime(_XE_PATH_.$icon_src);
					else $output->data[$key]->minigroup_icon = '';
				}
			}
			$my_minigroups = $output->data;
			if($my_minigroups)
			{
				foreach($my_minigroups as $key => $val)
				{
					// get minigroup site members count
					$myargs->site_srl = $val->site_srl;
					$output = executeQuery('minigroup.getSiteMemberCount', $myargs);
					$member_count = $output->data;
					$my_minigroups[$key]->memberCount = $member_count->member_count;
				}
			}
			Context::set('my_minigroups', $my_minigroups);
			Context::set('my_minigroup_page_navigation',$output->page_navigation);
		}
		$this->setTemplateFile('myMinigroupList');
	}

	/**
	 * @brief 홈페이지 생성
	 **/
	function dispMinigroupCreate()
	{
		$oMinigroupModel = &getModel('minigroup');
		if(!$oMinigroupModel->isCreationGranted()) return new Object(-1,'msg_not_permitted');
		$this->setTemplateFile('create');
	}

	function dispMinigroupManage()
	{
		header('location:'.getNotEncodedUrl('act','dispMinigroupAdminSiteManage'));
		Context::close();
		exit();
	}

	/**
	 * 소모임 내 통합검색
	 * @return void
	 */
	function dispMinigroupIS()
	{
		$oFile = &getClass('file');
		$oModuleModel = &getModel('module');
		$oMinigroupModel = &getModel('minigroup');

		$vid = Context::get('vid');
		$site_info = $oModuleModel->getSiteInfoByDomain($vid);

		//site_srl 없으면 list로 보고 통합검색 제공
		if($site_info->site_srl)
		{
			$site_info = $oMinigroupModel->getMinigroupInfo($site_info->site_srl);
			if($site_info->site_srl) $args->site_srl = $site_info->site_srl;
		}


		$module_srl_list = array();
		$output_module_list = executeQueryArray('minigroup.getModuleListMinigroup',$args);
		$include_module_list = $output_module_list->data;
		if(is_array($include_module_list))
		{
			$target = 'include';
			foreach($include_module_list as $val)
			{
				array_push($module_srl_list,$val->module_srl);
			}
		}

		// Set a variable for search keyword
		$is_keyword = Context::get('is_keyword');
		// Set page variables
		$page = (int)Context::get('page');
		if(!$page) $page = 1;
		// Search by search tab
		$where = Context::get('where');
		// Create integration search model object
		if($is_keyword)
		{
			$oIS = &getModel('integration_search');
			switch($where)
			{
				case 'document' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title';
					Context::set('search_target', $search_target);

					$output = $oIS->getDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("document", $page);
					break;
				case 'comment' :
					$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("comment", $page);
					break;
				case 'trackback' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','url','blog_name','excerpt'))) $search_target = 'title';
					Context::set('search_target', $search_target);

					$output = $oIS->getTrackbacks($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					Context::set('output', $output);
					$this->setTemplateFile("trackback", $page);
					break;
				case 'multimedia' :
					$output = $oIS->getImages($target, $module_srl_list, $is_keyword, $page,20);
					Context::set('output', $output);
					$this->setTemplateFile("multimedia", $page);
					break;
				case 'file' :
					$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
					Context::set('output', $output);
					$this->setTemplateFile("file", $page);
					break;
				default :
					$output['document'] = $oIS->getDocuments($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
					$output['trackback'] = $oIS->getTrackbacks($target, $module_srl_list, 'title', $is_keyword, $page, 5);
					$output['multimedia'] = $oIS->getImages($target, $module_srl_list, $is_keyword, $page, 5);
					$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
					Context::set('search_result', $output);
					Context::set('search_target', 'title');
					$this->setTemplateFile("search_index", $page);
					break;
			}
		}
		else
		{
			$this->setTemplateFile("no_keywords");
		}

		$security = new Security();
		$security->encodeHTML('is_keyword', 'search_target', 'where', 'page');
	}
}
/* End of file minigroup.view.php */
/* Location: ./modules/minigroup/minigroup.view.php */