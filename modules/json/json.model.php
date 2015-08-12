<?php
    /**
     * Json Object Control Class
     * 
     * @class  JsonModel
     * @author KnDol (kndol@kndol.net)
     **/

    class jsonModel extends json {

        /**
         * @brief 초기화
         **/
        function init() {

        }
		
		function getPushMessage($v, $reg_id) {
			if(empty($reg_id))
				return;
			
			switch($v->type)
			{
				case 'D':
					$type = "글";
				break;
				case 'C':
					$type = "댓글";
				break;
				case 'E':
					$type = "쪽지";
				break;
			}
			 
			switch($v->target_type)
			{
				case 'C':
					$target_type = "댓글";
				break;
				case 'M':
					$target_type = "언급";
				break;
				case 'E':
					$target_type = "쪽지";
				break;
			}
		 
			//푸쉬전송
			$url = 'https://android.googleapis.com/gcm/send';
			$apiKey = "AIzaSyBK38PNU7ziJX8WKeJJzrhEsjj2us2Q55M";
		 
			$target_device = array($reg_id);  // using object Id of target Installation.
			
			// Set POST variables
			$url = 'https://android.googleapis.com/gcm/send';

			$oModuleController = &getController('module');
			$oModuleController->replaceDefinedLangCode($v->target_browser);
			$fields = array(
				'registration_ids' => $target_device,
				'data' => array('notify' => $v->notify, 'type' => $type, 'target_type' => $target_type, 'target_nick_name' => $v->target_nick_name, 
										'target_summary' => $v->target_summary, 'target_browser' => $v->target_browser, 'srl' => $v->srl)
			);
			$headers = array(
				'Content-Type: application/json',
				'Authorization: key= '.$apiKey.''
			);

			// Open connection
			$ch = curl_init();
			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
			
			curl_exec($ch);
		}
		
		function getJsonCheckIdentifier($user_id=null, $email_address=null, $nick_name=null) {
			Context::setRequestMethod('JSON');
			Context::setResponseMethod('JSON');
			
			$user_id = Context::get('user_id');
			$email_address = Context::get('email_address');
			$nick_name = Context::get('nick_name');
			
			if(!(isset($user_id) xor isset($email_address) xor isset($nick_name)))
				$this->setMessage('invalid_request');
			
			$oMemberModel = &getModel('member');
			if(isset($user_id)) {
				$output = $oMemberModel->getMemberInfoByUserID($user_id);
			} else if(isset($email_address)) {
				$output = $oMemberModel->getMemberInfoByEmailAddress($email_address);
			} else if(isset($nick_name)) {
				$output = $oMemberModel->getMemberSrlByNickName($nick_name);
			}
			
			if(isset($user_id) && $oMemberModel->isDeniedID($user_id))
				$this->setMessage('forbidden_user_id');
			else if(isset($nick_name) && $oMemberModel->isDeniedNickName($nick_name))
				$this->setMessage('forbidden_nick_name');
			
			if(!empty($output))
				$this->setMessage('identifier_already_exists');
		}
    }
?>