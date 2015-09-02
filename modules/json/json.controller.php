<?php
	/**
	 * Json Procedure Control Class
	 * 
	 * @class  JsonController
	 * @author KnDol (kndol@kndol.net)
	 **/

	require_once(_XE_PATH_ . 'modules/json/json.lib.php');

    class jsonController extends json {
        /**
         * @brief 초기화
         **/
        function init() {
			Context::setRequestMethod('JSON');
			Context::setResponseMethod('JSON');
        }


		function procJsonMemberLogin() {
			$crypted = Context::get('crypted');

			$security_key = $_SESSION['SECURITY_KEY'];

			if (!$crypted || !$security_key) {
				$this->setError(-1);
				$this->setMessage('Illegal access', 'error');
				return;
			}

			$oJsonLib = new jsonLib();
			$data = $oJsonLib->decrypt($security_key, $crypted);

			$user_id = $data->user_id;
			$password = $data->password;
			$keep_signed = $data->keep_signed;
			$reg_id = $data->reg_id;

			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');

			if($oMemberModel->isLogged()) {
				$this->setMessage('already_logged', 'info');
			}
			else {
				$output = $oMemberController->doLogin($user_id, $password, $password==''?true:$keep_signed);
				if($output->toBool()) {
					$logged_info = $oMemberModel->getLoggedInfo();
					
					if(isset($reg_id)) {
						$member_srl = $logged_info->member_srl;
						$oNcenterliteModel = getModel('ncenterlite');
						$output = $oNcenterliteModel->getMemberConfig($member_srl);
	
						$args = new stdClass();
						
						$args->reg_id = $reg_id;
						$args->empty_value = "";
						$outputs = executeQuery('ncenterlite.resetUserConfig', $args);
						
						$args->member_srl = $member_srl;
						$args->comment_notify = $output?$output->data->comment_notify:'Y';
						$args->mention_notify = $output?$output->data->mention_notify:'Y';
						$args->message_notify = $output?$output->data->message_notify:'Y';
						if(!$output)
							$outputs = executeQuery('ncenterlite.insertUserConfig', $args);
						else
							$outputs = executeQuery('ncenterlite.updateUserConfig', $args);
					}
					
					$config = $oMemberModel->getMemberConfig();
					$args = new stdClass();
					$args->identifier = $config->identifier;
					$logged_info->config = $args;
					$this->setMessage('msg_logged', 'info');
					$this->add('data', $logged_info);
				} else {
					$this->setError(-1);
					$this->setMessage($output->message, 'error');
				}
			}
		}
		
		function procJsonMemberLogout() {
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
				$oNcenterliteModel = getModel('ncenterlite');
				$config = $oNcenterliteModel->getMemberConfig($member_srl)->data;
				
				$args = new stdClass();
				$args->reg_id = $config->reg_id;
				$args->empty_value = "";
				
				$outputs = executeQuery('ncenterlite.resetUserConfig', $args);
				
				 $result = $oMemberController->procMemberLogout();
				 if(!$result->toBool()) $this->setMessage('failed_to_logout');
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonMemberJoin($user_id) {
			
		}
		
		function procJsonUpdateReadedCount($document_srl) {
			$document_srl = Context::get('document_srl');
			
			$oDocumentModel = getModel('document');
			$oDocument = $oDocumentModel->getDocument($document_srl, false);
			$oDocumentController = getController('document');
			$oDocumentController->updateReadedCount($oDocument);
		}
		
		function procJsonWriteDocument($mid, $title, $content) {
			$mid = Context::get('mid');
			$title = Context::get('title');
			$content = Context::get('content');
			
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$module_srl = $oModuleModel->getModuleInfoByMid($mid)->module_srl;
	
				$member_srl = $logged_info->member_srl;
				$user_id = $logged_info->user_id;
				$user_name = $logged_info->user_name;
				$nick_name = $logged_info->nick_name;
				$email_address = $logged_info->email_address;
				$homepage = $logged_info->homepage;
				$birthday = $logged_info->birthday;
				$is_admin = $logged_info->is_admin;
				
				$title = addslashes($title);
				$title = urldecode($title);
				$content = addslashes($content);
				$content = urldecode($content);

				$obj->title = $title;
				$obj->content = $content;
				$obj->module_srl = $module_srl;
				$obj->member_srl = $member_srl;
				$obj->user_id = $user_id;
				$obj->nick_name =  $nick_name;
				$obj->homepage = $homepage;
				$obj->allow_comment = 'Y';
				$document_srl = getNextSequence();
				$obj->document_srl = $document_srl;
				$obj->list_order = $obj->update_order = -1*getNextSequence();
				
				$oDocumentController = &getController('document');
				$result = $oDocumentController->insertDocument($obj);
				if(!$result->toBool()) $this->setMessage('failed_to_write_document');
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonWriteComment($document_srl, $parent_srl, $content) {
			$document_srl = Context::get('document_srl');
			$parent_srl = Context::get('parent_srl');
			$content = Context::get('content');
			
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$module_srl = $oModuleModel->getModuleInfoByDocumentSrl($document_srl)->module_srl;
				$content = urldecode($content);
				
				$member_srl = $logged_info->member_srl;
				$user_id = $logged_info->user_id;
				$user_name = $logged_info->user_name;
				$nick_name = $logged_info->nick_name;
				$email_address = $logged_info->email_address;
				$homepage = $logged_info->homepage;
				$birthday = $logged_info->birthday;
				$is_admin = $logged_info->is_admin;
				
				$obj->content = $content;
				$obj->module_srl = $module_srl;
				$obj->document_srl = $document_srl;
				$obj->parent_srl = $parent_srl;
				$obj->member_srl = $member_srl;
				$obj->user_id = $user_id;
				$obj->user_name = $user_name;
				$obj->nick_name =  $nick_name;
				$obj->email_address = $email_address;
				$obj->homepage = $homepage;
				$comment_srl = getNextSequence();
				$obj->comment_srl = $comment_srl;
				$obj->list_order = -1*getNextSequence();
				
				if($parent_srl != 0) {
					$oCommentModel = &getModel('comment');
					$parent_comment = $oCommentModel->getComment($parent_srl);
				}
				
				$oDocumentModel = &getModel('document');
				$oDocument = $oDocumentModel->getDocument($document_srl, false);
				if($parent_srl!=0 && !$parent_comment->isExists())
					$this->setMessage('invalid_parent_srl');
				else if (!$oDocument->isExists())
					$this->setMessage('invalid_document_srl');
				else {
					$oCommentController = &getController('comment');
					$result = $oCommentController->insertComment($obj);
					if(!$result->toBool()) $this->setMessage('failed_to_write_comment');
				}
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonUpdateDocument($document_srl, $title, $content) {
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
				$title = Context::get('title');
				$title = addslashes($title);
				$title = urldecode($title);
				$content = Context::get('content');
				$content = addslashes($content);
				$content = urldecode($content);
				
				$oDocumentModel = getModel('document');	     
				$source_obj = $oDocumentModel->getDocument($document_srl, false);
				
				$obj->document_srl = $source_obj->document_srl;
				$obj->title = $title;
				$obj->content = $content;
				$obj->module_srl = $source_obj->module_srl;
				$obj->member_srl = $member_srl;
				$obj->user_id = $source_obj->user_id;
				$obj->nick_name =  $source_obj->nick_name;
				$obj->homepage = $source_obj->homepage;
				$obj->allow_comment  = 'Y';
				
				if($source_obj->isGranted()) {
					$oDocumentController = &getController('document');
					$result = $oDocumentController->updateDocument($source_obj, $obj);
					
					if(!$result->toBool()) $this->setMessage('failed_to_update_document');
				} else $this->message =  "have_no_permission";
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonUpdateComment($comment_srl, $comment) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$comment_srl = Context::get('comment_srl');
				$content = Context::get('content');
				$content = urldecode($content);
				
				$oCommentModel = getModel('comment');	     
				$source_obj = $oCommentModel->getComment($comment_srl, false);
				
				$obj->module_srl = $source_obj->module_srl;
				$obj->parent_srl = $source_obj->parent_srl;
				$obj->comment_srl = $comment_srl;
				$obj->content = $content;
				$obj->member_srl = $member_srl;
				$obj->user_id = $source_obj->user_id;
				$obj->nick_name =  $source_obj->nick_name;
				$obj->homepage = $source_obj->homepage;
				
				if($source_obj->isGranted()) {
					$oCommentController = &getController('comment');
					$result = $oCommentController->updateComment($obj);
					
					if(!$result->toBool()) $this->setMessage('failed_to_update_comment');
				} else $this->setMessage('have_no_permission');
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonDeleteDocument($document_srl) {
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
				$oDocumentModel = &getModel('document');
				$oDocument = $oDocumentModel->getDocument($document_srl);
				$oDocumentController = &getController('document');

				if($oDocument->isGranted()) {
					$obj->document_srl = $document_srl;
					$result = $oDocumentController->deleteDocument($document_srl);
					
					if(!$result->toBool()) $this->setMessage('failed_to_delete_document');
				} else $this->setMessage('have_no_permission');
			} else $this->setMessage('failed_to_login');
		}
		
		function procJsonDeleteComment($comment_srl) {
			$oModuleModel = &getModel('module');
			$oModuleModel->loadModuleExtends();
			$oMemberModel = &getModel('member');
			$oMemberController = &getController('member');
			
			if($oMemberModel->isLogged()) {
				$oMemberController->setSessionInfo();
				$logged_info = $oMemberModel->getLoggedInfo();
			}
			
			if($logged_info) {
				$comment_srl = Context::get('comment_srl');
				$oCommentModel = &getModel('comment');
				$oComment = $oCommentModel->getComment($comment_srl);

				if($oComment->isGranted()) {
					$obj->comment_srl = $comment_srl;
					$oCommentController = &getController('comment');
					$result = $oCommentController->deleteComment($comment_srl);
					
					if(!$result->toBool()) $this->setMessage('failed_to_delete_comment');
				} else $this->setMessage('have_no_permission');
			} else $this->setMessage('failed_to_login');
		}
				
		/**
		 * @brief A trigger to add Object ID for Member
		 */
		function triggerAfterInsertNotify(&$args)
		{
			//푸쉬알림 추가
			$oNcenterliteModel = getModel('ncenterlite');
			$push_sender_info = $oNcenterliteModel->getMemberConfig($args->member_srl)->data;
			if($push_sender_info->reg_id){
				$oJsonModel = &getModel('json');
				$oJsonModel->getPushMessage($args, $push_sender_info->reg_id);
			}
		}
	}
?>