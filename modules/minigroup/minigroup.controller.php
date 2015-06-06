<?php
/**
 * @class  minigroupController
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의 controller class
 **/

class minigroupController extends minigroup
{
	var $site_module_info = null;
	var $site_srl = null;
	var $minigroup_info = null;
	var $selected_layout = null;

	function init()
	{
		$oModuleModel = &getModel('module');
		$oMinigroupModel = &getModel('minigroup');
		$oLayoutModel = &getModel('layout');

		$logged_info = Context::get('logged_info');
		if($this->act != 'procMinigroupCreateMinigroup' && $this->act != 'procMinigroupSiteSignUp' && $this->act != 'procMinigroupSiteLeave'
			&& !$oModuleModel->isSiteAdmin($logged_info)) return $this->stop('msg_not_permitted');

		// site_module_info값으로 홈페이지의 정보를 구함
		$this->site_module_info = Context::get('site_module_info');
		$this->site_srl = $this->site_module_info->site_srl;
		$this->minigroup_info = $oMinigroupModel->getMinigroupInfo($this->site_srl);
		$this->selected_layout = $oLayoutModel->getLayout($this->minigroup_info->layout_srl);
	}

	function procMinigroupCreateMinigroup()
	{
		global $lang;
		$oMinigroupAdminController = &getAdminController('minigroup');
		$oMinigroupModel = &getModel('minigroup');
		$oModuleModel = &getModel('module');

		if(!$oMinigroupModel->isCreationGranted()) return new Object(-1,'msg_not_permitted');

		$minigroup_id = Context::get('minigroup_id');
		if(!$minigroup_id) {
			do
			{
				$minigroup_id = 'mg'.date('ymdHisu');
			} while($oModuleModel->isIDExists($minigroup_id));
		}
		if($oModuleModel->isIDExists($minigroup_id)) return new Object(-1,'msg_not_enabled_id');
		$minigroup_title = Context::get('minigroup_title');
		if(!$minigroup_title) return new Object(-1,sprintf($lang->filter->isnull, $lang->minigroup_title));
		$minigroup_description = Context::get('minigroup_description');
		if(!$minigroup_description) return new Object(-1,sprintf($lang->filter->isnull, $lang->minigroup_description));

		$minigroup_config = $oMinigroupModel->getConfig();
		if($minigroup_config->access_type == 'vid') $domain = $minigroup_id;
		else $domain = $minigroup_config->default_domain.$minigroup_id;

		$oMinigroupAdminController->insertMinigroup($minigroup_title, $domain);
		if(!$oMinigroupAdminController->toBool()) return $output;

		$site_srl = $oMinigroupAdminController->get('site_srl');

		// 홈페이지 제목/내용 변경
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		$args->title = $minigroup_title;
		$args->description = $minigroup_description;
		$args->layout_srl = $minigroup_info->layout_srl;
		$args->mlayout_srl = $minigroup_info->mlayout_srl;
		$args->site_srl = $site_srl;
		$output = executeQuery('minigroup.updateMinigroup', $args);
		if(!$output->toBool()) return $output;

		$siteSetupPage = getSiteUrl($domain,'act','dispMinigroupAdminSiteManage','mid','minigroup_board','error_return_url','','ruleset','','minigroup_title','','minigroup_description','');
		$this->setRedirectUrl(str_replace("amp;","",$siteSetupPage));
	}

	function procMinigroupChangeLayout()
	{
		$oLayoutModel = &getModel('layout');
		$oLayoutAdminController = &getAdminController('layout');
		$oMinigroupModel = &getModel('minigroup');

		// 레이아웃 변경 권한 체크
		$minigroup_config = $oMinigroupModel->getConfig($this->site_srl);
		if($minigroup_config->enable_change_layout == 'N') return new Object('msg_not_permitted');

		$layout = Context::get('layout');
		if(!$layout || ($layout!='faceoff' && !is_dir(_XE_PATH_.'layouts/'.$layout))) return new Object(-1,'msg_invalid_request');

		// 원래 레이아웃 정보를 가져옴
		$layout_srl = $this->selected_layout->layout_srl;
		if($layout_srl)
		{
			$args->layout_srl = $layout_srl;
			$output = executeQuery('layout.getLayout', $args);
			if(!$output->toBool() || !$output->data) return $output;
			$layout_info = $output->data;
		}
		if($layout == $layout_info->layout) return new Object();

		$layout_info->layout = $layout;
		$output = $oLayoutAdminController->updateLayout($layout_info);
		if(!$output->toBool()) return $output;

		$oLayoutAdminController->initLayout($layout_srl, $layout);
	}

