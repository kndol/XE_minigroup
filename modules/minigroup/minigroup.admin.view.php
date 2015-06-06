<?php
/**
 * @class  minigroupAdminView
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의 admin view class
 **/

class minigroupAdminView extends minigroup
{
	var $site_module_info = null;
	var $site_srl = 0;
	var $minigroup_info = null;

	function init()
	{
		if(strpos($this->act,'MinigroupAdminSite')!==false)
		{
			$oModuleModel = &getModel('module');
			// 현재 접속 권한 체크하여 사이트 관리자가 아니면 접근 금지
			$logged_info = Context::get('logged_info');
			if(!Context::get('is_logged') || !$oModuleModel->isSiteAdmin($logged_info)) return $this->stop('msg_not_permitted');

			// site_module_info값으로 홈페이지의 정보를 구함
			$this->site_module_info = Context::get('site_module_info');
			$this->site_srl = $this->site_module_info->site_srl;
			if(!$this->site_srl) return $this->stop('msg_invalid_request');

			// 홈페이지 정보를 추출하여 세팅
			$oMinigroupModel = &getModel('minigroup');
			$this->minigroup_info = $oMinigroupModel->getMinigroupInfo($this->site_srl);
			Context::set('minigroup_info', $this->minigroup_info);

			// 모듈 번호가 있으면 해당 모듈의 정보를 구해와서 세팅
			$module_srl = Context::get('module_srl');
			if($module_srl)
			{
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
				if(!$module_info || $module_info->site_srl != $this->site_srl) return new Object(-1,'msg_invalid_request');
				$this->module_info = $module_info;
				Context::set('module_info', $module_info);
			}
		}
		$template_path = sprintf("%stpl/",$this->module_path);
		$this->setTemplatePath($template_path);
	}

