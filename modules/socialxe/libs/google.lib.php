<?php
	/**
	 * @class  libraryGoogle
     * @author CONORY (http://www.conory.com)
	 * @brief The google library of the socialxe module
	 */
	
	const GOOGLE_OAUTH2_REVOKE_URI = 'https://accounts.google.com/o/oauth2/revoke';
	const GOOGLE_OAUTH2_TOKEN_URI = 'https://accounts.google.com/o/oauth2/token';
	const GOOGLE_OAUTH2_AUTH_URL = 'https://accounts.google.com/o/oauth2/auth';
	const GOOGLE_PEOPLE_URI = 'https://www.googleapis.com/plus/v1/people/';
	
	class libraryGoogle extends socialxeLibrary
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
			
			$scope = array('profile','email','https://www.googleapis.com/auth/plus.login');
			$params = array(
				'response_type' => 'code',
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','google'),
				'client_id' => $this->config->google_client_id,
				'state' => $_SESSION['socialxe_auth_state'],
				'scope' => implode(' ', $scope),
				'access_type' => 'offline'
			);
			
			return GOOGLE_OAUTH2_AUTH_URL . '?' . http_build_query($params, '', '&');
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
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','google'),
				'client_id' => $this->config->google_client_id,
				'client_secret' => $this->config->google_client_secret
			);
			$token = $this->requestHttp(GOOGLE_OAUTH2_TOKEN_URI, $post_data);
			
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
				'client_id' => $this->config->google_client_id,
				'client_secret' => $this->config->google_client_secret
			);
			$token = $this->requestHttp(GOOGLE_OAUTH2_TOKEN_URI, $post_data);
			
			$this->setAccessToken($token['access_token']);
		}
		
		/**
		 * @brief 토큰파기
		 */
		function revokeToken()
		{
			if($this->refresh_token) $token = $this->refresh_token;
			else $token = $this->access_token;
			
			if(!$token) return;
			
			$request_url = GOOGLE_OAUTH2_REVOKE_URI.'?token='.$token;
			$this->requestHttp($request_url);
		}
		
		/**
		 * @brief 계정 정보 가져오기
		 */
		function takeAccountInfo()
		{
			if(!$this->access_token) return;
			
			$request_url = GOOGLE_PEOPLE_URI.'me?access_token='.$this->access_token;
			$profile = $this->requestHttp($request_url);
			
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
			
			//Google Plus 사용자만.
			if(!$profile['isPlusUser']){
				$this->revokeToken();
				return new Object(-1, "msg_not_google_plus_user");
			}
			
			//팔로우 제한
			if($this->config->sns_follower_count){
				if($this->config->sns_follower_count > $profile['circledByCount']){
					$this->revokeToken();
					return new Object(-1, sprintf(Context::getLang('msg_not_sns_follower_count'), $this->config->sns_follower_count));
				}
			}
			
			$this->profile_id = $profile['id'];
			$this->profile_name = $profile['displayName'];
			$this->profile_image = $profile['image']['url'];
			$this->profile_url = $profile['url'];
			
			foreach($profile['emails'] as $key=> $val){
				if($val['type'] == 'account'){
					$this->profile_email = $val['value'];
					break;
				}
			}
			
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
			if($profile['aboutMe']){
				$extend->signature = $profile['aboutMe'];
			}else{
				$extend->signature = $profile['tagline'];
			}
			$extend->birthday = preg_replace('/[^0-9]*?/', '', $profile['birthday']);
			
			foreach($profile['urls'] as $key=> $val){
				if($val['type'] == 'other'){
					$extend->homepage = $val['value'];
					break;
				}
			}
			
			$gender = $profile['gender'];
			if($gender == 'male'){
				$extend->gender = '남성';
				
			}elseif($gender == 'female'){
				$extend->gender = '여성';
			}
			
			return $extend;
		}
		
		function getProfileImage()
		{
			$profile_image = $this->profile_image;
			return preg_replace('/\?sz\=[0-9]+/is', '', $profile_image);
		}	
		
		function requestHttp($request_url, $post_data = array())
		{
			if($post_data) $method = 'POST';
			else $method = 'GET';
			
			$request_config = array(
				'ssl_verify_peer' => false
			);
			
			$buff = FileHandler::getRemoteResource($request_url, null, 10, $method, 'application/x-www-form-urlencoded', array(), array(), $post_data, $request_config);
			
			$buff = json_decode($buff, true);
			
			return $buff;
		}
	}