	function procMinigroupInsertMenuItem()
	{
		global $lang;

		$oMenuAdminModel = &getAdminModel('menu');
		$oMenuAdminController = &getAdminController('menu');
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
		$oMinigroupAdminController = &getAdminController('minigroup');
		$oMinigroupModel = &getModel('minigroup');

		// 기본 변수 체크
		$source_args = Context::getRequestVars();
		unset($source_args->body);
		unset($source_args->module);
		unset($source_args->act);
		unset($source_args->module_type);
		unset($source_args->module_id);
		unset($source_args->url);
		if($source_args->menu_open_window!="Y") $source_args->menu_open_window = "N";
		if($source_args->menu_expand !="Y") $source_args->menu_expand = "N";
		$source_args->group_srls = str_replace('|@|',',',$source_args->group_srls);
		$source_args->parent_srl = (int)$source_args->parent_srl;

		$module_type = Context::get('module_type');
		if(!$module_type)
		{
			$module_type = Context::get('mtype');
			if(!$module_type) return new Object(-1, 'msg_module_type_setting');
		}
		$browser_title = trim(Context::get('menu_name'));
		$url = trim(Context::get('url'));
		$module_id = trim(Context::get('module_id'));

		$mode = Context::get('mode');

		// minigroup config 구함
		$minigroup_config = $oMinigroupModel->getConfig($this->site_srl);


		// module_type이 url이 아니면 게시판 또는 페이지를 생성한다
		if($module_type != 'url' && $mode == 'insert')
		{
			// 해당 모듈의 개수 검사
			$module_count = $oModuleModel->getModuleCount($this->site_srl, $module_type);

			if($module_count > $minigroup_config->allow_service[$module_type]) return new Object(-1,'msg_module_count_exceed');

			if(!$browser_title) return new Object(-1, sprintf($lang->filter->isnull, $lang->browser_title));

			// 모듈 등록
			$idx = $module_count+1;
			$args->site_srl = $this->site_srl;
			$args->mid = $module_type.'_'.$idx;
			$args->browser_title = $browser_title;
			$args->layout_srl = $this->selected_layout->layout_srl;
			$args->module = $module_type;
			if($args->module == 'page') $args->page_type = 'WIDGET';
			$args->menu_srl = $source_args->menu_srl;
			$output = $oModuleController->insertModule($args);
			while(!$output->toBool())
			{
				$idx++;
				$args->mid = $module_type.'_'.$idx;
				$output = $oModuleController->insertModule($args);
			}
			if(!$output->toBool()) return $output;
			$module_id = $args->mid;

			$module_srl = $output->get('module_srl');
		}

		// 변수 정리 (form문의 column과 DB column이 달라서)
		$args->menu_srl = $source_args->menu_srl;
		$args->menu_item_srl = $source_args->menu_item_srl;
		$args->parent_srl = $source_args->parent_srl;
		$args->name = $source_args->menu_name;
		if($module_type=='url') $args->url = 'http://'.preg_replace('/^(http|https):\/\//i','',$url);
		else $args->url = $module_id;
		$args->open_window = $source_args->menu_open_window;
		$args->expand = $source_args->menu_expand;
		$args->group_srls = $source_args->group_srls;
		switch($mode)
		{
			case 'insert' :
					$args->menu_item_srl = getNextSequence();
					$args->listorder = -1*$args->menu_item_srl;
					$output = executeQuery('menu.insertMenuItem', $args);
					if(!$output->toBool()) return $output;
				break;
			case 'update' :
					$source_menu_info = $oMenuAdminModel->getMenuItemInfo($args->menu_item_srl);
					$root_menu_info = $oMenuAdminModel->getMenu($source_menu_info->menu_srl);
					if($this->site_srl != $root_menu_info->site_srl)
					{
						return new Object(-1,'msg_invalid_request');
					}
					$output = executeQuery('menu.updateMenuItem', $args);
					if(!$output->toBool()) return $output;

					if($module_type != 'url')
					{
						$oModuleModel = &getModel('module');
						$module_info = $oModuleModel->getModuleInfoByMid($source_menu_info->url, $this->site_srl);
						if($module_info->mid != $module_id || $module_info->browser_title != $browser_title)
						{
							$module_info->browser_title = $browser_title;
							$module_info->mid = $module_id;
							$oModuleController = &getController('module');
							$oModuleController->updateModule($module_info);
						}
					}
				break;
			default :
					return new Object(-1,'msg_invalid_request');
				break;
		}

		//버튼 업로드
		$args->normal_btn = $source_args->normal_btn;
		$args->hover_btn = $source_args->hover_btn;
		$args->active_btn = $source_args->active_btn;

		$args->menu_normal_btn = $source_args->menu_normal_btn;
		$args->menu_hover_btn = $source_args->menu_hover_btn;
		$args->menu_active_btn = $source_args->menu_active_btn;
		$btnOutput = $oMenuAdminController->_uploadButton($args);
		if($btnOutput['normal_btn'])
		{
			$args->normal_btn = $btnOutput['normal_btn'];
		}
		if($btnOutput['hover_btn'])
		{
			$args->hover_btn = $btnOutput['hover_btn'];
		}
		if($btnOutput['active_btn'])
		{
			$args->active_btn = $btnOutput['active_btn'];
		}

		// Button delete check
		if(!$btnOutput['normal_btn'] && $source_args->isNormalDelete == 'Y')
		{
			$args->normal_btn = '';
		}
		if(!$btnOutput['hover_btn'] && $source_args->isHoverDelete == 'Y')
		{
			$args->hover_btn = '';
		}
		if(!$btnOutput['active_btn'] && $source_args->isActiveDelete == 'Y')
		{
			$args->active_btn = '';
		}

		$output = executeQuery('menu.updateMenuItem', $args);

		// XML 파일을 갱신하고 위치을 넘겨 받음
		$xml_file = $oMenuAdminController->makeXmlFile($args->menu_srl);

		$this->setMessage('success_updated');
		$returnUrl = Context::get('success_return_url');
		$this->setRedirectUrl($returnUrl);
	}

