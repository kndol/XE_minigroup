<?php
	/**
	 * @class  socialxeController
     * @author CONORY (http://www.conory.com)
	 * @brief Controller class of socialxe modules
	 */
	class socialxeController extends socialxe
	{
		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief 이메일 확인
		 */
		function procSocialxeConfirmMail()
		{
			if(!$_SESSION['socialxe_confirm_email']) return new Object(-1, "msg_invalid_request");
			
			$email_address = Context::get('email_address');	
			if(!$email_address) return new Object(-1, "msg_invalid_request");
			
			$oMemberModel = getModel('member');
			$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email_address);
			if($member_srl){
				$error = 'msg_exists_email_address';
			}
			
			$saved = $_SESSION['socialxe_confirm_email'];
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			if(!$error){
				$oLibrary = $this->getLibrary($saved['service']);
				if(!$oLibrary) return new Object(-1, "msg_invalid_request");
				
				$oLibrary->setProfile($saved['profile_info']);
				$oLibrary->setAccessToken($saved['access_token']);
				$oLibrary->setRefreshToken($saved['refresh_token']);
				$oLibrary->set('profile_email', $email_address);
				
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()){
					$error = $output->getMessage();
					$errorCode = $output->getError();
				}
			}
			
			//에러
			if($error){
				$msg = $error;
				if($errorCode == -12){
					Context::set('xe_validator_id', '');
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
					
				}else{
					$_SESSION['tmp_socialxe_confirm_email'] = $_SESSION['socialxe_confirm_email'];
					$this->setError(-1);
					$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeConfirmMail');
				}
			}
			
			unset($_SESSION['socialxe_confirm_email']);
			
			$oSocialxeModel = getModel('socialxe');
			
			//로그기록
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
		}
		
		/**
		 * @brief 추가정보 입력
		 */
		function procSocialxeInputAddInfo()
		{
			if(!$_SESSION['socialxe_input_add_info']) return new Object(-1, "msg_invalid_request");
			
			$saved = $_SESSION['socialxe_input_add_info'];
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
			
			$oMemberModel = getModel('member');
			$signupForm = array();
			
			//필수 추가 가입폼
			if(in_array('require_add_info',$this->config->sns_input_add_info)){
				$member_config = $oMemberModel->getMemberConfig();
				foreach($member_config->signupForm as $no=>$formInfo){
					if(!$formInfo->required || ($formInfo->isDefaultForm && $formInfo->name!='user_name')) continue;
					$signupForm[] = $formInfo->name;
				}
			}
			
			//닉네임 폼
			if(in_array('nick_name',$this->config->sns_input_add_info)){
				$signupForm[] = 'nick_name';
				
				$member_srl = $oMemberModel->getMemberSrlByNickName(Context::get('nick_name'));
				if($member_srl){
					$error = 'msg_exists_nick_name';
				} 
			}
			
			//약관 동의
			if(in_array('agreement',$this->config->sns_input_add_info)){
				$signupForm[] = 'accept_agreement';
			}
			
			//추가정보 저장
			$add_data = array();
			foreach($signupForm as $val)
			{
				$add_data[$val] = Context::get($val);
			}
			
			if(!$error){
				$oLibrary = $this->getLibrary($saved['service']);
				if(!$oLibrary) return new Object(-1, "msg_invalid_request");
				
				$oLibrary->setProfile($saved['profile_info']);
				$oLibrary->setAccessToken($saved['access_token']);
				$oLibrary->setRefreshToken($saved['refresh_token']);
				
				$_SESSION['socialxe_input_add_info_data'] = $add_data;
				
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()){
					$error = $output->getMessage();
				}
			}
			
			//에러
			if($error){
				$msg = $error;
				$this->setError(-1);
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispSocialxeInputAddInfo');
				
				$_SESSION['tmp_socialxe_input_add_info'] = $_SESSION['socialxe_input_add_info'];
			}
			
			unset($_SESSION['socialxe_input_add_info']);
			
			$oSocialxeModel = getModel('socialxe');
			
			//로그기록
			$info = new stdClass;
			$info->msg = $msg;
			$info->sns = $saved['service'];
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
		}
		
 		/**
		 *@brief SNS 해제
		 **/
        function procSocialxeSnsClear()
		{
            if(!Context::get('is_logged')) return new Object(-1, "msg_not_logged");
			
			$service = Context::get('service');	
			if(!$service) return new Object(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new Object(-1, "msg_invalid_request");
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSns($service);
			if(!$sns_info) return new Object(-1, "msg_invalid_request");
			
			if($this->config->sns_login == 'Y'){
				$sns_list = $oSocialxeModel->getMemberSns();
				if(!is_array($sns_list)) $sns_list = array($sns_list);
				if(count($sns_list) < 2) return new Object(-1, "msg_not_clear_sns_one");
			}
			
			$oLibrary->setRefreshToken($sns_info->refresh_token);
			$oLibrary->setAccessToken($sns_info->access_token);
			
			$logged_info = Context::get('logged_info');	
			
			$args = new stdClass;
			$args->service = $service;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('socialxe.deleteMemberSns', $args);
			if(!$output->toBool()) return $output;			
			
			//토큰 파기
			$oLibrary->revokeToken();
			
			//로그기록
			$info = new stdClass;
			$info->sns = $service;
			$oSocialxeModel->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_sns_register_clear');
			
			$redirect_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispSocialxeSnsManage');
			$this->setRedirectUrl($redirect_url);
        }
		
 		/**
		 *@brief SNS 연동설정
		 **/
        function procSocialxeSnsLinkage()
		{
            if(!Context::get('is_logged')) return new Object(-1, "msg_not_logged");
			
			$service = Context::get('service');
			if(!$service) return new Object(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new Object(-1, "msg_invalid_request");
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSns($service);
			if(!$sns_info) return new Object(-1, "msg_not_linkage_sns_info");
			
			if(!method_exists($oLibrary, 'insertActivities'))
			{
				return new Object(-1, sprintf(Context::getLang('msg_not_support_linkage_setting'),ucwords($service)));
			}
			
			$args = new stdClass;
			if($sns_info->linkage == 'Y'){
				$args->linkage = 'N';
			}else{
				$args->linkage = 'Y';
			}
			
			$logged_info = Context::get('logged_info');
			
			$args->service = $service;
			$args->member_srl = $logged_info->member_srl;
			$output = executeQuery('socialxe.updateMemberSns', $args);
			if(!$output->toBool()) return $output;
			
			//로그기록
			$info = new stdClass;
			$info->sns = $service;
			$info->linkage = $args->linkage;
			$oSocialxeModel->logRecord($this->act, $info);
			
			$this->setMessage('msg_success_linkage_sns');
			
			$redirect_url = getNotEncodedUrl('','mid',Context::get('mid'),'act','dispSocialxeSnsManage');
			$this->setRedirectUrl($redirect_url);
        }
		
 		/**
		 *@brief Callback
		 **/
        function procSocialxeCallback()
		{
			$service = Context::get('service');
			if(!in_array($service,$this->config->sns_services)) return new Object(-1, "msg_invalid_request");
			
			$oLibrary = $this->getLibrary($service);
			if(!$oLibrary) return new Object(-1, "msg_invalid_request");
			
			$type = $_SESSION['socialxe_auth_type'];
			if(!$type) return new Object(-1, "msg_invalid_request");
			
			$mid = $_SESSION['socialxe_auth_redirect_mid'];
			$redirect_url = $_SESSION['socialxe_auth_redirect'];
			
			$called_from = $_SESSION["called_from"];

			if($redirect_url){
				$redirect_url = Context::getRequestUri().'?'.$redirect_url;
			}else{
				$redirect_url = Context::getRequestUri();
			}
			
			//인증진행
			$output = $oLibrary->authenticate();
			if(!$output->toBool()){
				$error = $output->getMessage();
			}
			
			//프로필 가져오기
			if(!$error){
				$output = $oLibrary->setProfile();
				if(!$output->toBool()){
					$error = $output->getMessage();
				}
			}
			
			//등록처리
			if(!$error){
				if($type == 'register'){
					$msg = 'msg_success_sns_register';
					
					$output = $this->registerSns($oLibrary);
					if(!$output->toBool()){
						$error = $output->getMessage();
					}
					
				}elseif($type == 'login'){
					$output = $this->LoginSns($oLibrary);
					if(!$output->toBool()){
						$error = $output->getMessage();
					}
					
					//로그인후 페이지 이동(회원설정 참조)
					$oModuleModel = getModel('module');
					$member_config = $oModuleModel->getModuleConfig('member');
					if(!$member_config->after_login_url){
						$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', '');
					}else{
						$redirect_url = $member_config->after_login_url;
					}
				}
			}
			
			//에러
			if($error){
				$msg = $error;
				$this->setError(-1);
				if($type == 'login'){
					if ($called_from == "json") {
						$_SESSION["error_msg"] = $msg;
					}
					else {
						$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispMemberLoginForm');
					}
				}
			}
			
			$oSocialxeModel = getModel('socialxe');
			
			//로그기록
			$info = new stdClass;
			$info->sns = $service;
			$info->msg = $msg;
			$info->type = $type;
			$oSocialxeModel->logRecord($this->act, $info);
			
			if($msg){
				$this->setMessage($msg);
			}
			if ($type == 'login' && $called_from == "json") {
				$redirect_url = getNotEncodedUrl('', 'mid', $mid, 'act', 'dispJsonLogged');
			}
			if(!$this->getRedirectUrl()){
				$this->setRedirectUrl($redirect_url);
			}
        }
		
 		/**
		 *@brief module Handler 트리거
		 **/
        function triggerModuleHandler(&$obj)
		{
			if($this->config->default_signup != 'Y' && $this->config->sns_login == 'Y' && (Context::get('act') != 'dispMemberLoginForm' || Context::get('mode') == 'default')){
				if(Context::get('module') == 'admin'){
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-body, #access .login-footer{display:none;}</style>');
				}else{
					Context::addHtmlHeader('<style>.signin .login-footer, #access .login-footer{display:none;}</style>');
				}
			}
			
			if(!Context::get('is_logged')) return new Object();
			
			$oMemberController = getController('member');
			$oMemberController->addMemberMenu('dispSocialxeSnsManage', 'sns_manage');			
			
			$execute_act = array('dispMemberModifyInfo','dispMemberModifyEmailAddress');
			if(!in_array(Context::get('act'), $execute_act)) return new Object();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if(Context::get('act') == 'dispMemberModifyInfo' || Context::get('act') == 'dispMemberModifyEmailAddress'){
					$_SESSION['rechecked_password_step'] = 'VALIDATE_PASSWORD';
				}
			}
			
            return new Object();
        }
		
 		/**
		 *@brief module Object Before 트리거
		 **/
        function triggerModuleObjectBefore(&$obj)
		{
			if($this->config->sns_login != 'Y') return new Object();
			
			$member_act = array('dispMemberSignUpForm','dispMemberFindAccount','procMemberInsert','procMemberFindAccount','procMemberFindAccountByQuestion');
			
			if($this->config->default_signup != 'Y' && in_array($obj->act, $member_act)){
				return new Object(-1, "msg_use_sns_login");
			}
			
			if($this->config->default_login != 'Y' && $obj->act == 'procMemberLogin'){
				return new Object(-1, "msg_use_sns_login");
			}
			
			if(!Context::get('is_logged')) return new Object();
			
			$execute_act = array('dispMemberModifyPassword','procMemberModifyPassword','procMemberLeave','dispMemberLeave');
			if(!in_array($obj->act, $execute_act)) return new Object();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if($obj->act == 'dispMemberModifyPassword' || $obj->act == 'procMemberModifyPassword' || ($this->config->delete_member_forbid == 'Y' && ($obj->act == 'procMemberLeave' || $obj->act == 'dispMemberLeave'))){
					if($obj->act == 'dispMemberModifyPassword'){
						$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
						$obj->setRedirectUrl($returnUrl);
					}else{
						return new Object(-1, "msg_invalid_request");
					}
					
				}elseif($obj->act == 'procMemberLeave'){
					$logged_info = Context::get('logged_info');
					$member_srl = $logged_info->member_srl;
					
					$oMemberController = getController('member');
					$output = $oMemberController->deleteMember($member_srl);
					if(!$output->toBool()) return $output;
					
					$oMemberController->destroySessionInfo();
					
					$obj->setMessage('success_delete_member_info');
					$returnUrl = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', '');
					$obj->setRedirectUrl($returnUrl);
				}
			}
			
            return new Object();
        }
		
 		/**
		 *@brief module Object After 트리거
		 **/
        function triggerModuleObjectAfter(&$obj)
		{
			if($this->config->sns_login != 'Y') return new Object();
			
			$oSocialxeModel = getModel('socialxe');
			
			if(Mobile::isFromMobilePhone()){
				$template_path = sprintf("%sm.skins/%s/",$this->module_path, $this->config->mskin);
			}else{
				$template_path = sprintf("%sskins/%s/",$this->module_path, $this->config->skin);
			}
			
			//로그인 페이지
			if($obj->act == 'dispMemberLoginForm' && (Context::get('mode') != 'default' || $this->config->default_login != 'Y')){
				Context::set('config', $this->config);
				
				$obj->setTemplatePath($template_path);
				$obj->setTemplateFile('sns_login');
				
				foreach($this->config->sns_services as $key=> $val){
					$args = new stdClass;
					$args->auth_url = $oSocialxeModel->snsAuthUrl($val, 'login');
					$args->service = $val;
					$sns_services[$key] = $args;
				}
				Context::set('sns_services', $sns_services);
			
			//인증메일 재발송
			}elseif($obj->act == 'procMemberResetAuthMail'){
				$redirect_url = getNotEncodedUrl('', 'act', 'dispMemberLoginForm');
				$obj->setRedirectUrl($redirect_url);
			}
			
			if(!Context::get('is_logged')) return new Object();
			
			$execute_act = array('dispMemberAdminInsert','dispMemberModifyInfo','dispMemberLeave');
			if(!in_array($obj->act, $execute_act)) return new Object();
			
			$sns_user = $oSocialxeModel->memberUserSns();
			if($sns_user){
				if($obj->act == 'dispMemberLeave'){
					$obj->setTemplatePath($template_path);
					$obj->setTemplateFile('delete_member');
					
				//비밀번호 질문 제거
				}elseif($obj->act == 'dispMemberModifyInfo'){
					$formTags = Context::get('formTags');
					$new_formTags = array();
					foreach($formTags as $no=>$formInfo){
						if($formInfo->name == 'find_account_question') continue;
						$new_formTags[] = $formInfo;
					}
					Context::set('formTags', $new_formTags);
				}
			}
			
			//관리자 회원정보 수정 SNS 항목삽입
			if($obj->act == 'dispMemberAdminInsert' && Context::get('member_srl')){
				$member_srl = Context::get('member_srl');
				$sns_user = $oSocialxeModel->memberUserSns($member_srl);
				
				if($sns_user){
					$snsTag = array();
					foreach($this->config->sns_services as $key=> $val){
						$sns_info = $oSocialxeModel->getMemberSns($val, $member_srl);
						if(!$sns_info) continue;
						
						$snsTag[]= sprintf('[%s] <a href="%s" target="_blank">%s</a>',ucwords($val),$sns_info->profile_url, $sns_info->name);
					}
					$snsTag = implode(', ',$snsTag);
					
					$formTags = Context::get('formTags');
					$new_formTags = array();
					foreach($formTags as $no=>$formInfo){
						if($formInfo->name == 'find_account_question'){
							$formInfo->name = 'sns_info';
							$formInfo->title = 'SNS';
							$formInfo->type = '';
							$formInfo->inputTag = $snsTag;
						}
						$new_formTags[] = $formInfo;
					}
					Context::set('formTags', $new_formTags);
				}
			}
			
            return new Object();
        }
		
        /**
         * @brief display 트리거
         **/
        function triggerDisplay(&$output)
		{
			if($this->config->sns_login != 'Y') return new Object();
			if(!Context::get('is_logged')) return new Object();
			
			$execute_act = array('dispMemberInfo','dispMemberModifyInfo','dispMemberAdminInsert');
			if(!in_array(Context::get('act'), $execute_act)) return new Object();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			
			if($sns_user){
				if(Context::get('act') == 'dispMemberInfo'){
					$output = preg_replace('/\<a[^\>]*?dispMemberModifyPassword[^\>]*?\>[^\<]*?\<\/a\>/is', '', $output);
					
					if($this->config->delete_member_forbid == 'Y'){
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', '', $output);
					}else{
						$output = preg_replace('/(\<a[^\>]*?dispMemberLeave[^\>]*?\>)[^\<]*?(\<\/a\>)/is', sprintf('$1%s$2', Context::getLang('delete_member_info')), $output);
					}
					
				//비밀번호 질문 제거
				}elseif(Context::get('act') == 'dispMemberModifyInfo'){
					$acode = cut_str(md5(date('YmdHis')), 13, '');
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberModifyInfo\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />',$acode), $output);
				}
			}
			
			//관리자 회원정보 수정
			if(Context::get('act') == 'dispMemberAdminInsert' && Context::get('member_srl')){
				$member_srl = Context::get('member_srl');
				$sns_user = $oSocialxeModel->memberUserSns($member_srl);
				
				if($sns_user){
					$acode = cut_str(md5(date('YmdHis')), 13, '');
					$output = preg_replace('/(\<input[^\>]*?value\=\"procMemberAdminInsert\"[^\>]*?\>)/is', sprintf('$1<input type="hidden" name="find_account_question" value="1" /><input type="hidden" name="find_account_answer" value="%s" />',$acode), $output);
				}
			}
			
			return new Object();
		}
		
 		/**
		 *@brief 문서등록 트리거
		 **/
        function triggerInsertDocumentAfter($obj) 
		{
			if(!Context::get('is_logged')) return new Object();
			
			//설정된 모듈 제외
			if($this->config->linkage_module_srl){
				$module_srl_list = explode(',',$this->config->linkage_module_srl);
				if($this->config->linkage_module_target=='exclude' && in_array($obj->module_srl, $module_srl_list)) return new Object();
				elseif($this->config->linkage_module_target!='exclude' && !in_array($obj->module_srl, $module_srl_list)) return new Object();
			}
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns();
			if(!$sns_user) return new Object();
			
			foreach($this->config->sns_services as $key=> $val){
				$sns_info = $oSocialxeModel->getMemberSns($val);
				if($sns_info->linkage != 'Y') continue;
				
				$oLibrary = $this->getLibrary($val);
				if(!$oLibrary) continue;
				
				$oLibrary->setRefreshToken($sns_info->refresh_token);
				$oLibrary->refreshToken();
				
				$args = new stdClass;
				$args->title = $obj->title;
				$args->content = $obj->content;
				$args->url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
				$oLibrary->insertActivities($args);
				
				//로그기록
				$info = new stdClass;
				$info->sns = $val;
				$info->title = $obj->title;
				$oSocialxeModel->logRecord('linkage', $info);
			}
			
			return new Object();
		}
		
 		/**
		 *@brief 회원등록 트리거
		 **/
        function triggerInsertMember(&$config) 
		{
			//이메일 주소 확인
			if(Context::get('act') == 'procSocialxeConfirmMail'){
				$config->enable_confirm = 'Y';
				
			//SNS 로그인시에 메일인증을 사용안함
			}elseif(Context::get('act') == 'procSocialxeCallback' || Context::get('act') == 'procSocialxeInputAddInfo'){
				$config->enable_confirm = 'N';
			}
			
			return new Object();
		}
		
 		/**
		 *@brief 회원메뉴 팝업 트리거
		 **/
		function triggerMemberMenu()
		{
			$member_srl = Context::get('target_srl');
			$mid = Context::get('cur_mid');

			if(!$member_srl || $this->config->sns_profile != 'Y') return new Object();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_user = $oSocialxeModel->memberUserSns($member_srl);
			
			if(!$sns_user) return new Object();
			
			$url = getUrl('', 'mid', $mid, 'act', 'dispSocialxeSnsProfile', 'member_srl', $member_srl);
			$oMemberController = getController('member');
			$oMemberController->addMemberPopupMenu($url, 'sns_profile', '');

			return new Object();
		}
		
 		/**
		 *@brief 회원삭제 트리거
		 **/
        function triggerDeleteMember($obj) 
		{
			$args = new stdClass;
			$args->member_srl = $obj->member_srl;
            $output = executeQueryArray('socialxe.getMemberSns',$args);
			
			$sns_id = array();
			foreach($output->data as $key=> $val){
				$sns_id[] = '['.$val->service.'] '.$val->id;
				$oLibrary = $this->getLibrary($val->service);
				if(!$oLibrary) continue;
				
				$oLibrary->setRefreshToken($val->refresh_token);
				$oLibrary->setAccessToken($val->access_token);
				
				//토큰 파기
				$oLibrary->revokeToken();
			}
			
			executeQuery('socialxe.deleteMemberSns', $args);
			
			$oSocialxeModel = getModel('socialxe');
			$logged_info = Context::get('logged_info');
			
			//로그기록
			$info = new stdClass;
			$info->sns_id = implode(' | ', $sns_id);
			$info->nick_name = $logged_info->nick_name;
			$info->member_srl = $obj->member_srl;
			$oSocialxeModel->logRecord('delete_member', $info);
			
			return new Object();
		}
		
 		/**
		 *@brief SNS 등록
		 **/
        function registerSns($oLibrary, $member_srl = null)
		{
			if(!$member_srl){
				$logged_info = Context::get('logged_info');	
				$member_srl = $logged_info->member_srl;
			}
			if($this->config->sns_login != 'Y' && !$member_srl) return new Object(-1, "msg_not_sns_login");
			
			if(!$oLibrary->getId()) return new Object(-1, "msg_errer_api_connect");
			
			//SNS 계정 인증상태 체크
			if(!$oLibrary->getVerified()) return new Object(-1, "msg_not_sns_verified");
			
			$id = $oLibrary->getId();
			$service = $oLibrary->getService();
			
			$oSocialxeModel = getModel('socialxe');			
			$sns_info = $oSocialxeModel->getMemberSnsById($id, $service);
			if($sns_info) return new Object(-1, "msg_already_registed_sns");
			
			//중복 이메일 계정이 있으면 그 계정으로 로그인
			$oMemberModel = getModel('member');
			$email = $oLibrary->getEmail();
			if(!$member_srl && $email && !$_SESSION['socialxe_confirm_email']){
				$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email);
				if($member_srl){
					//단, 관리자 계정은 보안문제로 제외.
					$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
					if($member_info->is_admin == 'Y'){
						unset($member_srl);
						return new Object(-1, "msg_request_admin_sns_login");
					}else{
						$do_login = true;
					}
				} 
			}
			
			//회원가입
			if(!$member_srl){
				$password = cut_str(md5(date('YmdHis')), 13, '');
				$nick_name = $oLibrary->getName();
				$profile_image = $oLibrary->getProfileImage();
				
				$replaceStr = array("\r\n", "\r", "\n", " ", "\t", "\xC2\xAD");
				$user_name = $nick_name = str_replace($replaceStr, '', $nick_name);

				$member_name = $oMemberModel->getMemberSrlByNickName($nick_name);
				if($member_name){
					$nick_name = $nick_name.date('is');
				}
				
				//추가정보받음
				if($this->config->sns_input_add_info[0] && !$_SESSION['socialxe_input_add_info_data']){
					$_SESSION['tmp_socialxe_input_add_info'] = $oLibrary->get();
					$_SESSION['tmp_socialxe_input_add_info']['nick_name'] = $nick_name;
					$_SESSION['tmp_socialxe_input_add_info']['user_name'] = $user_name;
					$redirect_url = getNotEncodedUrl('', 'act', 'dispSocialxeInputAddInfo');
					return $this->setRedirectUrl($redirect_url, new Object(-1,'sns_input_add_info'));
				}

				//이메일 확인받음
				if(!$email){
					$_SESSION['tmp_socialxe_confirm_email'] = $oLibrary->get();
					$redirect_url = getNotEncodedUrl('', 'act', 'dispSocialxeConfirmMail');
					return $this->setRedirectUrl($redirect_url, new Object(-1,'need_confirm_email_address'));
					
				}else{
					Context::setRequestMethod('POST');
					Context::set('password', $password, true);
					Context::set('nick_name', $nick_name, true);
					Context::set('email_address', $email, true);
					Context::set('accept_agreement', 'Y', true);
					
					$extend = $oLibrary->getProfileExtend();
					$signature = $extend->signature;
					Context::set('homepage', $extend->homepage, true);
					Context::set('blog', $extend->blog, true);
					Context::set('birthday', $extend->birthday, true);
					Context::set('gender', $extend->gender, true);
					
					//추가정보
					$add_data = $_SESSION['socialxe_input_add_info_data'];
					foreach($add_data as $key=> $val){
						Context::set($key, $val, true);
					}
					
					unset($_SESSION['socialxe_input_add_info_data']);
				}
				
				$oMemberController = getController('member');
				$output = $oMemberController->procMemberInsert();
				if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()){
					if($output->error != -1){
						$s_output = $output;
					}else{
						return $output;
					}
				}
				
				$member_srl = $oMemberModel->getMemberSrlByEmailAddress($email);
				if(!$member_srl) return new Object(-1, "msg_error_register_sns");
				
				//이전 로그인 기록이 있으면 가입 포인트 제거
				$sns_user = $oSocialxeModel->getSnsUser($id, $service);
				if($sns_user){
					$PHC_member_srl = $member_srl;
					$PHC_content = Context::getLang('PHC_member_register_sns_login');
					eval('$__PHC'.$PHC_member_srl.'__[] = array($PHC_content,$PHC_point,$PHC_type);');
					eval('Context::set(\'__PHC\'.$PHC_member_srl.\'__\',$__PHC'.$PHC_member_srl.'__);');
					
					$oPointController = getController('point');
					$oPointController->setPoint($member_srl, 0, 'update');
				}
				
				if($signature){
					$oMemberController->putSignature($member_srl, $signature);
				}
				
				if($profile_image){
					$tmp_dir = './files/cache/tmp/';
					if(!is_dir($tmp_dir)) FileHandler::makeDir($tmp_dir);
					
					$url = parse_url($profile_image);
					$path_parts = pathinfo($url['path']);
					$extension = $path_parts['extension'];
					$tmp_file = sprintf('%s%s.%s', $tmp_dir, $password,$extension);
					
					$request_config = array();
					$request_config['ssl_verify_peer'] = false;
					
					if(FileHandler::getRemoteFile($profile_image, $tmp_file, null, 3, 'GET', null, array(), array(), array(), $request_config)){
						$oMemberController->insertProfileImage($member_srl, $tmp_file);
						@unlink($tmp_file);
					}
				}
				
			//sns 등록
			}else{
				$sns_info = $oSocialxeModel->getMemberSns($service, $member_srl);
				if($sns_info) return new Object(-1, "msg_invalid_request");
			}
			
			$args = new stdClass;
			$args->refresh_token = $oLibrary->getRefreshToken();
			$args->access_token = $oLibrary->getAccessToken();
			$args->profile_info = $oLibrary->getProfileInfo();
			$args->profile_url = $oLibrary->getProfileUrl();
			$args->profile_image = $oLibrary->getProfileImage();
			$args->email = $oLibrary->getEmail();
			$args->name = $oLibrary->getName();
			$args->id = $oLibrary->getId();
			$args->service = $service;
			$args->member_srl = $member_srl;
			$output = executeQuery('socialxe.insertMemberSns', $args);
			if(!$output->toBool()) return $output;
			
			//SNS ID 따로 기록. (SNS 정보가 삭제 되더라도 영구보관)
			$sns_user = $oSocialxeModel->getSnsUser($id, $service);
			if(!$sns_user){
				$output = executeQuery('socialxe.insertSnsUser', $args);
				if(!$output->toBool()) return $output;
			}
			
			if($do_login){
				$output = $this->LoginSns($oLibrary);
				if(!$output->toBool()) return $output;
			}
			
			if($s_output) return $s_output;
			
			return new Object();
        }
		
 		/**
		 *@brief SNS 로그인
		 **/
        function LoginSns($oLibrary)
		{
			if($this->config->sns_login != 'Y') return new Object(-1, "msg_not_sns_login");
            if(Context::get('is_logged')) return new Object(-1, "already_logged");
			
			if(!$oLibrary->getId()) return new Object(-1, "msg_errer_api_connect");
			
			//SNS 계정 인증상태 체크
			if(!$oLibrary->getVerified()) return new Object(-1, "msg_not_sns_verified");
			
			$id = $oLibrary->getId();
			$service = $oLibrary->getService();
			
			$oSocialxeModel = getModel('socialxe');
			$sns_info = $oSocialxeModel->getMemberSnsById($id, $service);
			
			if($sns_info){
				$member_srl = $sns_info->member_srl;
				$oMemberModel = getModel('member');
				$member_info = $oMemberModel->getMemberInfoByMemberSrl($member_srl);
				
				//인증메일
				if($member_info->denied == 'Y'){
					$args = new stdClass;
					$args->member_srl = $member_srl;
					$output = executeQuery('member.chkAuthMail', $args);
					if ($output->toBool() && $output->data->count != '0'){
						$_SESSION['auth_member_srl'] = $member_info->member_srl;
						$redirectUrl = getNotEncodedUrl('', 'act', 'dispMemberResendAuthMail');
						return $this->setRedirectUrl($redirectUrl, new Object(-1,'msg_user_not_confirmed'));
					}
				}
				
				$config = $oMemberModel->getMemberConfig();
				if($config->identifier == 'email_address'){
					$user_id = $member_info->email_address;
				}else{
					$user_id = $member_info->user_id;
				}
				
				$oMemberController = getController('member');
				$output = $oMemberController->doLogin($user_id, '', $this->config->sns_keep_signed=='Y'?true:false);
				if(!$output->toBool()) return $output;
				
				$args = new stdClass;
				$args->refresh_token = $oLibrary->getRefreshToken();
				$args->access_token = $oLibrary->getAccessToken();
				$args->profile_info = $oLibrary->getProfileInfo();
				$args->profile_url = $oLibrary->getProfileUrl();
				$args->profile_image = $oLibrary->getProfileImage();
				$args->email = $oLibrary->getEmail();
				$args->name = $oLibrary->getName();
				$args->service = $service;
				$args->member_srl = $member_srl;	
				$output = executeQuery('socialxe.updateMemberSns', $args);
				if(!$output->toBool()) return $output;
				
				if ($_SESSION["called_from"] == 'json') {
					$_SESSION["user_id"] = $user_id;
				}

				//SNS 등록
			}else{
				$output = $this->registerSns($oLibrary);
				if(!$output->toBool()) return $output;
			}
			
			return new Object();
        }
	}