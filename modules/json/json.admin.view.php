<?php
    /**
     * Json Admin Display Control Class
     * 
     * @class  JsonAdminView
     * @author KnDol (kndol@kndol.net)
     **/

    class jsonAdminView extends json {

        /**
         * @brief 초기화
         **/
        function init() {
        }
		
		function dispJsonAdminSetup() {
			$oModuleModel = &getModel('module');
			$oMenuAdminModel = &getAdminModel('menu');

			$config = $oModuleModel->getModuleConfig('json');

			$oMenuList = $oMenuAdminModel->getMenuList()->data;
			if($oMenuList && !is_array($oMenuList))
				$oMenuList = array($oMenuList);
			foreach($oMenuList as $menu_list) {
				$menu_list->items_count = count($oMenuAdminModel->getMenuItems($menu_list->menu_srl)->data);
			}

			if ($config->menu_srl) {
				$oMenuItems = $oMenuAdminModel->getMenuItems($config->menu_srl)->data;
				if($oMenuItems) {
					if(!is_array($oMenuItems))
						$oMenuItems = array($oMenuItems);
debugPrint($oMenuItems);
					$oMenuItems = parent_sort($oMenuItems);
debugPrint($oMenuItems);
				}
			}

			Context::set('config', $config);
			Context::set('module_info',$oModuleModel->getModuleInfoXml('json'));
			Context::set('menu_list', $oMenuList);
			Context::set('menu_items', $oMenuItems);
			
			// 템플릿정보를 가져온다.
            $this->setTemplatePath($this->module_path."/tpl/");
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
            // 템플릿파일이름 정하기
            $this->setTemplateFile('config');
        }
    }
?>