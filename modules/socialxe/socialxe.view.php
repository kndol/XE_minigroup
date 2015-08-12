<?php
	/**
	 * @class  socialxeView
	 * @author CONORY (http://www.conory.com)
	 * @brief The view class of the socialxe module
	 */
	class socialxeView extends socialxe
	{
		/**
		 * @brief Initialization
		 */
        function init() 
		{
			Context::set('config', $this->config);
			
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->config->skin);
            $this->setTemplatePath($template_path);
			
            Context::addJsFile($this->module_path.'tpl/js/socialxe.js');
			
			//사용자 레이아웃
			if($this->config->layout_srl){
				$oLayoutModel = getModel('layout');
				$layout_info = $oLayoutModel->getLayout($this->config->layout_srl);
				if($layout_info){
					$this->module_info->layout_srl = $this->config->layout_srl;
					$this->setLayoutPath($layout_info->path);
				}
			}
        }

		/**
		 * @brief SNS 관리
		 */
		function dispSocialxeSnsManage()
		{
            if(!Context::get('is_logged')) return new Object(-1, "msg_not_logged");
			
			$oSocialxeModel = getModel('socialxe');
			
			foreach($this->config->sns_services as $key=> $val){
				$sns_info = $oSocialxeModel->getMemberSns($val);
				
				$args = new stdClass;
				if(!$sns_info){
					$args->auth_url = $oSocialxeModel->snsAuthUrl($val, 'register');
					$sns_status = Context::getLang('status_sns_no_register');
				}else{
					$args->register = true;
					$sns_status = sprintf('<a href="%s" target="_blank">%s</a>',$sns_info->profile_url, $sns_info->name);
				}
				
				$args->sns_status = $sns_status;
				$args->service = $val;
				$args->linkage = $sns_info->linkage;
				$sns_services[$key] = $args;
			}
			
			Context::set('sns_services', $sns_services);
			
			$this->setTemplateFile('sns_manage');
		}
		
		/**
		 * @brief 이메일 확인
		 */
		function dispSocialxeConfirmMail()
		{
			if(!$_SESSION['tmp_socialxe_confirm_email']) return new Object(-1, "msg_invalid_request");
			Context::set('service', $_SESSION['tmp_socialxe_confirm_email']['service']);
			
			$_SESSION['socialxe_confirm_email'] = $_SESSION['tmp_socialxe_confirm_email'];
			unset($_SESSION['tmp_socialxe_confirm_email']);
			
			$this->setTemplateFile('confirm_email');
		}
		
		/**
		 * @brief 추가정보 입력
		 */
		function dispSocialxeInputAddInfo()
		{
			if(!$_SESSION['tmp_socialxe_input_add_info']) return new Object(-1, "msg_invalid_request");
			
			$_SESSION['socialxe_input_add_info'] = $_SESSION['tmp_socialxe_input_add_info'];
			unset($_SESSION['tmp_socialxe_input_add_info']);
			
			$oMemberModel = getModel('member');
			$member_config = $oMemberModel->getMemberConfig();
			Context::set('member_config', $member_config);
			
			$nick_name = $_SESSION['socialxe_input_add_info']['nick_name'];
			Context::set('nick_name', $nick_name);
			
			$user_name = $_SESSION['socialxe_input_add_info']['user_name'];
			Context::set('user_name', $user_name);

			$signupForm = array();
			
			//필수 추가 가입폼 출력
			if(in_array('require_add_info',$this->config->sns_input_add_info)){
				foreach($member_config->signupForm as $no=>$formInfo){
					if(!$formInfo->required || ($formInfo->isDefaultForm && $formInfo->name!='user_name')) continue;
					$signupForm[] = $formInfo;
				}
				$member_config->signupForm = $signupForm;
				
				$oMemberAdminView = getAdminView('member');
				$oMemberAdminView->memberConfig = $member_config;
				$formTags = $oMemberAdminView->_getMemberInputTag();
				Context::set('formTags', $formTags);
				
				$oMemberView = getView('member');
				$oMemberView->addExtraFormValidatorMessage();
			}
			
			//닉네임 폼
			if(in_array('nick_name',$this->config->sns_input_add_info)){
				$args = new stdClass;
				$args->required = true;
				$args->name = 'nick_name';
				$signupForm[] = $args;
			}
			
			//룰셋 생성
			$this->_createAddInfoRuleset($signupForm, in_array('agreement',$this->config->sns_input_add_info));
			
			$this->setTemplateFile('input_add_info');
		}
		
		/**
		 * @brief SNS 연결 진행
		 */
		function dispSocialxeConnectSns()
		{
			if(isCrawler()) return new Object(-1, "msg_invalid_request");
			
			$service = Context::get('service');	
			if(!$service || !in_array($service,$this->config->sns_services)) return new Object(-1, "msg_not_support_service_login");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new Object(-1, "msg_invalid_request");
			
			$type = Context::get('type');
			if(!$type) return new Object(-1, "msg_invalid_request");
			
			$is_logged = Context::get('is_logged');	
			if($type == 'register'){
				if(!$is_logged) return new Object(-1, "msg_not_logged");
			}elseif($type == 'login'){
				if($is_logged) return new Object(-1, "already_logged");
			}
			
			//인증메일 유효시간
			if($this->config->mail_auth_valid_hour){
				$args = new stdClass;
				$args->list_count = 5;
				$args->regdate_less = date("YmdHis",strtotime(sprintf('-%s hour',$this->config->mail_auth_valid_hour)));
				$output = executeQueryArray('socialxe.getAuthMailLess', $args);
				if($output->toBool()){
					$oMemberController = getController('member');
					foreach($output->data as $key=> $val){
						if(!$val->member_srl) continue;
						$oMemberController->deleteMember($val->member_srl);
					}
				}
			}
			
			unset($_SESSION['socialxe_input_add_info_data']);
			
			$oSocialxeModel = getModel('socialxe');
			
			//로그기록
			$info = new stdClass;
			$info->sns = $service;
			$info->type = $type;
			$oSocialxeModel->logRecord($this->act, $info);
			
			$redirect_url = $oLibrary->createAuthUrl($type);
			$this->setRedirectUrl($redirect_url);
		}
		
		/**
		 * @brief SNS 프로필
		 */
		function dispSocialxeSnsProfile()
		{
			if($this->config->sns_profile != 'Y') return new Object(-1, "msg_invalid_request");
			
			$member_srl = Context::get('member_srl');	
			if(!$member_srl) return new Object(-1, "msg_invalid_request");
			
			$oMemberModel = getModel('member');
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
			if(!$member_info->member_srl) return new Object(-1, "msg_invalid_request");
			
			Context::set('member_info', $member_info);
			
			$oSocialxeModel = getModel('socialxe');
			
			foreach($this->config->sns_services as $key=> $val){
				$sns_info = $oSocialxeModel->getMemberSns($val, $member_info->member_srl);
				if(!$sns_info->name) continue;
				
				$args = new stdClass;
				$args->profile_name = $sns_info->name;
				$args->profile_url = $sns_info->profile_url;
				$args->service = $val;
				$sns_services[$key] = $args;
			}
			
			Context::set('sns_services', $sns_services);
			
			$this->setTemplateFile('sns_profile');
		}
		
		/**
		 * @brief 필수 추가폼 룰셋파일 생성
		 */
		function _createAddInfoRuleset($signupForm, $agreement = false){
			$xml_file = './files/ruleset/insertAddInfoSocialxe.xml';
			$buff = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL.
				'<ruleset version="1.5.0">' . PHP_EOL.
				'<customrules>' . PHP_EOL.
				'</customrules>' . PHP_EOL.
				'<fields>' . PHP_EOL . '%s' . PHP_EOL . '</fields>' . PHP_EOL.
				'</ruleset>';

			$fields = array();
			
			if ($agreement)
			{
				$fields[] = '<field name="accept_agreement" required="true" />';
			}
			foreach($signupForm as $formInfo)
			{
				if($formInfo->required || $formInfo->mustRequired)
				{
					if($formInfo->type == 'tel' || $formInfo->type == 'kr_zip')
					{
						$fields[] = sprintf('<field name="%s[]" required="true" />', $formInfo->name);
					}
					else if($formInfo->name == 'nick_name')
					{
						$fields[] = sprintf('<field name="%s" required="true" length="2:20" />', $formInfo->name);
					}
					else
					{
						$fields[] = sprintf('<field name="%s" required="true" />', $formInfo->name);
					}
				}
			}

			$xml_buff = sprintf($buff, implode(PHP_EOL, $fields));
			FileHandler::writeFile($xml_file, $xml_buff);
			unset($xml_buff);

			$validator   = new Validator($xml_file);
			$validator->setCacheDir('files/cache');
			$validator->getJsPath();
		}
	}