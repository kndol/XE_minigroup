<?php
/**
 * @class  minigroupAdminController
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의 admin controller class
 **/

class minigroupAdminController extends minigroup
{
	function init()
	{
	}

	/**
	 * @brief 소모임 설정
	 **/
	function procMinigroupAdminInsertConfig()
	{
		global $lang;
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
		$oMinigroupModel = &getModel('minigroup');
		$oMinigroupAdminModel = &getAdminModel('minigroup');
		$vars = Context::getRequestVars();
		unset($vars->module);
		unset($vars->act);
		unset($vars->body);
		$args->default_layout = $vars->default_layout;
		$args->default_mlayout = $vars->default_mlayout;
		$args->enable_change_layout = $vars->enable_change_layout;

		$site_srl = $vars->site_srl;
		if($site_srl)
		{
			// 개별 소모임인 경우
			if (!is_numeric($vars->default_group_no)) $vars->default_group_no = 3;

			$args->signup_method = $vars->signup_method;
			$args->invitable_group = $vars->invitable_group;
			$args->expose_to_list = $vars->expose_to_list;
			$args->default_group_no = $vars->default_group_no;
			$args->lifespan_limited = $vars->lifespan_limited;
			$args->limit_date = $vars->limit_date;
			$args->disable_reading = $vars->disable_reading;

			$oModuleController->insertModulePartConfig('minigroup', $site_srl, $args);
			$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);

			$args->site_srl = $vars->site_srl;
			$args->default_layout = $vars->default_layout;
			$this->insertLayout($args,$minigroup_info);
			$args->default_layout = $vars->default_mlayout;
			$this->insertLayout($args,$minigroup_info,'M');

			$this->setMinigroupSiteDefaultGroup($site_srl, $vars->default_group_no);
		}
		else
		{
			// 기본 정보인 경우
			$args->access_type = $vars->access_type;
			$args->default_domain = $vars->default_domain;
			if(strpos($args->default_domain,':')===false) $args->default_domain = 'http://'.$args->default_domain;
			if(substr($args->default_domain,-1)!='/') $args->default_domain .= '/';
			if($args->access_type != 'vid' && !$args->default_domain) return new Object(-1,sprintf($lang->filter->isnull, $lang->domain));

			$args->minigroup_main_mid = $vars->minigroup_main_mid;
			$args->browser_title = $vars->browser_title;
			if(!$args->browser_title) $args->browser_title = $lang->minigroup;
			if(!$args->minigroup_main_mid) return new Object(-1,sprintf($lang->filter->isnull,$lang->minigroup_main_mid));
			$args->board_skin = $vars->board_skin;
			$args->use_mobile = $vars->use_mobile;
			$args->skin = $vars->skin;
			$args->layout_srl = $vars->layout_srl;
			$args->mskin = $vars->mskin;
			$args->mlayout_srl = $vars->mlayout_srl;
			$args->board_mskin = $vars->board_mskin;

			if(!$args->skin) $args->skin = 'minigroup_default';

			$minigroup_config = $oMinigroupModel->getConfig(0);
			$mid = $minigroup_config->minigroup_main_mid;
			$module_info = $oModuleModel->getModuleInfoByMid($mid, 0);

			$module_args->module = 'minigroup';
			$module_args->site_srl = 0;
			$module_args->mid = $args->minigroup_main_mid;
			$module_args->skin = $args->skin;
			$module_args->board_skin = $args->board_skin;
			$module_args->browser_title = $args->browser_title;
			$module_args->layout_srl = $vars->layout_srl;
			$module_args->use_mobile = $args->use_mobile;
			$module_args->mskin = $args->mskin;
			$module_args->board_mskin = $args->board_mskin;
			$module_args->mlayout_srl = $args->mlayout_srl;

			if(!$module_info->module_srl)
			{
				$output = $oModuleController->insertModule($module_args);
				if(!$output->toBool()) return $output;
			}
			else
			{
				$module_args->module_srl = $module_info->module_srl;

				$output = $oModuleController->updateModule($module_args);
				if(!$output->toBool()) return $output;
			}

			$module_info = $oModuleModel->getModuleInfoByMid($mid, 0);
			$args->module_srl = $module_info->module_srl;
			if(in_array($vars->creation_default,array('member','group')))
			{
				$args->creation_default = $vars->creation_default;
				$args->creation_group = implode(',',explode('|@|',$vars->creation_group));
			}
                $args->layout_srl = $vars->layout_srl;
			$oModuleController->insertModuleConfig('minigroup', $args);
		}
	}

	function insertLayout($args,$minigroup_info,$layout_type = "P")
	{
		$oLayoutModel = &getModel('layout');
		$oLayoutAdminController = &getAdminController('layout');
		if($layout_type != 'M')
			$layout_srl = $minigroup_info->layout_srl;
		else
			$layout_srl = $minigroup_info->mlayout_srl;

		$layout_info = $oLayoutModel->getLayout($layout_srl);

		if(!$layout_info || $layout_info->layout != $args->default_layout)
		{
			if($layout_info->layout_srl)
			{
				$output = $oLayoutAdminController->deleteLayout($layout_info->layout_srl);
			}

			// don't insert layout
			if($layout_type == 'M' && !(bool)$args->default_layout)
			{
				$home_args->mlayout_srl = 0;
				$home_args->site_srl = $args->site_srl;
			}
			else
			{
				if($layout_info->extra_var && count($layout_info->extra_var))
				{
					foreach($layout_info->extra_var as $key => $val) $extra_vars->{$key} = $val->value;
				}
				$extra_vars->main_menu = $minigroup_info->menu_srl;
				$extra_vars->logo_text = $minigroup_info->title;
				$layout_args->extra_vars = serialize($extra_vars);

				$layout_args->layout_srl = getNextSequence();
				$layout_args->site_srl = $args->site_srl;
				$layout_args->layout = $args->default_layout;
				$layout_args->title = $minigroup_info->title;
				if($layout_type == 'M') $layout_args->layout_type = 'M';

				$output = $oLayoutAdminController->insertLayout($layout_args);

				if($layout_type != 'M')
					$home_args->layout_srl = $layout_args->layout_srl;
				else
					$home_args->mlayout_srl = $layout_args->layout_srl;
				$home_args->site_srl = $args->site_srl;
			}
			$output = executeQuery('minigroup.updateMinigroup', $home_args);
			return $output;
		}
		return;
	}
	/**
	 * @brief 접속 방법중 domain 이나 site id나 모두 sites 테이블의 domain 컬럼에 저장이 됨
	 * site id보다 domain이 우선 순위를 가짐
	 **/
	function procMinigroupAdminInsertMinigroup()
	{
		$title = Context::get('minigroup_title');

		$domain = preg_replace('/^(http|https):\/\//i','', trim(Context::get('domain')));
		$vid = trim(Context::get('minigroup_vid'));

		$description = Context::get('minigroup_description');

		if($domain && $vid) unset($vid);
		if(!$domain && $vid) $domain = $vid;

		if(!$title) return new Object(-1, 'msg_invalid_request');
		if(!$domain) return new Object(-1, 'msg_invalid_request');

		$output = $this->insertMinigroup($title, $domain, $description);

		if($this->get('site_srl')) $msg_code = 'success_updated';
		else $msg_code = 'msg_invalid_request';

		$this->setMessage($msg_code);

		if (Context::get('success_return_url') || !$this->get('site_srl'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispMinigroupAdminSetup', 'site_srl', $this->get('site_srl')));
		}


		return $output;
	}

	function insertMinigroup($title, $domain, $description)
	{
		$oModuleController = &getController('module');
		$oModuleAdminController = &getAdminController('module');
		$oModuleModel = &getModel('module');
		$oMinigroupModel = &getModel('minigroup');
		$oMinigroupController = &getController('minigroup');
		$oLayoutModel = &getModel('layout');
		$oLayoutController = &getAdminController('layout');
		$oMemberModel = &getModel('member');
		$oMemberController = &getController('member');
		$oMemberAdminController = &getAdminController('member');
		$oAddonController = &getAdminController('addon');
		$oEditorController = &getAdminController('editor');
		$oMenuAdminController = &getAdminController('menu');

		$info->title = $title;
		$info->domain = $domain;
		$info->description = $description;

		// virtual site 생성하고 site_srl을 보관
		$output = $oModuleController->insertSite($domain, 0);
		if(!$output->toBool()) return $output;
		$info->site_srl = $output->get('site_srl');

		$minigroup_config = $oMinigroupModel->getConfig(0);
		if(!$minigroup_config->default_layout) $minigroup_config->default_layout = 'minigroup_site';

		// 레이아웃 생성
		$info->layout_srl = $this->makeLayout($info->site_srl, $title,$minigroup_config->default_layout);
		$info->mlayout_srl = $this->makeLayout($info->site_srl, $title,$minigroup_config->default_mlayout,'M');

		// 기본 게시판 생성
		$board_info = $this->makeBoard($info->site_srl, 'minigroup_board', '$user_lang->minigroup_board', $oMinigroupModel->getConfig(), $info->layout_srl, $info->mlayout_srl);
		$info->module->minigroup_board_mid = $board_info->mid;
		// 게시판을 기본 페이지로 설정
		$info->module->home_srl = $board_info->module_srl;
		// 게시판 권한 설정
		$this->insertBoardGrant($board_info->module_srl);

		// 메뉴 생성
		$info->menu_srl = $this->makeMenu($info->site_srl, $title, 'Main Menu');
		// menu 설정
		$this->insertMenuItem($info->menu_srl, 0, $info->module->minigroup_board_mid, '$user_lang->minigroup_board');
		

		// layout의 설정
		$layout_args = $oLayoutModel->getLayout($info->layout_srl);
		$layout->colorset = 'white';

		// vid 형식일 경우
		if(isSiteID($domain)) $layout->index_url = getFullSiteUrl($domain, '');
		else $layout->index_url = 'http://'.$domain;
		$layout->main_menu = $info->menu_srl;

		$layout_args->extra_vars = serialize($layout);
		$oLayoutController->updateLayout($layout_args);

		// 생성된 게시판의 레이아웃 변경
		$menu_args->menu_srl = $info->menu_srl;
		$output = executeQueryArray('layout.getLayoutModules', $menu_args);
		$modules = array();
		foreach($info->module as $module_srl) $modules[] = $module_srl;
		$layout_module_args->layout_srl = $info->layout_srl;
		$layout_module_args->module_srls = implode(',',$modules);
		$output = executeQuery('layout.updateModuleLayout', $layout_module_args);

		//모바일 레이아웃
		if($info->mlayout_srl)
		{
	   		// layout의 설정
			$layout_args = $oLayoutModel->getLayout($info->mlayout_srl);

			// vid 형식일 경우
			if(isSiteID($domain)) $layout->index_url = getFullSiteUrl($domain, '');
			else $layout->index_url = 'http://'.$domain;
			$layout->main_menu = $info->menu_srl;

			$layout_args->extra_vars = serialize($layout);
			$oLayoutController->updateLayout($layout_args);

			// 생성된 게시판의 레이아웃 변경
			$menu_args->menu_srl = $info->menu_srl;
			$output = executeQueryArray('layout.getLayoutModules', $menu_args);
			$modules = array();
			foreach($info->module as $module_srl) $modules[] = $module_srl;
			$layout_module_args->layout_srl = $info->mlayout_srl;
			$layout_module_args->module_srls = implode(',',$modules);
			$output = executeQuery('layout.updateModuleLayout', $layout_module_args);
		}

		// 홈페이지 등록
		$args->site_srl = $info->site_srl;
		$args->title = $info->title;
		$args->description = $info->description;
		$args->layout_srl = $info->layout_srl;
		$args->mlayout_srl = $info->mlayout_srl;
		$args->menu_srl = $info->menu_srl;
		$args->list_order = $info->site_srl * -1;
		// for mobile skin - 12.05.31
		$output = executeQuery('minigroup.insertMinigroup', $args);

		// site의 index_module_srl 을 변경
		$site_args->site_srl = $info->site_srl;
		$site_args->index_module_srl = $info->module->home_srl;
		$site_args->domain = $domain;
		$oModuleController->updateSite($site_args);

		// 기본그룹 추가
		unset($args);
		$args->title = '$user_lang->default_group1';
		$args->is_default = 'N';
		$args->is_admin = 'N';
		$args->site_srl = $info->site_srl;
		$oMemberAdminController->insertGroup($args);

		unset($args);
		$args->title = '$user_lang->default_group2';
		$args->is_default = 'N';
		$args->is_admin = 'N';
		$args->site_srl = $info->site_srl;
		$oMemberAdminController->insertGroup($args);

		unset($args);
		$args->title = '$user_lang->default_group3';
		$args->is_default = 'Y';
		$args->is_admin = 'N';
		$args->site_srl = $info->site_srl;
		$oMemberAdminController->insertGroup($args);

		unset($args);
		$args->title = '$user_lang->default_group4';
		$args->is_default = 'N';
		$args->is_admin = 'Y';
		$args->site_srl = $info->site_srl;
		$oMemberAdminController->insertGroup($args);

		// 운영진 그룹에 추가
		$logged_info = Context::get('logged_info');
		$admin_group_srl = $oMemberModel->getAdminGroupSrl($info->site_srl);
		$member_group = $oMemberModel->getMemberGroups($logged_info->member_srl,$info->site_srl);
		$bAlreadyAdminGroup = false;
		foreach ($member_group as $group_srl => $group_name) {
			if ($group_srl == $admin_group_srl) {
			 	$bAlreadyAdminGroup = true;
			 	break;
			}
		} 
		if(!$bAlreadyAdminGroup) {
			$oMemberController->addMemberToGroup($logged_info->member_srl, $admin_group_srl, $info->site_srl);
		}

		//계정 로그인 방식 체크
		$member_config = $oMemberModel->getMemberConfig();
		if($member_config->identifier == 'email_address') $adminKey = $logged_info->email_address;
		else $adminKey = $logged_info->user_id;

		// 소모임 관리자 지정
		$oModuleController->insertSiteAdmin($info->site_srl, array($adminKey));

		// 사용자 언어 등록 - 소모임 게시판, 그룹 이름
		$this->registerUserLang($info->site_srl);

		// 기본 애드온 On
		$oAddonController->doInsert('autolink', $info->site_srl);
		$oAddonController->doInsert('counter', $info->site_srl);
		$oAddonController->doInsert('member_communication', $info->site_srl);
		$oAddonController->doInsert('member_extra_info', $info->site_srl);
		$oAddonController->doInsert('referer', $info->site_srl);
		$oAddonController->doInsert('resize_image', $info->site_srl);
		$oAddonController->doActivate('autolink', $info->site_srl);
		$oAddonController->doActivate('counter', $info->site_srl);
		$oAddonController->doActivate('member_communication', $info->site_srl);
		$oAddonController->doActivate('member_extra_info', $info->site_srl);
		$oAddonController->doActivate('referer', $info->site_srl);
		$oAddonController->doActivate('resize_image', $info->site_srl);
		$oAddonController->makeCacheFile($info->site_srl);

		// 기본 에디터 컴포넌트 On
		$oEditorController->insertComponent('colorpicker_text',true, $info->site_srl);
		$oEditorController->insertComponent('colorpicker_bg',true, $info->site_srl);
		$oEditorController->insertComponent('emoticon',true, $info->site_srl);
		$oEditorController->insertComponent('url_link',true, $info->site_srl);
		$oEditorController->insertComponent('image_link',true, $info->site_srl);
		$oEditorController->insertComponent('multimedia_link',true, $info->site_srl);
		$oEditorController->insertComponent('quotation',true, $info->site_srl);
		$oEditorController->insertComponent('table_maker',true, $info->site_srl);
		$oEditorController->insertComponent('poll_maker',true, $info->site_srl);
		$oEditorController->insertComponent('image_gallery',true, $info->site_srl);

		// 메뉴 XML 파일 생성
		$oMenuAdminController->makeXmlFile($info->menu_srl, $info->site_srl);

		//설정 저장
		$oModuleController = &getController('module');
		unset($args);
		$args->default_layout = $minigroup_config->default_layout;
		$args->default_mlayout = $minigroup_config->default_mlayout;
		$args->enable_change_layout = $minigroup_config->enable_change_layout;

		$args->use_mobile = $minigroup_config->use_mobile;
		$args->mskin = $minigroup_config->mskin;
		$args->mlayout_srl = $minigroup_config->mlayout_srl;
		$args->allow_service = $minigroup_config->allow_service;

		$site_srl = $info->site_srl;
		$oModuleController->insertModulePartConfig('minigroup', $site_srl, $args);

		$this->add('site_srl', $info->site_srl);
		$this->add('url', getSiteUrl($info->domain, ''));
	}

	function makeBoard($site_srl, $mid, $browser_title, $minigroup_config, $layout_srl, $mlayout_srl=0)
	{
		$args->site_srl = $site_srl;
		$args->module_srl = getNextSequence();
		$args->module = 'board';
		$args->mid = $mid;
		$args->browser_title = $browser_title;
		$args->is_default = 'N';
		$args->layout_srl = $layout_srl;
		$args->mlayout_srl = $mlayout_srl;
		$args->skin = $minigroup_config->board_skin;
		$args->mskin = $minigroup_config->board_mskin;

		$oModuleController = &getController('module');
		$output = $oModuleController->insertModule($args);

		$idx=0;
		while(!$output->toBool())
		{
			$idx++;
			$args->mid = $mid.'_'.$idx;
			$output = $oModuleController->insertModule($args);
		}

		return $args;
	}

	function insertBoardGrant($board_srl)
	{
		$oModuleController = &getController('module');

		$xml_grant = $this->xml_info->grant;

		$grant_list = new stdClass();
		$grant_list->access = array(-1);
		$grant_list->manager = array(-3);
		foreach ($xml_grant as $grant_name => $value) {
			switch ($value->default) {
				case 'member':
					$grant_list->{$grant_name} = array(-1);
					break;
				case 'site':
					$grant_list->{$grant_name} = array(-2);
					break;
				case 'manager':
				case 'root':
					$grant_list->{$grant_name} = array(-3);
					break;
			}
		} 

		$oModuleController->insertModuleGrants($board_srl, $grant_list);
	}

	function makeMenu($site_srl, $title, $menu_title)
	{
		$args->site_srl = $site_srl;
		$args->title = $title.' - '.$menu_title;
		$args->menu_srl = getNextSequence();
		$args->listorder = $args->menu_srl * -1;

		$output = executeQuery('menu.insertMenu', $args);
		if(!$output->toBool()) return $output;

		return $args->menu_srl;
	}

	function makeLayout($site_srl, $title, $layout,$layout_type = 'P')
	{
		if(!$layout) return false;
		$args->site_srl = $site_srl;
		$args->layout_srl = getNextSequence();
		$args->layout = $layout;
		$args->title = $title;
		$args->layout_type = $layout_type;

		$oLayoutAdminController = &getAdminController('layout');
		$output = $oLayoutAdminController->insertLayout($args);
		if(!$output->toBool()) return $output;
		return $args->layout_srl;
	}

	function insertMenuItem($menu_srl, $parent_srl = 0, $mid, $name)
	{
		// 변수를 다시 정리 (form문의 column과 DB column이 달라서)
		$args->menu_srl = $menu_srl;
		$args->menu_item_srl = getNextSequence();
		$args->parent_srl = $parent_srl;
		$args->name = $name;
		$args->url = $mid;
		$args->open_window = 'N';
		$args->expand = 'N';
		$args->normal_btn = null;
		$args->hover_btn = null;
		$args->active_btn = null;
		$args->group_srls = null;
		$args->listorder = $args->menu_item_srl*-1;
		$output = executeQuery('menu.insertMenuItem', $args);
		return $args->menu_item_srl;
	}

	function procMinigroupAdminUpdateMinigroup()
	{
		$oMinigroupModel = &getModel('minigroup');
		$oModuleController = &getController('module');
		$oMemberModel = &getModel('member');
		$oMemberController = &getController('member');

		// 소모임 이름, 설명, 접속 방법, 소모임 관리자 지정
		$args = Context::gets('site_srl','title','description','minigroup_admin','layout_srl','mlayout_srl');
		if(!$args->site_srl) return new Object(-1,'msg_invalid_request');

		if(Context::get('access_type')=='domain') $args->domain = Context::get('domain');
		else $args->domain = Context::get('minigroup_vid');
		if(!$args->domain) return new Object(-1,'msg_invalid_request');

		$minigroup_info = $oMinigroupModel->getMinigroupInfo($args->site_srl);
		if(!$minigroup_info->site_srl) return new Object(-1,'msg_invalid_request');

		// 소모임 관리자 지정
		$output = $this->setMinigroupSiteAdmin($minigroup_info->site_srl, $args->minigroup_admin);
		if(!$output->toBool()) return $output;

		// 소모임 이름, 설명 변경
		$output = executeQuery('minigroup.updateMinigroup', $args);
		if(!$output->toBool()) return false;

		// 아이콘 설정
		$minigroup_icon = Context::get('minigroup_icon');
		$icon_src = 'files/attach/minigroup_icon/'.$minigroup_info->site_srl.'.png';
		if (Context::get('icon_del') == 'Y')
		{
			FileHandler::removeFile($icon_src);
		}
		else if($minigroup_icon && (!is_uploaded_file($minigroup_icon['tmp_name']) || !preg_match('/\.(gif|jpeg|jpg|png)$/i',$minigroup_icon['name']) || !checkUploadedFile($minigroup_icon['tmp_name'])))
		{
			Context::set('error_messge', Context::getLang('msg_invalid_request'));
		}
		else
		{
			$tmp_arr = explode('.',$minigroup_icon['name']);
			$ext = $tmp_arr[count($tmp_arr)-1];

			$path = './files/attach/minigroup_icon/';
			$icon_src = sprintf('%s%d.%s', $path, $minigroup_info->site_srl, $ext);

			if(!is_dir($path)) FileHandler::makeDir($path);

			move_uploaded_file($minigroup_icon['tmp_name'], $icon_src);
		}

		// 도메인 변경
		$output = $oModuleController->updateSite($args);
		if(!$output->toBool()) return false;

		// 기본 레이아웃, 레이아웃 변경, 허용 서비스 변경
		$this->procMinigroupAdminInsertConfig();

		// 도메인 변경된 경우 캐시파일 재생성
		if($minigroup_info->domain != $args->domain && $minigroup_info->menu_srl)
		{
			$oMenuAdminController = &getAdminController('menu');
			$oMenuAdminController->makeXmlFile($minigroup_info->menu_srl);
		}

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
	}

	function procMinigroupAdminDeleteMinigroup()
	{
		$site_srl = Context::get('site_srl');
		if(!$site_srl) return new Object(-1,'msg_invalid_request');

		$oMinigroupModel = &getModel('minigroup');
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		if(!$minigroup_info->site_srl) return new Object(-1,'msg_invalid_request');

		$args->site_srl = $site_srl;

		// 홈페이지 정보 삭제
		$output = executeQuery('minigroup.deleteMinigroup', $args);
		if(!$output->toBool()) return $output;

		// 사이트 정보 삭제
		$output = executeQuery('module.deleteSite', $args);
		if(!$output->toBool()) return $output;

		// 사이트 관리자 삭제
		$output = executeQuery('module.deleteSiteAdmin', $args);
		if(!$output->toBool()) return $output;

		// 회원 그룹 매핑 데이터 삭제
		$output = executeQuery('member.deleteMemberGroup', $args);
		if(!$output->toBool()) return $output;

		// 회원 그룹 삭제
		$output = executeQuery('member.deleteSiteGroup', $args);
		if(!$output->toBool()) return $output;

		// 메뉴 삭제
		$oMenuAdminController = &getAdminController('menu');
		$output = $oMenuAdminController->deleteMenu($minigroup_info->menu_srl);
		if(!$output->toBool()) return $output;

		// 카운터 정보 삭제
		$oCounterController = &getController('counter');
		$oCounterController->deleteSiteCounterLogs($site_srl);

		// 애드온 삭제
		$oAddonController = &getController('addon');
		$oAddonController->removeAddonConfig($site_srl);

		// 에디터 컴포넌트 삭제
		$oEditorController = &getController('editor');
		$oEditorController->removeEditorConfig($site_srl);

		// 레이아웃 삭제
		Context::set('layout_srl', $minigroup_info->layout_srl);
		$oLayoutAdminController = &getAdminController('layout');
		$output = $oLayoutAdminController->procLayoutAdminDelete();
		if(!$output->toBool()) return $output;

		//모바일 레이아웃 삭제
		Context::set('mlayout_srl', $minigroup_info->mlayout_srl);
		$oLayoutAdminController = &getAdminController('layout', 'M');
		$output = $oLayoutAdminController->procLayoutAdminDelete();
		if(!$output->toBool()) return $output;

		// 게시판 삭제
		$oModuleModel = &getModel('module');
		$oModuleController =&getController('module');
		$mid_list = $oModuleModel->getMidList($args);
		foreach($mid_list as $key => $val)
		{
			$module_srl = $val->module_srl;
			$oModuleController->deleteModule($module_srl,$site_srl);
		}

		$this->deleteUserLang($site_srl);

		$icon_src = _XE_PATH_.'files/attach/minigroup_icon/'.$site_srl.'.png';
		if(file_exists($icon_src)) FileHandler::removeFile($icon_src);

		$this->setMessage('success_deleted');
	}

	function registerUserLang($site_srl)
	{
		$oModuleAdminController = &getAdminController('module');

		// 언어 코드 추출
		$supported_lang = Context::loadLangSupported();
		$now_lang_prefix = Context::getLangType();

		foreach($supported_lang as $lang_prefix => $lang_text)
		{
			Context::setLangType($lang_prefix);
			Context::loadLang(_XE_PATH_.'modules/minigroup/lang/');
			$default_groups = Context::getLang('default_groups');

			if(!count($default_groups)) {
				continue;
			}

			foreach($default_groups as $key => $val)
			{
				$defined_lang[$lang_prefix]->{$key} = $val;
			}
		}
		$default_groups = null;
		Context::setlangtype($now_lang_prefix);

		// 언어 코드 등록 (소모임 게시판, 그룹 이름 등)
		foreach($defined_lang as $lang_code => $v)
		{
			foreach($v as $key => $val)
			{
				unset($lang_args);
				$lang_args->site_srl = $site_srl;
				$lang_args->name = $key;
				$lang_args->lang_code = $lang_code;
				$lang_args->value = $val;
				$output = executeQuery('module.insertLang', $lang_args);
			}
		}
		$oModuleAdminController->makeCacheDefinedLangCode($site_srl);
	}

	function deleteUserLang($site_srl)
	{
		// 사용자 정의 언어 제거
		$lang_args->site_srl = $site_srl;
		$output = executeQuery('module.deleteLangs', $lang_args);
		if(!$output->toBool()) return $output;
		$lang_supported = Context::get('lang_supported');
		foreach($lang_supported as $key => $val)
		{
			$lang_cache_file = _XE_PATH_.'files/cache/lang_defined/'.$site_srl.'.'.$key.'.php';
			FileHandler::removeFile($lang_cache_file);
		}
	}

	/**
	 * @brief 주어진 번호의 그룹을 기본 그룹으로 설정
	 **/
	function setMinigroupSiteDefaultGroup($site_srl, $group_no)
	{
		if (!is_numeric($group_no)) return new Object(-1,'msg_invalid_request');
		$oMemberModel = &getModel('member');
		$oMemberAdminController = &getAdminController('member');

		$group_name = sprintf("default_group%d", $group_no);
		$group_list = $oMemberModel->getGroups($site_srl);
		foreach ($group_list as $group_srl => $group)
		{
			foreach ($group as $key => $value)
			{
				if ($key != "title") continue;
				$group->is_default = (strpos($value, $group_name) !== false) ? "Y" : "N";
				$oMemberAdminController->updateGroup($group);
				break;
			}
		}
	}

	/**
	 * @brief 소모임 관리자 지정
	 *        파라메터: $site_srl        - 미니그룹 사이트 srl
	 *                  $minigroup_admin - ','로 연결된 관리자 아이디들로 이뤄진 문자열
	 **/
	function setMinigroupSiteAdmin($site_srl, $minigroup_admin)
	{
		$minigroup_admin = trim($minigroup_admin);

		if(strlen($minigroup_admin))
		{
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			$oMinigroupAdminModel = &getAdminModel('minigroup');

			$admin_list = explode(',',$minigroup_admin);

			// 새로운 사이트 관리자 지정
			$output = $oModuleController->insertSiteAdmin($site_srl, $admin_list);
			if(!$output->toBool()) return $output;

			// 이전 사이트 관리자의 그룹을 정회원으로 변경
			$args->site_srl = $site_srl;
			$args->member_srl = $oMinigroupAdminModel->getMinigroupSiteStaffMemberSrls($site_srl);
			$args->group_srl = $oMinigroupAdminModel->getMinigroupSiteGroupSrl($site_srl, MEMBER);
			$output = $oMemberController->replaceMemberGroup($args);
			if(!$output->toBool()) return $output;

			// 새로운 사이트 관리자의 그룹을 운영진으로 변경
			$args->member_srl = $oMinigroupAdminModel->getMinigroupMemberSrlsFromMemberList($minigroup_admin);
			$args->group_srl = $oMemberModel->getAdminGroupSrl($site_srl);
			$output = $oMemberController->replaceMemberGroup($args);
		}
		else {
			return new Object(-1,'msg_admin_must_exist');
		}
		return $output;
	}
}
/* End of file minigroup.admin.controller.php */
/* Location: ./modules/minigroup/minigroup.admin.controller.php */