	function dispMinigroupAdminIndex()
	{
		$oMinigroupAdminModel = &getAdminModel('minigroup');

		// 생성된 소모임 목록을 구함
		$args->page = Context::get('page');
		$args->search_target = Context::get('search_target');
		$args->search_keyword = Context::get('search_keyword');

		$output = $oMinigroupAdminModel->getMinigroupList($args);

		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('minigroup_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('index');
	}

	function dispMinigroupAdminInsert()
	{
		Context::addJsFilter($this->module_path.'tpl/filter', 'insert_minigroup.xml');
		$this->setTemplateFile('insert');

	}

	function dispMinigroupAdminManage()
	{
		$oLayoutModel = &getModel('layout');
		$oMinigroupModel = &getModel('minigroup');
		$oModuleModel = &getModel('module');
		$oMemberModel = &getModel('member');
		$board_path = ModuleHandler::getModulePath("board");

		// 소모임 전체 설정을 구함
		Context::set('minigroup_config', $oMinigroupModel->getConfig());

		// 레이아웃 목록을 구함
		Context::set('layout_list', $oLayoutModel->getDownloadedLayoutList());

		// 모바일 레이아웃
		Context::set('mlayout_list', $oLayoutModel->getDownloadedLayoutList('M'));

		// 소모임 목록의 레이아웃
		Context::set('list_layout_list', $oLayoutModel->getLayoutList());

		// 소모임 목록의 모바일 레이아웃
		Context::set('list_mlayout_list', $oLayoutModel->getLayoutList(0, "M"));

		// 소모임 목록의 스킨
		Context::set('skins', $oModuleModel->getSkins($this->module_path));

		// 소모임 목록의 모바일 스킨
		Context::set('list_mskin_list', $oModuleModel->getSkins($this->module_path, "m.skins"));

		// 기본 사이트의 그룹 구함
		Context::set('groups', $oMemberModel->getGroups(0));

		// 소모임 게시판의 스킨
		Context::set('board_skins', $oModuleModel->getSkins($board_path));

		// 소모임 게시판의 모바일 스킨
		Context::set('board_mskins', $oModuleModel->getSkins($board_path, "m.skins"));

		//메뉴 목록
		$oMenuAdminModel = &getAdminModel('menu');
		$menu_list = $oMenuAdminModel->getMenus();
		Context::set('menu_list', $menu_list);

		$this->setTemplateFile('manage');
	}

	function dispMinigroupAdminSetup()
	{
		$oLayoutModel = &getModel('layout');
		$oMinigroupAdminModel = &getAdminModel('minigroup');
		$oMinigroupModel = &getModel('minigroup');
		$oModuleModel = &getModel('module');
		$oModuleAdminModel = getAdminModel('module');
		$oMemberModel = &getModel('member');

		$member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $member_config);

		$site_srl = Context::get('site_srl');
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		Context::set('minigroup_info', $minigroup_info);

		// 전체 설정
		Context::set('minigroup_config', $oMinigroupModel->getConfig($site_srl));

		// 레이아웃 목록을 구함
		Context::set('layout_list', $oLayoutModel->getDownloadedLayoutList());

		// 모바일 레이아웃 목록
		$mlayout_list = $oLayoutModel->getDownloadedLayoutList('M');
		Context::set('mlayout_list', $mlayout_list);

		$admin_list = $oModuleModel->getSiteAdmin($site_srl);
		Context::set('admin_list', $admin_list);

		$grant_content = $this->getMinigroupGrantHTML($minigroup_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('setup');
	}

	function getMinigroupGrantHTML($module_srl, $source_grant_list)
	{
		if(!$module_srl)
		{
			return;
		}

		// get member module's config
		$oMemberModel = &getModel('member');
		$member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $member_config);

		$columnList = array('module_srl', 'site_srl');
		$oModuleModel = &getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
		// Grant virtual permission for access and manager
		$grant_list = new stdClass();
		$grant_list->access = new stdClass();
		$grant_list->access->title = Context::getLang('grant_access');
		$grant_list->access->default = 'member';
		if(count($source_grant_list))
		{
			foreach($source_grant_list as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new stdClass();
		$grant_list->manager->title = Context::getLang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);
		// Get a permission group granted to the current module
		$default_grant = array();
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$output = executeQueryArray('module.getModuleGrants', $args);
		if($output->data)
		{
			foreach($output->data as $val)
			{
				if($val->group_srl == 0) $default_grant[$val->name] = 'all';
				else if($val->group_srl == -1) $default_grant[$val->name] = 'member';
				else if($val->group_srl == -2) $default_grant[$val->name] = 'site';
				else if($val->group_srl == -3) $default_grant[$val->name] = 'manager';
				else
				{
					$selected_group[$val->name][] = $val->group_srl;
					$default_grant[$val->name] = 'group';
				}
			}
		}
		Context::set('selected_group', $selected_group);
		Context::set('default_grant', $default_grant);
		Context::set('module_srl', $module_srl);
		// Extract admin ID set in the current module
		$admin_member = $oModuleModel->getAdminId($module_srl);
		Context::set('admin_member', $admin_member);
		// Get a list of groups
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups($module_info->site_srl);

		// 그룹 이름을 언어 설정 값으로 치환
		$default_groups = Context::getLang("default_groups");
		foreach ($group_list as $group_srl => $group) {
			foreach ($group as $key => $value) {
				if ($key == "title") {
					$v = explode(">", $value);
					$userlang = $default_groups["$v[1]"];
					$group_list["$group_srl"]->title = $userlang;
					break;
				}
			} 
		} 
		Context::set('group_list', $group_list);

		$GLOBALS['lang']->minigroup_board = $default_groups['minigroup_board'];

		//Security			
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('admin_member..nick_name');

		// Get information of module_grants
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($this->module_path.'tpl', 'module_grants');
	}

	function dispMinigroupAdminDelete()
	{
		$site_srl = Context::get('site_srl');
		$oMinigroupModel = &getModel('minigroup');
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		Context::set('minigroup_info', $minigroup_info);

		$oModuleModel = &getModel('module');
		$admin_list = $oModuleModel->getSiteAdmin($site_srl);
		Context::set('admin_list', $admin_list);

		$this->setTemplateFile('delete');
	}

	function dispMinigroupAdminSkinSetup()
	{
		$oModuleAdminModel = &getAdminModel('module');
		$oMinigroupModel = &getModel('minigroup');

		$minigroup_config = $oMinigroupModel->getConfig(0);
		$skin_content = $oModuleAdminModel->getModuleSkinHTML($minigroup_config->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplateFile('skin_info');
	}

	function dispMinigroupAdminMobileSkinSetup()
	{
		$oModuleAdminModel = &getAdminModel('module');
		$oMinigroupModel = &getModel('minigroup');

		$minigroup_config = $oMinigroupModel->getConfig(0);
		$skin_content = $oModuleAdminModel->getModuleMobileSkinHTML($minigroup_config->module_srl);
		Context::set('skin_content', $skin_content);

		$this->setTemplateFile('skin_info');
	}

	/**
	 * @brief 홈페이지 기본 관리
	 **/
	function dispMinigroupAdminSiteManage()
	{
		$oModuleModel = &getModel('module');
		$oMenuAdminModel = &getAdminModel('menu');
		$oLayoutModel = &getModel('layout');
		$oMinigroupModel = &getModel('minigroup');
		$oMemberModel = &getModel('member');

		$minigroup_config = $oMinigroupModel->getConfig($this->site_srl);
		Context::set('minigroup_config', $minigroup_config);

		$member_config = $oMemberModel->getMemberConfig();
		Context::set('member_config', $member_config);

		$admin_list = $oModuleModel->getSiteAdmin($this->site_srl);
		Context::set('admin_list', $admin_list);

		// 다운로드 되어 있는 레이아웃 목록을 구함
		$layout_list = $oLayoutModel->getDownloadedLayoutList();
		Context::set('layout_list', $layout_list);

		// 레이아웃 정보 가져옴
		$this->selected_layout = $oLayoutModel->getLayout($this->minigroup_info->layout_srl);
		Context::set('selected_layout', $this->selected_layout);

		if(!Context::get('act')) Context::set('act', 'dispMinigroupManage');

		$args->site_srl = $this->site_srl;

		$minigroup_info = $oMinigroupModel->getMinigroupInfo($this->site_srl);
		$grant_content = $this->getMinigroupGrantHTML($minigroup_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);
		
		Context::set('index_mid', $minigroup_info->module_srl);

		$this->setTemplateFile('site_manage');
	}

	/**
	 * @brief 홈페이지 회원 그룹 관리
	 **/
	function dispMinigroupAdminSiteMemberGroupManage()
	{
		// 멤버모델 객체 생성
		$oMemberModel = &getModel('member');

		// group_srl이 있으면 미리 체크하여 selected_group 세팅
		$group_srl = Context::get('group_srl');
		if($group_srl)
		{
			$selected_group = $oMemberModel->getGroup($group_srl);
			Context::set('selected_group',$selected_group);
		}

		// group 목록 가져오기
		$group_list = $oMemberModel->getGroups($this->site_srl);
		Context::set('group_list', $group_list);

		$this->setTemplateFile('site_group_list');
	}

	/**
	 * @brief 홈페이지 모듈의 회원 관리
	 **/
	function dispMinigroupAdminSiteMemberManage()
	{
		$oMemberModel = &getModel('member');
		$oModuleModel = &getModel('module');

		// 회원 그룹을 구함
		$group_list = $oMemberModel->getGroups($this->site_srl);
		if(!$group_list) $group_list = array();
		Context::set('group_list', $group_list);

		// 회원 목록을 구함
		$args->selected_group_srl = Context::get('selected_group_srl');
		if(!isset($group_list[$args->selected_group_srl]))
		{
			$args->selected_group_srl = implode(',',array_keys($group_list));
		}

		//로그인 방식 확인
		$config = $oModuleModel->getModuleConfig('member');
		$identifier = ($config->identifier) ? $config->identifier : 'email_address';
		Context::set('identifier',$identifier);

		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));
		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'user_id' :
						if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
						$args->s_user_id = $search_keyword;
					break;
				case 'user_name' :
						if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
						$args->s_user_name = $search_keyword;
					break;
				case 'nick_name' :
						if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
						$args->s_nick_name = $search_keyword;
					break;
				case 'email_address' :
						if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
						$args->s_email_address = $search_keyword;
					break;
				case 'regdate' :
						$args->s_regdate = ereg_replace("[^0-9]","",$search_keyword);
					break;
				case 'regdate_more' :
						$args->s_regdate_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'regdate_less' :
						$args->s_regdate_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login' :
						$args->s_last_login = $search_keyword;
					break;
				case 'last_login_more' :
						$args->s_last_login_more = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'last_login_less' :
						$args->s_last_login_less = substr(ereg_replace("[^0-9]","",$search_keyword) . '00000000000000',0,14);
					break;
				case 'extra_vars' :
						$args->s_extra_vars = ereg_replace("[^0-9]","",$search_keyword);
					break;
			}
		}

		$query_id = 'member.getMemberListWithinGroup';
		$args->sort_index = "member.member_srl";
		$args->sort_order = "desc";
		$args->page = Context::get('page');
		$args->list_count = 40;
		$args->page_count = 5;
		$output = executeQuery($query_id, $args);

		$members = array();
		if(count($output->data))
		{
			foreach($output->data as $key=>$val)
			{
				$members[] = $val->member_srl;
			}
		}

		$members_groups = $oMemberModel->getMembersGroups($members, $this->site_srl);
		Context::set('members_groups',$members_groups);

		// 템플릿에 쓰기 위해서 context::set
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('member_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$this->setTemplateFile('site_member_list');
	}

	/**
	 * @brief 접속 통계
	 **/
	function dispMinigroupAdminSiteCounter()
	{
		// 정해진 일자가 없으면 오늘자로 설정
		$selected_date = Context::get('selected_date');
		if(!$selected_date) $selected_date = date("Ymd");
		Context::set('selected_date', $selected_date);

		// counter model 객체 생성
		$oCounterModel = &getModel('counter');

		// 전체 카운터 및 지정된 일자의 현황 가져오기
		$status = $oCounterModel->getStatus(array(0,$selected_date),$this->site_srl);
		Context::set('total_counter', $status[0]);
		Context::set('selected_day_counter', $status[$selected_date]);

		// 시간, 일, 월, 년도별로 데이터 가져오기
		$type = Context::get('type');
		if(!$type)
		{
			$type = 'day';
			Context::set('type',$type);
		}
		$detail_status = $oCounterModel->getHourlyStatus($type, $selected_date, $this->site_srl);
		Context::set('detail_status', $detail_status);

		// 표시
		$this->setTemplateFile('site_status');
	}

	/**
	 * @brief 게시판 권한 설정
	 **/
	function dispMinigroupAdminGrantInfo() {
		// get the grant infotmation from admin module
		$oMinigroupModel = &getModel('minigroup');
		$this->site_module_info = Context::get('site_module_info');
		$this->site_srl = $this->site_module_info->site_srl;
		$this->minigroup_info = $oMinigroupModel->getMinigroupInfo($this->site_srl);
		$grant_content = $this->getMinigroupGrantHTML($this->minigroup_info->module_srl, $this->xml_info->grant);
		Context::set('grant_content', $grant_content);

		$this->setTemplateFile('site_grant_list');
	}

	/**
	 * @brief 모듈 목록
	 **/
/*
	function dispMinigroupAdminSiteMidSetup()
	{
		// 현재 site_srl 에 등록된 것들을 가져오기
		$args->site_srl = $this->site_srl;
		$oModuleModel = &getModel('module');
		$mid_list = $oModuleModel->getMidList($args);
		$installed_module_list = $oModuleModel->getModulesXmlInfo();
		foreach($installed_module_list as $key => $val)
		{
			if($val->category != 'service') continue;
			$service_modules[$val->module] = $val;
		}

		if(count($mid_list))
		{
			foreach($mid_list as $key => $val)
			{
				$mid_list[$key]->setup_index_act = $service_modules[$val->module]->setup_index_act;
			}
		}
		Context::set('mid_list', $mid_list);

		$this->setTemplateFile('site_mid_list');
	}
*/
}
/* End of file minigroup.admin.view.php */
/* Location: ./modules/minigroup/minigroup.admin.view.php */