	function procMinigroupDeleteMenuItem()
	{
		$oModuleModel = &getModel('module');
		$oMenuAdminModel = &getAdminModel('menu');
		$oMenuAdminController = &getAdminController('menu');
		$oMinigroupModel = &getModel('minigroup');
		$oModuleController = &getController('module');

		$menu_item_srl = Context::get('menu_item_srl');
		if(!$menu_item_srl) return new Object(-1,'msg_invalid_request');

		$menu_info = $oMenuAdminModel->getMenuItemInfo($menu_item_srl);
		if(!$menu_info || $menu_info->menu_item_srl != $menu_item_srl) return new Object(-1,'msg_invalid_request');

		$mid = $menu_info->url;
		$module_info = $oModuleModel->getModuleInfoByMid($mid, $this->site_srl);
		if($module_info->module_srl)
		{
			$minigroup_info = $oMinigroupModel->getMinigroupInfo($this->site_srl);
			if($minigroup_info->module_srl == $module_info->module_srl) return new Object(-1,'msg_default_mid_cannot_delete');
		}

		Context::set('menu_srl', $menu_info->menu_srl);
		$output = $oMenuAdminController->procMenuAdminDeleteItem();
		if(is_object($output) && !$output->toBool()) return $output;
		$this->add('xml_file', $oMenuAdminController->get('xml_file'));

		if($module_info && $module_info->module_srl) $output = $oModuleController->deleteModule($module_info->module_srl);
	}

	function procMinigroupDeleteButton()
	{
		$menu_srl = Context::get('menu_srl');
		$menu_item_srl = Context::get('menu_item_srl');
		$target = Context::get('target');
		$filename = Context::get('filename');
		FileHandler::removeFile($filename);

		$this->add('target', $target);
	}

