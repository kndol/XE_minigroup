<?php
	/**
	 * @class  socialxeAdminController
     * @author CONORY (http://www.conory.com)
	 * @brief The admin controller class of the socialxe module
	 */
	
	class socialxeAdminController extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
        /**
         * @brief API 설정
         **/
        function procSocialxeAdminSettingApi() 
		{
            $args = Context::getRequestVars();
			
			$config = $this->config;
			$args_list = get_object_vars($args);
			foreach($args_list as $key=> $val){
				$config->{$key} = $val;
			}
			
			$config->sns_services = implode('|@|', $config->sns_services);
			$config->sns_input_add_info = implode('|@|', $config->sns_input_add_info);
			
            $oModuleController = getController('module');
            $oModuleController->insertModuleConfig('socialxe', $config);	
			
            $this->setMessage('success_updated');
			$returnUrl = Context::get('success_return_url')?Context::get('success_return_url'):getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminSettingApi');
			$this->setRedirectUrl($returnUrl);
        }
		
        /**
         * @brief 환경설정
         **/
        function procSocialxeAdminSetting() 
		{
            $args = Context::getRequestVars();
			
			$config = $this->config;
			$args_list = get_object_vars($args);
			foreach($args_list as $key=> $val){
				$config->{$key} = $val;
			}
			
			if(!$args->sns_services) $config->sns_services = implode('|@|', $this->default_services);
			else $config->sns_services = implode('|@|', $config->sns_services);
			
			if(!$args->sns_input_add_info) $config->sns_input_add_info = '';
			else $config->sns_input_add_info = implode('|@|', $config->sns_input_add_info);
			
			if(!$args->sns_login) $config->sns_login = '';
			if(!$args->default_login) $config->default_login = '';
			if(!$args->default_signup) $config->default_signup = '';
			if(!$args->sns_profile) $config->sns_profile = '';
			if(!$args->delete_member_forbid) $config->delete_member_forbid = '';
			if(!$args->sns_suspended_account) $config->sns_suspended_account = '';
			if(!$args->linkage_module_srl) $config->linkage_module_srl = '';
			if(!$args->sns_keep_signed) $config->sns_keep_signed = '';
			
            $oModuleController = getController('module');
            $oModuleController->insertModuleConfig('socialxe', $config);	
			
            $this->setMessage('success_updated');
			$returnUrl = Context::get('success_return_url')?Context::get('success_return_url'):getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminSetting');
			$this->setRedirectUrl($returnUrl);
        }
		
        /**
         * @brief 로그기록 삭제
         **/
        function procSocialxeAdminDeleteLogRecord() 
		{
			$args = new stdClass;
			$date_srl = Context::get('date_srl');
		    if($date_srl){
				$args->regdate = $date_srl;
			}
            $output = executeQuery('socialxe.deleteLogRecord', $args);	
            if(!$output->toBool()) return $output;
				
            $this->setMessage('success_deleted');
			$returnUrl = Context::get('success_return_url')?Context::get('success_return_url'):getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSocialxeAdminLogRecord');
			$this->setRedirectUrl($returnUrl);
        }
	}