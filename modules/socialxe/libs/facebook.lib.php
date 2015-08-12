<?php
	/**
	 * @class  libraryFacebook
     * @author CONORY (http://www.conory.com)
	 * @brief The facebook library of the socialxe module
	 */
	
	const FACEBOOK_GRAPH_API_VERSION = 'v2.2';
	const FACEBOOK_URI = 'https://www.facebook.com/';
	const FACEBOOK_GRAPH_URL = 'https://graph.facebook.com/';
	
	class libraryFacebook extends socialxeLibrary
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
			
			$scope = array('public_profile','email');
			$params = array(
				'client_id' => $this->config->facebook_app_id,
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','facebook'),
				'state' => $_SESSION['socialxe_auth_state'],
				'scope' => implode(',', $scope)
			);
			
			return FACEBOOK_URI . 'dialog/oauth?' . http_build_query($params, '', '&');
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
				'client_id' => $this->config->facebook_app_id,
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','facebook'),
				'client_secret' => $this->config->facebook_app_secret,
				'code' => $code
			);
			
			$token = $this->requestHttp('/oauth/access_token', $post_data);
			
			$this->setAccessToken($token['access_token']);
			$this->setRefreshToken($token['access_token']);
			
			unset($_SESSION['socialxe_auth_state']);
			
			return new Object();
		}
		
		/**
		 * @brief 토큰 새로고침
		 */
		function refreshToken()
		{
		}
		
		/**
		 * @brief 토큰파기
		 */
		function revokeToken()
		{
			if(!$this->access_token) return;
			
			$request_url = '/me/permissions?access_token='.$this->access_token;
			$this->requestHttp($request_url, array(), true);
		}
		
		/**
		 * @brief 계정 정보 가져오기
		 */
		function takeAccountInfo()
		{
			if(!$this->access_token) return;
			
			$request_url = '/me?access_token='.$this->access_token;
			$profile = $this->requestHttp($request_url);
			
			$request_url = '/me/picture?redirect=0&type=normal&access_token='.$this->access_token;
			$profile['image'] = $this->requestHttp($request_url);
			
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
			
			$this->profile_id = $profile['id'];
			$this->profile_name = $profile['name'];
			$this->profile_image = $profile['image']['data']['url'];
			$this->profile_url = $profile['link'];
			$this->profile_email = $profile['email'];
			$this->profile_verified = $profile['verified'];
			
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
			if($profile['about']){
				$extend->signature = $profile['about'];
			}else{
				$extend->signature = $profile['bio'];
			}
			$extend->homepage = $profile['website'];
			
			$birthday = explode('/', $profile['birthday']);
			$extend->birthday = $birthday[2].$birthday[0].$birthday[1];
			
			$gender = $profile['gender'];
			if($gender == 'male'){
				$extend->gender = '남성';
				
			}elseif($gender == 'female'){
				$extend->gender = '여성';
			}
			
			return $extend;
		}
		
		function requestHttp($request_url, $post_data = array(), $delete = false, $parser = true)
		{
			if($post_data) $method = 'POST';
			else $method = 'GET';
			
			if($delete) $method = 'DELETE';
			
			$request_config = array(
				'ssl_verify_peer' => false
			);
			
			$request_url = FACEBOOK_GRAPH_URL . FACEBOOK_GRAPH_API_VERSION . $request_url;
			
			$buff = FileHandler::getRemoteResource($request_url, null, 10, $method, 'application/x-www-form-urlencoded', array(), array(), $post_data, $request_config);
			
			if($parser){
				$decodedResult = json_decode($buff, true);
				
				if(!$decodedResult){
					$result = array();
					parse_str($buff, $result);
				}else{
					$result = $decodedResult;
				}
			}else{
				$result = $buff;
			}
			
			return $result;
		}
	}