<?php
	/**
	 * @class  socialxeAdminView
     * @author CONORY (http://www.conory.com)
	 * @brief The admin view class of the socialxe module
	 */
	 
	class socialxeAdminView extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
			Context::set('config', $this->config);
			
		    //로그기록 자동삭제
            if($this->module_config->delete_auto_log_record){
				$args = new stdClass();
                $args->regdate_less = date("YmdHis",strtotime(sprintf('-%d day',$this->module_config->delete_auto_log_record)));
                executeQuery('socialxe.deleteLogRecordLess', $args);
            }
			
			$this->setTemplatePath($this->module_path.'tpl');
			Context::addJsFile($this->module_path.'tpl/js/socialxe_admin.js');
		}
		
		/**
		 * @brief API 설정
		 */
		function dispSocialxeAdminSettingApi()
		{
			
			$this->setTemplateFile('api_setting');
		}
		
		/**
		 * @brief 환경설정
		 */
		function dispSocialxeAdminSetting()
		{
			$oModuleModel = getModel('module');
			
			$oLayoutModel = getModel('layout');
			$layout_list = $oLayoutModel->getLayoutList();
			Context::set('layout_list', $layout_list);
			
			$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');
			Context::set('mlayout_list', $mlayout_list);
			
			//스킨
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);
			
			//모바일
			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
			Context::set('mskin_list', $mskin_list);
			
			Context::set('default_services', $this->default_services);
			
			//추가정보 입력
			$input_add_info = array('agreement','nick_name','require_add_info');
			Context::set('input_add_info', $input_add_info);
			
			$this->setTemplateFile('setting');
		}
		
		/**
		 * @brief 로그기록
		 */
		function dispSocialxeAdminLogRecord()
		{
		    $oMemberModel = getModel('member');
			
			//로그 카테고리
			$category_list = array('auth_request','register','sns_clear','login','linkage','delete_member','unknown');
            Context::set('category_list', $category_list);
			
            //검색옵션
            $search_option = array('email','nick_name','content','ipaddress');
            Context::set('search_option', $search_option);
			
			$args = new stdClass;
			$search_keyword = Context::get('search_keyword');
			$search_target = Context::get('search_target');			
            if($search_target && $search_keyword) {
				if($search_target == 'email') {
					$args->member_srl = $oMemberModel->getMemberSrlByEmailAddress($search_keyword);
					if(!$args->member_srl) $args->member_srl = 0;
					
				}elseif($search_target == 'nick_name') {
					$args->member_srl = $oMemberModel->getMemberSrlByNickName($search_keyword);
					if(!$args->member_srl) $args->member_srl = 0;
					
				}elseif($search_target == 'content') {
					$args->content = $search_keyword;	
					
				}elseif($search_target == 'ipaddress') {
					$args->ipaddress = $search_keyword;	
				}	
            }
			
			$args->category = Context::get('search_category');
		    $args->page = Context::get('page');
            $output = executeQuery('socialxe.getLogRecordList',$args);
			
			if($output->data){
				foreach($output->data as $key=> $val){
					if($val->member_srl){
						$member_info = $oMemberModel->getMemberInfoByMemberSrl($val->member_srl);
						$val->nick_name = $member_info->nick_name;
					}
					$output->data[$key] = $val;	
				}
			}
			
            Context::set('total_count', $output->page_navigation->total_count);
            Context::set('total_page', $output->page_navigation->total_page);
            Context::set('page', $output->page);
            Context::set('log_record_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			
			$this->setTemplateFile('log_record');
		}
		
		/**
		 * @brief SNS 목록
		 */
		function dispSocialxeAdminSnsList()
		{
			$oSocialxeModel = getModel('socialxe');
			
			Context::set('sns_services', $this->config->sns_services);
			
            //검색옵션
            $search_option = array('nick_name','email');
            Context::set('search_option', $search_option);
			
			$args = new stdClass();
			$search_keyword = Context::get('search_keyword');
			$search_target = Context::get('search_target');	
            if($search_target && $search_keyword){
				$args->{$search_target} = $search_keyword;
            }
			
		    $args->page = Context::get('page');
            $output = executeQuery('socialxe.getMemberSnsList',$args);
			if($output->data){
				foreach($output->data as $key=> $val){
					foreach($this->config->sns_services as $key2=> $val2){
						$sns_info = $oSocialxeModel->getMemberSns($val2, $val->member_srl);
						if(!$sns_info){
							$val->{'service_'.$val2} = Context::getLang('status_sns_no_register');
						}else{
							$val->{'service_'.$val2} = sprintf('<a href="%s" target="_blank">%s</a>',$sns_info->profile_url, $sns_info->name);
						}
					}
					$output->data[$key] = $val;	
				}
			}
			
            Context::set('total_count', $output->page_navigation->total_count);
            Context::set('total_page', $output->page_navigation->total_page);
            Context::set('page', $output->page);
            Context::set('sns_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			
			$this->setTemplateFile('sns_list');
		}
	}