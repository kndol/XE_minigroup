<?php
	/**
	 * @class  libraryKakao
     * @author CONORY (http://www.conory.com)
	 * @brief The kakao library of the socialxe module
	 */
	
	const KAKAO_OAUTH2_URI = 'https://kauth.kakao.com/oauth/';
	
	class libraryKakao extends socialxeLibrary
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
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','kakao'),
				'client_id' => $this->config->kakao_client_id,
				'state' => $_SESSION['socialxe_auth_state']
			);
			
			return KAKAO_OAUTH2_URI . 'authorize?' . http_build_query($params, '', '&');
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
				'redirect_uri' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','kakao'),
				'client_id' => $this->config->kakao_client_id
			);
			$token = $this->requestHttp('token', $post_data);
			
			$this->setAccessToken($token['access_token']);
			$this->setRefreshToken($token['refresh_token']);
			
			unset($_SESSION['socialxe_auth_state']);
			
			//카카오 스토리 사용자만.
			$request_url = 'https://kapi.kakao.com/v1/api/story/isstoryuser';
			$user = $this->requestHttp($request_url, array(), $this->access_token);
			if(!$user['isStoryUser']){
				$this->revokeToken();
				return new Object(-1, "msg_not_kakao_story_user");
			} 
			
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
				'client_id' => $this->config->kakao_client_id
			);
			$token = $this->requestHttp('token', $post_data);
			
			$this->setAccessToken($token['access_token']);
			
			if($token['refresh_token']){
				$this->setRefreshToken($token['refresh_token']);
			}
		}
		
		/**
		 * @brief 토큰파기
		 */
		function revokeToken()
		{
			if(!$this->access_token) return;
			
			$request_url = 'https://kapi.kakao.com/v1/user/logout';
			$this->requestHttp($request_url, array(), $this->access_token);
		}
		
		/**
		 * @brief 계정 정보 가져오기
		 */
		function takeAccountInfo()
		{
			if(!$this->access_token) return;
			
			$request_url = 'https://kapi.kakao.com/v1/api/story/profile';
			$profile = $this->requestHttp($request_url, array(), $this->access_token);
			
			return $profile;
		}
		
		/**
		 * @brief 활동쓰기 (트윗)
		 */
		function insertActivities($args)
		{
			if(!$this->access_token) return;
			
			$content = $args->title.' '.$args->url;
			$request_url = 'https://kapi.kakao.com/v1/api/story/post/note';
			$this->requestHttp($request_url, array('content' => $content), $this->access_token);
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
			
			$this->profile_id = str_replace('https://story.kakao.com/', '', $profile['permalink']);
			$this->profile_name = $profile['nickName'];
			$this->profile_image = $profile['profileImageURL'];
			$this->profile_url = $profile['permalink'];
			
			//프로필 인증
			$this->profile_verified = true;
			
			//이메일을 불려올 수 없음
			$this->profile_email = '';
			
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
			$extend->birthday = date('Y').$profile['birthday'];
			
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
					'Authorization' => 'Bearer '.$authorization
				);
			}
			
			$request_config = array(
				'ssl_verify_peer' => false
			);
			
			if($request_url == 'token'){
				$request_url = KAKAO_OAUTH2_URI . 'token';
			}
			
			$buff = FileHandler::getRemoteResource($request_url, null, 10, $method, 'application/x-www-form-urlencoded', $headers, array(), $post_data, $request_config);
			
			$buff = json_decode($buff, true);
			
			return $buff;
		}
	}