<?php
	require_once(_XE_PATH_ . 'modules/socialxe/socialxe.view.php');
	
	/**
	 * @class  socialxeMobile
	 * @author CONORY (http://www.conory.com)
	 * Mobile class of socialxe module
	 */
	class socialxeMobile extends socialxeView
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
			Context::set('config', $this->config);
			
            $template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->config->skin);
            $this->setTemplatePath($template_path);
			
            Context::addJsFile($this->module_path.'tpl/js/socialxe.js');
			
			//사용자 모바일 레이아웃
			if($this->config->mlayout_srl){
				$oLayoutModel = getModel('layout');
				$layout_info = $oLayoutModel->getLayout($this->config->mlayout_srl);
				if($layout_info){
					$this->module_info->mlayout_srl = $this->config->mlayout_srl;
					$this->setLayoutPath($layout_info->path);
				}
			}
		}
	}