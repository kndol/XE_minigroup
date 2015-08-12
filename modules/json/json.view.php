<?php
    /**
     * Json Display Control Class
     * 
     * @class  JsonView
     * @author KnDol (kndol@kndol.net)
     **/

    class jsonView extends json {
        /**
         * @brief 초기화
         **/
        function init() {
			Context::setRequestMethod('JSON');
			Context::setResponseMethod('JSON');
        }
		
		function dispJsonLogin() {
			global $lang;
			$loginForm = array();
			if(!Context::get('is_logged')) {
				$oSocialxeClass = &getClass('socialxe');
				$config = $oSocialxeClass->getConfig();

				if(Context::get('mode') != 'default' || $config->default_login != 'Y'){
					// SNS 로그인
					$oSocialxeModel = &getModel('socialxe');
					$this->setMessage('sns_login', 'ui');
					$args = new stdClass;
					$args->default_login = $config->default_login;
					$loginForm['config'] = $args;
					$sns_services = new stdClass;
					foreach($config->sns_services as $key=> $val){
						$sns_services->$val = ucwords($val);
					}
					$loginForm['sns_services'] = $sns_services;
					$args = new stdClass;
					$args->sns_login = $lang->sns_login;
					$args->default_login = $lang->sns_default_login;
					$args->about_default_login = $lang->about_default_login;
					$loginForm['lang'] = $args;
				}
				else {
					// 기본 로그인
					$oModuleModel = &getModel('module');
					$config = $oModuleModel->getModuleConfig('member');
					$args = new stdClass;
					$args->id = ($config->identifier == 'user_id') ? $lang->user_id : $lang->email_address;
					$args->password = $lang->password;
					$args->keep_signed = $lang->keep_signed;
					$args->about_keep_warning = $lang->about_keep_warning;
					$args->login_btn = $lang->cmd_login;
					$args->find_account = $lang->cmd_find_member_account;
					$args->signup = $lang->cmd_signup;
					$loginForm['lang'] = $args;
					$this->setMessage('default_login', 'ui');
				}
			}
			else {
				$this->setMessage('already_logged', 'info');
			}
			$this->add('data', $loginForm);
		}

		function dispJsonConnectSns() {
			Context::setRequestMethod('HTML');
			Context::setResponseMethod('HTML');
			$_SESSION["call_from"] = "json";

			$oSocialxeClass = getClass("socialxe");
			$config = $oSocialxeClass->config;

			if(isCrawler()) return new Object(-1, "msg_invalid_request");

			$service = Context::get('service');	
			if(!$service || !in_array($service,$config->sns_services)) return new Object(-1, "msg_not_support_service_login");

			$oLibrary = $oSocialxeClass->getLibrary($service);
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
			if($config->mail_auth_valid_hour){
				$args = new stdClass;
				$args->list_count = 5;
				$args->regdate_less = date("YmdHis",strtotime(sprintf('-%s hour',$config->mail_auth_valid_hour)));
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

		function dispJsonLoginResult() {
			$XE_VALIDATOR_MESSAGE = Context::get('XE_VALIDATOR_MESSAGE');
			$XE_VALIDATOR_ERROR = Context::get('XE_VALIDATOR_ERROR');
			$oMemberModel = &getModel('member');
			$is_logged = $oMemberModel->isLogged();

			Context::get('is_logged');
			if($XE_VALIDATOR_ERROR < 0) {
				$this->setMessage($XE_VALIDATOR_MESSAGE, 'info');
			}
			else if (!$is_logged) {
				$this->setMessage('msg_not_logged', 'info');
			}
			else {
				$logged_info = $oMemberModel->getLoggedInfo();
				$this->setMessage('msg_logged', 'info');
				$this->add('data', $logged_info);
			}
		}

		function dispJsonSignupFormList() {
			global $lang;
			$oMemberAdminView = &getAdminView('member');
			$inputTags = $oMemberAdminView->_getMemberInputTag(null);
			$oMemberModel = &getModel('member');
			$oModuleModel = &getModel('module');
			$formTags = $oModuleModel->getModuleConfig('member')->signupForm;

			foreach($formTags as $key=>$tags) {
				if($tags->name=="image_name" || $tags->name=="image_mark" || $tags->isUse==false) unset($formTags[$key]);
				if($tags->name=="password") $tags->type="password";
				else $tags->type = "text";
				foreach($inputTags as $input_tags) {
					if($tags->name == $input_tags->name)
						$tags->type = $input_tags->type;
				}
				if($tags->isDefaultForm == false) {
					if($tags->type == "select")  $tags->type = "custom_select";
					$form_values = $oMemberModel->getJoinForm($tags->member_join_form_srl);
					$tags->values = $form_values->default_value;
				}
				if($tags->name=="find_account_question") 
					$tags->values = $lang->find_account_question_items;
			}
debugPrint($formTags);
			swapElement($formTags, 1, 2);
debugPrint($formTags);
			$this->add('data', $formTags);
		}
		
		function dispJsonMenuList() {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$logged_member_srl = $logged_info->member_srl;
				
				$oModuleModel = &getModel('module');
				$json_config = $oModuleModel->getModuleConfig('json');
			
				$oMenuAdminModel = &getAdminModel('menu');
				$menu_srl = $json_config->menu_srl;
				$show_menu_srls = $json_config->show_menu_srls;
				
				$oMenuItems = $oMenuAdminModel->getMenuItems($menu_srl);
				if($oMenuItems->data) {
					$this->add('menu_srl',$menu_srl);
debugPrint($oMenuItems->data);
					$oMenuItems->data = parent_sort($oMenuItems->data);
debugPrint($oMenuItems->data);
					foreach($oMenuItems->data as $key => $menu) {
						if(empty($show_menu_srls[0]) || in_array($menu->menu_item_srl, $show_menu_srls)) {
							$moduleInfo = $oModuleModel->getModuleInfoByMenuItemSrl($menu->menu_item_srl);
							$menu->module = $moduleInfo->module;
							
							$oModuleController = &getController('module');
							$oModuleController->replaceDefinedLangCode($menu->name);
							
							$start_module = $oModuleModel->getSiteInfo(0, $columnList);
							if($moduleInfo->mid==$start_module->mid)
								$menu->is_start_module="Y";
							else 
								$menu->is_start_module="N";
							
							if($menu->module=="page") {
								$menu->page_type = $moduleInfo->page_type;
								if($menu->page_type=="WIDGET") {
									if($moduleInfo->mcontent!=null)
										$source = $moduleInfo->mcontent;
									else $source = $moduleInfo->content;
									$pattern = '/^.*document_srl=\"([^"]+)\"/';
									preg_match($pattern, $source, $matches);
									$menu->document_srl = $matches[1];
								} else if($menu->page_type=="OUTSIDE"){
									$menu->path = $moduleInfo->path;
								}
							}
						} else
							unset($oMenuItems->data[$key]);						
					}
					$this->add('results', $oMenuItems->data);
				}
			} else $this->setMessage('failed_to_login');
		}
		
		function dispJsonDocumentList($page=1, $mid=null, $srl=null, $search_target=null, $search_keyword=null) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$page = Context::get('page');
				if(!$mid) $mid = Context::get('mid');
				if(!$srl) $srl = Context::get('srl');
				if(!$search_target) $search_target = Context::get('search_target');
				if(!$search_keyword) $search_keyword = Context::get('search_keyword');
				$results_per_page = "20";
				
				$args->order_type="asc";
				$args->page = $_POST['page'];
				if(isset($search_keyword)) {
					$search_keyword = urldecode($search_keyword);
					$args->search_target = $_POST['search_target'];
					$args->search_keyword = $search_keyword;
					$search_obj->s_title = $search_keyword;
				}
				
				if(isset($mid))
					$oModule = $oModuleModel->getModuleInfoByMid($mid);
				else
					$oModule = $oModuleModel->getModuleInfoByDocumentSrl($srl);
				$browser_title = $oModule->browser_title;
				$module_srl = $oModule->module_srl;
				$args->module_srl = $module_srl;
				$oDocumentModel = getModel('document');
				
				$total_count = $oDocumentModel->getDocumentCount($module_srl, $search_obj);
				$args->list_count = $results_per_page;

				$oModuleController = &getController('module');
				$oModuleController->replaceDefinedLangCode($browser_title);
				$this->add('browser_title', $browser_title);
				$total_count = $oDocumentModel->getDocumentCount($module_srl, $search_obj);
				$this->add('total_count', $total_count);
				$columnList = array('document_srl', 'regdate', 'last_update', 'nick_name', 'title', 'content', 'comment_count', 'readed_count', 'status');
				$oDocumentList = $oDocumentModel->getDocumentList($args, false, true, $columnList);
				if($oDocumentList->data) {
					foreach($oDocumentList->data as $key => $doc) {
						$variables[$key] = $doc->variables;
						$variables[$key]['extra_images'] = $doc->getExtraImages(60*60*24);
					}
					$this->add('results', $variables);
				
				} else $this->setMessage('no_results');
			} else $this->setMessage('failed_to_login');
		}
		
		function dispJsonDocument($document_srl, $is_notification=false, $notify=null) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info){
				$member_srl = $logged_info->member_srl;
				
				$document_srl = Context::get('document_srl');
				$is_notification = Context::get('is_notification');
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument($document_srl, false);
				
				if($oDocument->isExists()) {
					if($is_notification) {
						$notify = Context::get('notify');
						$oNcenterliteController = getController('ncenterlite');
						$oNcenterliteController->updateNotifyRead($notify, $member_srl);
					}
					
					foreach($oDocument->variables as $key => $doc) {
						$this->add($key, $doc);
					}
					$editable = $oDocument->isEditable() ? 1 : 0;
					$this->add('editable', $editable);
				} else $this->setMessage('invalid_document_srl');
			}
		}
		
		function dispJsonCommentList($document_srl, $page=1) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$document_srl = Context::get('document_srl');
				$page = Context::get('page');
				$list_count = "20";
				
				$oCommentModel = getModel('comment');
				$total_count = $oCommentModel->getCommentCount($document_srl);
				$oCommentList = $oCommentModel->getCommentList($document_srl, $page, false, $list_count);
				
				$this->add('document_srl', $document_srl);
				$this->add('total_count', $total_count);
				if($oCommentList->data) {
					foreach($oCommentList->data as $com) {
						$com->content = preg_replace("/&#?[a-z0-9]{2,8};/i", "", $com->content);
						$com->content = strip_tags($com->content);
						
						$comment_srl = $com->comment_srl;
						$oComment = $oCommentModel->getComment($comment_srl, false);
						$com->editable = $oComment->isEditable() ? 1 : 0;
					}
					$this->add('results', $oCommentList->data);
				}
			}
		}
		
		function dispJsonMessageList() {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$oJsonModel = getModel('json');
				$args = new stdClass();
				$args->member_srl = $logged_info->member_srl;
				// $args->opposite_member_srl = "4";
				// $args->message_type = array('R', 'S', 'T');
				$query_id = 'json.getMessageList';
				// $args->sort_index = 'message.list_order';
				$args->page = Context::get('page');
				$args->list_count = 20;
				$args->page_count = 10;
				
				$result = executeQuery($query_id, $args)->data;
				
				foreach($result as $key=>$val) {
					$oMemberInfo = $oMemberModel->getMemberInfoByMemberSrl($val->sender_srl!=$logged_info->member_srl?$val->sender_srl:$val->receiver_srl);
					$val->opposite_member_srl = $oMemberInfo->member_srl;
					$val->profile_image = $oMemberInfo->profile_image->src;
					$val->nick_name = $oMemberInfo->nick_name;
				}
				$this->add('results', $result);
				
			} else $this->setMessage('failed_to_login');
		}
		
		function dispJsonMessage() {
			
		}
    }
?>
