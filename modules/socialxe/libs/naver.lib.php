<?php
	/**
	 * @class  libraryNaver
     * @author CONORY (http://www.conory.com)
	 * @brief The naver library of the socialxe module
	 */
	
	const NAVER_OAUTH2_URI = 'https://nid.naver.com/oauth2.0/';
	
	class libraryNaver extends socialxeLibrary
	{
		/**
		 * @brief 인증 URL 생성
		 */
		function createAuthUrl($type)
		{
			$_SESSION['socialxe_auth_redirect_mid'] = Context::get('mid');
			$_SESSION['socialxe_auth_redirect'] = Context::get('redirect');
			$_SESSION['socialxe_auth_type'] = $type;
			$_SESSION['socialxe_auth_state'] = md5(microtime().mt_rand());
			
			$params = array(
				'response_type' => 'code',
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','naver'),
				'client_id' => $this->config->naver_client_id,
				'state' => $_SESSION['socialxe_auth_state']
			);
			
			return NAVER_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
		}
		
		/**
		 * @brief 코드인증
		 */
		function authenticate()
		{
			$state = Context::get('state');
			$code = Context::get('code');
			if(!$code || !$_SESSION['socialxe_auth_state'] || $state != $_SESSION['socialxe_auth_state']) return new Object(-1, "msg_invalid_request");
			
			$post_data = array(
				'code' => $code,
				'grant_type' => 'authorization_code',
				'state' => $state,
				'client_id' => $this->config->naver_client_id,
				'client_secret' => $this->config->naver_client_secret
			);
			$token = $this->requestHttp('token', $post_data);
			
			$this->setAccessToken($token['access_token']);
			$this->setRefreshToken($token['refresh_token']);
			
			unset($_SESSION['socialxe_auth_state']);
			
			return new Object();
		}
		
		/**
		 * @brief 토큰 새로고침
		 */
		function refreshToken()
		{
			if(!$this->refresh_token) return;
			
			$post_data = array(
				'refresh_token' => $this->refresh_token,
				'grant_type' => 'refresh_token',
				'client_id' => $this->config->naver_client_id,
				'client_secret' => $this->config->naver_client_secret
			);
			$token = $this->requestHttp('token', $post_data);
			
			$this->setAccessToken($token['access_token']);
		}
		
		/**
		 * @brief 토큰파기
		 */
		function revokeToken()
		{
			if(!$this->access_token) return;
			
			$post_data = array(
				'sercive_provider' => 'NAVER',
				'access_token' => $this->access_token,
				'grant_type' => 'delete',
				'client_id' => $this->config->naver_client_id,
				'client_secret' => $this->config->naver_client_secret
			);
			$this->requestHttp('token', $post_data);
		}
		
		/**
		 * @brief 계정 정보 가져오기
		 */
		function takeAccountInfo()
		{
			if(!$this->access_token) return;
			
			$request_url = 'https://apis.naver.com/nidlogin/nid/getUserProfile.xml';
			$profile = $this->requestHttp($request_url, array(), $this->access_token);
			
			return $profile;
		}
		
		/**
		 * @brief 프로필 삽입
		 */
		function setProfile($profile_info = null)
		{
			if($profile_info){
				$this->profile_info = $profile_info;
				$profile = json_decode($profile_info, true);
			}else{
				$profile = $this->takeAccountInfo();
				$this->profile_info = json_encode($profile);
			}
			
			if(!$profile) return new Object(-1, "msg_errer_api_connect");
			
			$this->profile_id = $profile['enc_id'];
			$this->profile_email = $profile['email'];
// KnDol 2015.7.29 네이버 API에서 name이 추가돼서 nickname 대신 name을 사용하도록 변경
//			$this->profile_name = $profile['nickname'];
			$this->profile_name = $profile['name'];
			$this->profile_image = $profile['profile_image'];
			
			$email = explode('@', $profile['email']);
			$this->profile_url = 'http://blog.naver.com/'.$email[0];
			
			//프로필 인증
			$this->profile_verified = true;
			
			return new Object();
		}

		/**
		 * @brief 프로필 확장
		 */
		function getProfileExtend()
		{
			if(!$this->profile_info) return;
			
			$profile = json_decode($this->profile_info, true);
			
			$extend = new stdClass;
			$extend->birthday = date('Y').preg_replace('/[^0-9]*?/', '', $profile['birthday']);
			
			$email = explode('@', $profile['email']);
			$extend->blog = 'http://blog.naver.com/'.$email[0];
			
			$gender = $profile['gender'];
			if($gender == 'M'){
				$extend->gender = '남성';
				
			}elseif($gender == 'F'){
				$extend->gender = '여성';
			}
			
			return $extend;
		}
		
		function getProfileImage()
		{
			$profile_image = $this->profile_image;
			return preg_replace('/\?sz\=[0-9]+/is', '', $profile_image);
		}	
		
		function requestHttp($request_url, $post_data = array(), $authorization = null)
		{
			if($post_data) $method = 'POST';
			else $method = 'GET';
			
			if($authorization){
				$headers = array(
					'Host' => 'apis.naver.com',
					'Pragma' => 'no-cache',
					'Accept' => '*/*',
					'Authorization' => 'Bearer '.$authorization
				);
			}
			
			$request_config = array(
				'ssl_verify_peer' => false
			);
			
			if($request_url == 'token'){
				$request_url = NAVER_OAUTH2_URI . 'token';
			}
			
			$buff = FileHandler::getRemoteResource($request_url, null, 10, $method, 'application/x-www-form-urlencoded', $headers, array(), $post_data, $request_config);
			
			if(strpos($buff, '<?xml') !== false){
				$oXmlParser = new XmlParser();
				$xml_obj = $oXmlParser->parse($buff);
				$response = $xml_obj->data->response;
				
				$buff = array();
				$xml_list = get_object_vars($response);
				
				foreach($xml_list as $key=> $val){
					$buff[$key] = $val->body;
				}
				
			}else{
				$buff = json_decode($buff, true);
			}
			
			return $buff;
		}
	}