	function procMinigroupMenuItemMove()
	{
		$menu_srl = Context::get('menu_srl');
		$mode = Context::get('mode');
		$parent_srl = Context::get('parent_srl');
		$source_srl = Context::get('source_srl');
		$target_srl = Context::get('target_srl');

		if(!$menu_srl || !$mode || !$target_srl) return new Object(-1,'msg_invalid_request');
		$oMenuAdminController = &getAdminController('menu');
		$xml_file = $oMenuAdminController->moveMenuItem($menu_srl,$parent_srl,$source_srl,$target_srl,$mode);
		$this->add('xml_file', $xml_file);
	}

	function procMinigroupDeleteGroup()
	{
		$oMemberAdminController = &getAdminController('member');
		$group_srl = Context::get('group_srl');
		$output = $oMemberAdminController->deleteGroup($group_srl, $this->site_srl);
		if(!$output->toBool()) return $output;
	}

	function procMinigroupInsertGroup()
	{
		if(!$this->site_srl) return new Object(-1,'msg_invalid_request');
		$vars = Context::getRequestVars();

		$oMemberModel = &getModel('member');
		$oModuleController = &getController('module');
		$oMemberAdminController = &getAdminController('member');

		$defaultGroup = $oMemberModel->getDefaultGroup($this->site_srl);
		$defaultGroupSrl = $defaultGroup->group_srl;
		$group_srls = $vars->group_srls;

		foreach($group_srls as $order=>$group_srl)
		{
			$isInsert = false;
			$update_args = new stdClass();
			$update_args->site_srl = $this->site_srl;
			$update_args->title = $vars->group_titles[$order];
			$update_args->description = $vars->descriptions[$order];
			$update_args->list_order = $order + 1;

			if(!$update_args->title) continue;
			if(is_numeric($group_srl))
			{
				$update_args->group_srl = $group_srl;
				$output = $oMemberAdminController->updateGroup($update_args);
			}
			else
			{
				$update_args->group_srl = getNextSequence();
				$output = $oMemberAdminController->insertGroup($update_args);
			}
			if($vars->defaultGroup == $group_srl)
			{
				$defaultGroupSrl = $update_args->group_srl;
			}
		}

		//set default group
		$default_args = $oMemberModel->getGroup($defaultGroupSrl);
		$default_args->is_default = 'Y';
		$default_args->group_srl = $defaultGroupSrl;
		$output = $oMemberAdminController->updateGroup($default_args);

		$this->setMessage(Context::getLang('success_updated').' ('.Context::getLang('msg_insert_group_name_detail').')');
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'dispMinigroupAdminSiteMemberGroupManage');
		$this->setRedirectUrl($returnUrl);
	}

	function procMinigroupDeleteMember()
	{
		$member_srl = Context::get('member_srl');
		if(!$member_srl) return new Object(-1,'msg_invalid_request');

		$args->site_srl= $this->site_srl;
		$args->member_srl = $member_srl;
		$output = executeQuery('member.deleteMembersGroup', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_deleted');
	}

	function procMinigroupUpdateMemberGroup()
	{
		if(!Context::get('cart')) return new Object();
		$args->site_srl = $this->site_srl;
		$args->member_srl = explode('|@|',Context::get('cart'));
		$args->group_srl = Context::get('group_srl');
		$oMemberController = &getController('member');
		return $oMemberController->replaceMemberGroup($args);
	}

	function procMinigroupInsertBoardGrant()
	{
		$module_srl = Context::get('module_srl');

		// 현 모듈의 권한 목록을 가져옴
		$oModuleModel = &getModel('module');
		$xml_info = $oModuleModel->getModuleActionXml('board');
		$grant_list = $xml_info->grant;

		if(count($grant_list))
		{
			foreach($grant_list as $key => $val)
			{
				$group_srls = Context::get($key);
				if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
			}
			$grants = serialize($arr_grant);
		}

		$oModuleController = &getController('module');
		$oModuleController->updateModuleGrant($module_srl, $grants);

		$this->add('module_srl', Context::get('module_srl'));
		$this->setMessage('success_registed');
	}


	function procMinigroupUpdateSiteConfig()
	{
		$output = $this->updateMinigroupInfo();
		if(!$output->toBool()) return $output;

		$this->updateSiteInfo();
		if(!$output->toBool()) return $output;

		$this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	function updateMinigroupInfo()
	{
		global $lang;
		$oMinigroupModel = &getModel('minigroup');
		$oMinigroupAdminController = &getAdminController('minigroup');
		$oModuleController = &getController('module');
		$oMemberModel = &getModel('member');
		$oMemberController = &getController('member');

		$site_srl = Context::get('site_srl');
		if(!$site_srl) return new Object(-1,'msg_invalid_request');

		$title = Context::get('minigroup_title');
		if(!$title) return new Object(-1,sprintf($lang->filter->isnull,$lang->minigroup_title));
		$description = Context::get('minigroup_description');

		// 홈페이지 제목/내용 변경
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		if(!$minigroup_info->site_srl) return new Object(-1,'msg_invalid_request');
		$args->title = $title;
		$args->description = $description;
		$args->layout_srl = $minigroup_info->layout_srl;
		$args->mlayout_srl = $minigroup_info->mlayout_srl;
		$args->site_srl = $minigroup_info->site_srl;
		$output = executeQuery('minigroup.updateMinigroup', $args);
		if(!$output->toBool()) return $output;

		// 아이콘 설정
		$minigroup_icon = Context::get('minigroup_icon');
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

		// 소모임 관리자 지정
		$output = $oMinigroupAdminController->setMinigroupSiteAdmin($site_srl, Context::get('minigroup_admin'));
		if(!$output->toBool()) return $output;

		// 설정 저장
		$vars = Context::getRequestVars();
		unset($vars->module);
		unset($vars->act);
		unset($vars->body);
		unset($args);

		if (!is_numeric($vars->default_group_no)) $vars->default_group_no = 3;

		$args->default_layout = $vars->default_layout;
		$args->default_mlayout = $vars->default_mlayout;
		$args->enable_change_layout = $vars->enable_change_layout;
		$args->signup_method = $vars->signup_method;
		$args->invitable_group = $vars->invitable_group;
		$args->expose_to_list = $vars->expose_to_list;
		$args->default_group_no = $vars->default_group_no;
		$args->lifespan_limited = $vars->lifespan_limited;
		$args->limit_date = $vars->limit_date;
		$args->disable_reading = $vars->disable_reading;

		$oModuleController->insertModulePartConfig('minigroup', $site_srl, $args);

		$oMinigroupAdminController->setMinigroupSiteDefaultGroup($site_srl, $vars->default_group_no);
		
		return $output;
	}

	function updateSiteInfo()
	{
		$index_mid = Context::get('index_mid');
		$lang_code = Context::get('language');

		if(!$index_mid || !$lang_code) return new Object(-1,'msg_invalid_request');

		$args->index_module_srl = $index_mid;
		$args->default_language = $lang_code;
		$args->domain = $this->minigroup_info->domain;
		$args->site_srl= $this->site_srl;
		$oModuleController = &getController('module');
		$output = $oModuleController->updateSite($args);

		return $output;
	}

	function procMinigroupUpdateLayoutConfig()
	{
		if(Context::get('layout')) $this->procMinigroupChangeLayout();
		$layout_srl = Context::get('layout_srl');
		if(!$layout_srl || $layout_srl!=$this->selected_layout->layout_srl) exit();
		$oLayoutAdminController = &getAdminController('layout');
		$oLayoutAdminController->procLayoutAdminUpdate();
		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	function triggerMemberMenu(&$content)
	{
		$site_module_info = Context::get('site_module_info');
		$logged_info = Context::get('logged_info');
		if(!$site_module_info->site_srl || !$logged_info->member_srl) return new Object();

		if($logged_info->is_admin == 'Y' || $logged_info->is_site_admin)
		{
			$oMinigroupModel = &getModel('minigroup');
			$oMemberController = &getController('member');
			$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_module_info->site_srl);
			if($minigroup_info->site_srl) $oMemberController->addMemberMenu('dispMinigroupAdminSiteManage','cmd_minigroup_setup');
		}
		return new Object();
	}

	function triggerApplyLayout(&$oModule)
	{
		// It does not site, return
		$site_module_info = Context::get('site_module_info');
		if($site_module_info->site_srl<1) return new Object();

		// If XMLRPC, JSON, do return
		if(in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) return new Object();

		// If admin page, do return
		if(Context::get('module')=='admin') return new Object();

		// If popup layout, do return
		if(!$oModule || $oModule->getLayoutFile()=='popup_layout.html') return new Object();

		$site_srl = $site_module_info->site_srl;

		$args->site_srl = $site_srl;
		$output = executeQuery('minigroup.getMinigroup', $args);
		$layout_srl = $output->data->layout_srl;

		if(!$layout_srl) return new Object();

		$current_module_info = Context::get('current_module_info');

		$oModule->module_info->layout_srl = $layout_srl;
		$current_module_info->layout_srl = $layout_srl;

		Context::set('current_module_info', $current_module_info);

		Context::set('is_minigroup', true);

		$oMinigroupModel = &getModel('minigroup');
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		Context::set('minigroup_info', $minigroup_info);

		return new Object();
	}

	function triggerApplyMLayout(&$oModule)
	{
		// It does not site, return
		$site_module_info = Context::get('site_module_info');
		if($site_module_info->site_srl<1) return new Object();

		// If XMLRPC, JSON, do return
		if(in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) return new Object();

		// If admin page, do return
		if(Context::get('module')=='admin') return new Object();

		$site_srl = $site_module_info->site_srl;

		$args->site_srl = $site_srl;
		$output = executeQuery('minigroup.getMinigroup', $args);
		$mlayout_srl = $output->data->mlayout_srl;

		if(!$mlayout_srl) return new Object();

		$current_module_info = Context::get('current_module_info');
		$oModule->mlayout_srl = $mlayout_srl;
		$current_module_info->mlayout_srl = $mlayout_srl;

		$oModule->use_mobile = 'Y';
		$current_module_info->use_mobile = 'Y';

		Context::set('current_module_info', $current_module_info);

		Context::set('is_minigroup', true);

		$oMinigroupModel = &getModel('minigroup');
		$minigroup_info = $oMinigroupModel->getMinigroupInfo($site_srl);
		Context::set('minigroup_info', $minigroup_info);

		return new Object();
	}
	
	function procMinigroupSiteSignUp() {
		$oMinigroupModel = &getModel("minigroup");
		$oMemberModel = &getModel('member');
		$oMemberController = &getController("member");

		$site_module_info = Context::get('site_module_info');
		$logged_info = Context::get('logged_info');
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) return new Object(-1,'msg_invalid_request');

		$site_srl = $site_module_info->site_srl;
		$columnList = array('site_srl', 'group_srl', 'title');

		$minigroup_config = $oMinigroupModel->getConfig($site_srl);

		if ($minigroup_config->signup_method == "inviting") return new Object(-1,'msg_signup_disabled');

		$default_group = $oMemberModel->getDefaultGroup($site_srl, $columnList);

		$oMemberController->addMemberToGroup($logged_info->member_srl, $default_group->group_srl, $site_srl);
		$groups[$default_group->group_srl] = $default_group->title;
		$logged_info->group_list = $groups;
	}

	function procMinigroupSiteLeave() {
		$oModuleModel = &getModel("module");
		$oMemberController = &getController("member");

		$site_module_info = Context::get('site_module_info');
		$logged_info = Context::get('logged_info');
		if(!$site_module_info->site_srl || !Context::get('is_logged') || count($logged_info->group_srl_list) ) return new Object(-1,'msg_invalid_request');

		if ($oModuleModel->isSiteAdmin($logged_info)) return new Object(-1,'msg_cannot_leave_admin');
		$args = new stdClass;
		$args->site_srl= $site_module_info->site_srl;
		$args->member_srl = $logged_info->member_srl;
		$output = executeQuery('member.deleteMembersGroup', $args);
		if(!$output->toBool()) return $output;
		$this->setMessage('success_deleted');
		$oMemberController->_clearMemberCache($args->member_srl, $site_module_info->site_srl);
	}
}
/* End of file minigroup.controller.php */
/* Location: ./modules/minigroup/minigroup.controller.php */