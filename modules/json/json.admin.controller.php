<?php
    /**
     * Json Admin Procedure Control Class
     * 
     * @class  JsonAdminController
     * @author KnDol (kndol@kndol.net)
     **/

    class jsonAdminController extends json {

        /**
         * @brief 초기화
         **/
        function init() {

        }
		
		function procJsonAdminSaveConfig() {
		  $oModuleController = &getController('module');
		  
		  $config->menu_srl = Context::get('menu_srl');
		  $config->show_menu_srls = Context::get('show_menu_srls');
		  if(!is_array($config->show_menu_srls)) $config->show_menu_srls = explode('|@|', $config->show_menu_srls);
		  
		  $oModuleController->updateModuleConfig('json', $config);
		  $this->setMessage('success_updated'); 
		 }
	}
?>