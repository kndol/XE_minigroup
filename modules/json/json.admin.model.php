<?php
    /**
     * Json Admin Display Model Class
     * 
     * @class  JsonAdminModel
     * @author KnDol (kndol@kndol.net)
     **/
 
    class jsonAdminModel extends json {
 
        /**
         * @brief 초기화
         **/
        function init() {
 
        }
         
        /**
         * @brief 모듈정보 return
         **/
        function getModuleInfo() {
            static $module_info = null;
            if(is_null($module_info)) {
                 
                // module module_info의 값을 구함
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleConfig('json');
 
                $skin_info->module_srl = $module_info->module_srl;
                $oModuleModel->syncSkinInfoToModuleInfo($skin_info);
 
                // stopsmoking dummy module의 is_default 값을 구함
                $dummy = $oModuleModel->getModuleInfoByMid($module_info->mid);
                $module_info->is_default = $dummy->is_default;
                $module_info->module_srl = $dummy->module_srl;
                $module_info->browser_title = $dummy->browser_title;
                $module_info->layout_srl = $dummy->layout_srl;
   
                unset($module_info->grants);
            }
            return $module_info;
        }

		function getAdminMenuItems() {
			$menu_srl = Context::get('menu_srl');

			$oMenuAdminModel = &getAdminModel('menu');
			$oModuleController = &getController('module');
			$oJsonAdminController = &getAdminController('json');
			
			if ($menu_srl) {
				$oMenuItems = $oMenuAdminModel->getMenuItems($menu_srl)->data;
				if($oMenuItems) {
					if(!is_array($oMenuItems))
						$oMenuItems = array($oMenuItems);
					$oMenuItems = parent_sort($oMenuItems);
					foreach ($oMenuItems as &$menu_item) {
					 	$oModuleController->replaceDefinedLangCode($menu_item->name);
					}
				}
			}
			$this->add('menu_items', $oMenuItems);
		}
    }
?>
