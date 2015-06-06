<?php
/**
 * @class  minigroupMobile
 * @author (kndol@kndol.net)
 * @brief  minigroup 모듈의 모바일 class
 **/

require_once(_XE_PATH_.'modules/minigroup/minigroup.view.php');

class minigroupMobile extends minigroupView
{
	function init()
	{
		$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		if(!is_dir($template_path)||!$this->module_info->skin)
		{
			$this->module_info->mskin = 'minigroup_default';
			$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->module_info->mskin);
		}

		$site_srl = Context::get('site_srl');
		if($site_srl)
		{
			$oMinigroupModel = &getModel('minigroup');
			$args->site_srl = $site_srl;
			$siteInfo = $oMinigroupModel->getMinigroupInfo($args->site_srl);
			Context::set('siteInfo', $siteInfo);
		}

		$this->setTemplatePath($template_path);
	}
}
/* End of file minigroup.mobile.php */
/* Location: ./modules/minigroup/minigroup.mobile.php */