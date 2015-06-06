<?php
/**
 * @class  minigroupModel
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의  model class
 **/

class minigroupModel extends minigroup
{

	var $site_module_info = null;
	var $site_srl = 0;

	function init()
	{
		// site_module_info값으로 홈페이지의 정보를 구함
		$this->site_module_info = Context::get('site_module_info');
		$this->site_srl = $this->site_module_info->site_srl;
	}

	function getConfig($site_srl = 0)
	{
		static $configs = array();

		$oModuleModel = &getModel('module');

		if(!isset($configs[$site_srl]))
		{
			$config = $oModuleModel->getModuleConfig('minigroup');
			if(!$config)
			{
				$config->default_layout = 'KnDol_AppStyle';
				$config->enable_change_layout = 'N';
				$config->creation_group = array();
				$config->minigroup_main_mid = 'minigroups';
				$config->skin = 'minigroup_default';
				$config->access_type = 'vid';
				$config->default_domain = '';
			}
			else
			{
				$config->creation_group = explode(',',$config->creation_group);
				if(!isset($config->minigroup_main_mid)) $config->minigroup_main_mid = 'minigroups';
				if(!isset($config->skin)) $config->skin = 'minigroup_default';
				if(!isset($config->access_type)) $config->access_type = 'vid';
				if($config->default_domain)
				{
					if(strpos($config->default_domain,':')===false) $config->default_domain = 'http://'.$config->default_domain;
					if(substr($config->default_domain,-1)!='/') $config->default_domain .= '/';
				}
			}

			if($site_srl)
			{
				$part_config = $oModuleModel->getModulePartConfig('minigroup', $site_srl);
				if(!$part_config) $part_config = $config;
				else $config = $part_config;
			}
			$configs[$site_srl] = $config;
		}

		return $configs[$site_srl];
	}

	function isCreationGranted($member_info = null)
	{
		if(!$member_info) $member_info = Context::get('logged_info');
		if($member_info->is_admin == 'Y') return true;

		$config = $this->getConfig(0);

		switch($config->creation_default)
		{
			case 'member':
				if(Context::get('is_logged')) return true;
			break;

			case 'group':
				if(!is_array($member_info->group_list) || !count($member_info->group_list) || !count($config->creation_group)) return false;
				$keys = array_keys($member_info->group_list);
				for($i=0,$c=count($keys);$i<$c;$i++)
				{
					if(in_array($keys[$i],$config->creation_group)) return true;
				}
			break;
		}

		return false;
	}

	function getMinigroupInfo($site_srl)
	{
		static $infos = array();
		if(!isset($infos[$site_srl]))
		{
			$args->site_srl = $site_srl;
			$output = executeQuery('minigroup.getMinigroupInfo', $args);
			if(!$output->toBool() || !$output->data)
			{
				$infos[$site_srl] = null;
			}
			else
			{
				$icon_src = 'files/attach/minigroup_icon/'.$site_srl.'.png';
				if(file_exists(_XE_PATH_.$icon_src)) $output->data->minigroup_icon = $icon_src.'?rnd='.filemtime(_XE_PATH_.$icon_src);
				$output->data->layout_srl = $output->data->minigroup_layout_srl;
				$output->data->mlayout_srl = $output->data->minigroup_mlayout_srl;
				$infos[$site_srl] = $output->data;
			}
		}
		return $infos[$site_srl];
	}

	function getMinigroupMenuItem()
	{
		$node_srl = Context::get('node_srl');
		if(!$node_srl) return new Object(-1,'msg_invalid_request');

		$oMenuAdminModel = &getAdminModel('menu');
		$menu_info = $oMenuAdminModel->getMenuItemInfo($node_srl);

		if(!preg_match('/^http/i',$menu_info->url))
		{
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid($menu_info->url, $this->site_srl);
			if($module_info->mid == $menu_info->url)
			{
				$menu_info->module_type = $module_info->module;
				$menu_info->module_id = $module_info->mid;
				$menu_info->browser_title = $module_info->browser_title;
				unset($menu_info->url);
			}
		}
		else
		{
			$menu_info->module_type = 'url';
			$menu_info->url = preg_replace('/^(http|https):\/\//i','',$menu_info->url);
		}
		$this->add('menu_info', $menu_info);
	}

	function getMinigroupMenuTplInfo()
	{
		// 해당 메뉴의 정보를 가져오기 위한 변수 설정
		$menu_item_srl = Context::get('menu_item_srl');
		$parent_srl = Context::get('parent_srl');
		$mode = Context::get('mode');

		// 홈페이지 정보
		$oModuleModel = &getModel('module');
		// 회원 그룹의 목록을 가져옴
		$oMemberModel = &getModel('member');
		$group_list = $oMemberModel->getGroups($this->site_srl);
		Context::set('group_list', $group_list);

		// parent_srl이 있고 menu_item_srl이 없으면 하부 메뉴 추가임
		$oMenuAdminModel =  &getAdminModel('menu');
		if($mode == 'insert')
		{
			// 상위 메뉴의 정보를 가져옴
			$parent_info = $oMenuAdminModel->getMenuItemInfo($parent_srl);
			$item_info->parent_srl = $parent_srl;

		// root에 메뉴 추가하거나 기존 메뉴의 수정일 경우
		}
		else if ($mode == 'update')
		{
			// menu_item_srl 이 있으면 해당 메뉴의 정보를 가져온다
			if($menu_item_srl) $item_info = $oMenuAdminModel->getMenuItemInfo($menu_item_srl);
		}

		if(!preg_match('/^http/i',$item_info->url))
		{
			$oModuleModel = &getModel('module');
			$module_info = $oModuleModel->getModuleInfoByMid($item_info->url, $this->site_srl);
			if($module_info->mid == $item_info->url)
			{
				$item_info->module_type = $module_info->module;
				$item_info->module_id = $module_info->mid;
				$item_info->browser_title = $module_info->browser_title;
				unset($item_info->url);
			}
		}
		else
		{
			$item_info->module_type = 'url';
			$item_info->url = preg_replace('/^(http|https):\/\//i','',$item_info->url);
		}
		Context::set('item_info', $item_info);
		$minigroup_config = $this->getConfig($this->site_srl);
		if(count($minigroup_config->allow_service))
		{
			foreach($minigroup_config->allow_service as $k => $v)
			{
				if($v<1) continue;
				$c = $oModuleModel->getModuleCount($this->site_srl, $k);
				$minigroup_config->allow_service[$k] -= $c;
			}
		}
		Context::set('minigroup_config', $minigroup_config);

		// template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'minigroup_menu_item_info');

		$oModuleController = &getController('module');
		$oModuleController->replaceDefinedLangCode($tpl);
		$this->add('tpl',$tpl);
	}
}
/* End of file minigroup.model.php */
/* Location: ./modules/minigroup/minigroup.model.php */