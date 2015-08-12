<?php
	/**
	 * @class  socialxeModel
	 * @author CONORY (http://www.conory.com)
	 * @brief The model class fo the socialxe module
	 */
	class socialxeModel extends socialxe
	{

		/**
		 * @brief Initialization
		 */
		function init()
		{
		}
		
		/**
		 * @brief 회원 SNS
		 */
		function getMemberSns($service = null, $member_srl = null)
		{
			if(!$member_srl){
				$logged_info = Context::get('logged_info');	
				if(!$logged_info->member_srl) return;
				$member_srl = $logged_info->member_srl;
			}
			
			$args = new stdClass;
			$args->service = $service;
			$args->member_srl = $member_srl;
            $output = executeQuery('socialxe.getMemberSns',$args);

            return $output->data;
		}
		
		/**
		 * @brief SNS ID로 회원조회
		 */
		function getMemberSnsById($id, $service = null)
		{
			$args = new stdClass;
			$args->id = $id;
			$args->service = $service;
            $output = executeQuery('socialxe.getMemberSns',$args);

            return $output->data;
		}
		
		/**
		 * @brief SNS ID 첫 로그인 조회
		 */
		function getSnsUser($id, $service = null)
		{
			$args = new stdClass;
			$args->id = $id;
			$args->service = $service;
            $output = executeQuery('socialxe.getSnsUser',$args);

            return $output->data;
		}
		
		/**
		 * @brief SNS 유저여부
		 */
		function memberUserSns($member_srl = null)
		{
			$sns_list = $this->getMemberSns(null, $member_srl);
			if(!is_array($sns_list)) $sns_list = array($sns_list);
			
			if(count($sns_list) > 0) return true;

            return false;
		}
		
		/**
		 * @brief SNS 인증 URL
		 */
		function snsAuthUrl($service, $type)
		{
			$auth_url = getUrl('','mid',Context::get('mid'),'act','dispSocialxeConnectSns','service',$service,'type',$type,'redirect', $_SERVER['QUERY_STRING']);
			
            return $auth_url;
		}
		
 		/**
		 *@brief 로그기록
		 **/
        function logRecord($act, $info = null) {
			if(!is_object($info)){
				$info = Context::getRequestVars();
			}
			
			$args = new stdClass;
			switch($act) {
				case 'procSocialxeConfirmMail' : 
						$args->category = 'register';
						$args->content = sprintf('첫 로그인 이메일 주소 등록 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					break;
				case 'procSocialxeInputAddInfo' : 
						$args->category = 'register';
						$args->content = sprintf('추가정보 입력 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
					break;
				case 'procSocialxeSnsClear' : 
						$args->category = 'sns_clear';
						$args->content = sprintf('SNS 연결 해제 (SNS : %s)', $info->sns);
					break;
				case 'procSocialxeSnsLinkage' : 
						$args->category = 'linkage';
						$args->content = sprintf('SNS 연동설정 변경 (SNS : %s, 변경값 : %s)', $info->sns, $info->linkage);
					break;
				case 'dispSocialxeConnectSns' : 
						$args->category = 'auth_request';
						$args->content = sprintf('SNS 인증 요청 (SNS : %s)', $info->sns);
					break;
				case 'procSocialxeCallback' : 
						$args->category = $info->type;
						if($info->type == 'register'){
							if(!$info->msg) $info->msg = '등록 성공';
							$args->content = sprintf('SNS 등록 실행 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
							
						}elseif($info->type == 'login'){
							if(!$info->msg) $info->msg = '로그인 성공';
							$args->content = sprintf('SNS 로그인 실행 (SNS : %s, msg : %s)', $info->sns, Context::getLang($info->msg));
						}
					break;
				case 'linkage' : 
						$args->category = 'linkage';
						$args->content = sprintf('SNS 연동 (게시물 전송) (SNS : %s, Title : %s)', $info->sns, $info->title);
					break;
				case 'delete_member' : 
						$args->category = 'delete_member';
						if($info->nick_name){
							$args->content = sprintf('회원정보 삭제 (탈퇴) (회원번호 : %s, 닉네임 : %s, SNS ID : %s)', $info->member_srl, $info->nick_name, $info->sns_id);
						}else{
							$args->content = sprintf('[자동실행] 인증메일 유효시간이 지나 회원정보 삭제 (탈퇴) (회원번호 : %s, SNS ID : %s)', $info->member_srl, $info->nick_name, $info->sns_id);
						}
					break;
			}
			
			$logged_info = Context::get('logged_info');			
			$args->member_srl = $logged_info->member_srl;
		
			if(!$args->category){
				$args->category = 'unknown';
				$args->content = sprintf('%s (act : %s)', Context::getLang('unknown'), $act);
			}
            $args->act = $act;
			$args->micro_time = getMicroTime();
            executeQuery('socialxe.insertLogRecord',$args);
        }
	}