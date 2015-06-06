<?php
/* Copyright (C) 큰돌넷 <http://www.kndol.net> */
/**
 * @class my_minigroups
 * @author 큰돌 (kndol@kndol.net)
 * @brief 내 소모임 목록 보여주기
 * @version 0.1
 */
class my_minigroups extends WidgetHandler
{
	/**
	 * @brief Widget handler
	 *
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */

	function proc($args)
	{
		// Set variables used internally
		$oModuleModel = &getModel('module');
		$oTemplate = &TemplateHandler::getInstance();
		$modules_info = array();
		$logged_info = Context::get('logged_info');
		$site_module_info = Context::get('site_module_info');

		$obj = new stdClass();
		if(!$args->module_srls)
		{
			$modules_info = $oModuleModel->getMidList($obj);
		}
		else
		{
			$obj->module_srls = $args->module_srls;
			$output = executeQueryArray('widgets.content.getMids', $obj);
			if($output->data)
			{
				foreach($output->data as $key => $val)
				{
					$modules_info[$val->mid] = $val;
				}
			}
		}
		
		Context::set('modules_info', $modules_info);

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
			Context::set('my_minigroups_page_navigation', $output->page_navigation);
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
		}

		Context::set('colorset', $args->colorset);

		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);

		return $oTemplate->compile($tpl_path, "my_minigroups");
	}
}

/* End of file my_minigroups.class.php */
/* Location: ./widgets/my_minigroups/my_minigroups.class.php */
