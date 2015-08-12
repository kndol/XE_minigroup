<?php
	/**
	 * @class  libraryTwitter
     * @author CONORY (http://www.conory.com)
	 * @brief The twitter library of the socialxe module
	 */
	
	require_once(_XE_PATH_.'modules/socialxe/libs/twitter/autoload.php');
	use Abraham\TwitterOAuth\TwitterOAuth;
	
	class libraryTwitter extends socialxeLibrary
	{
		/**
		 * @brief 인증 URL 생성
		 */
		function createAuthUrl($type)
		{
			$_SESSION['socialxe_auth_redirect_mid'] = Context::get('mid');
			$_SESSION['socialxe_auth_redirect'] = Context::get('redirect');
			$_SESSION['socialxe_auth_type'] = $type;
			
			$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret);
			$request_token = $connection->oauth('oauth/request_token', array(
				'oauth_callback' => getNotEncodedFullUrl('', 'module', 'socialxe', 'act', 'procSocialxeCallback','service','twitter')
			));
			
			$_SESSION['twitter_oauth_token'] = $request_token['oauth_token'];
			$_SESSION['twitter_oauth_token_secret'] = $request_token['oauth_token_secret'];
			
			$auth_url = $connection->url('oauth/authenticate', array('oauth_token' => $_SESSION['twitter_oauth_token']));
			
			return $auth_url;
		}
		
		/**
		 * @brief 코드인증
		 */
		function authenticate()
		{
			$oauth_token = Context::get('oauth_token');
			$oauth_verifier = Context::get('oauth_verifier');
			if(!$oauth_verifier || !$_SESSION['twitter_oauth_token_secret'] || !$_SESSION['twitter_oauth_token'] || $_SESSION['twitter_oauth_token'] !== $oauth_token) return new Object(-1, "msg_invalid_request");
			
			$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $_SESSION['twitter_oauth_token'], $_SESSION['twitter_oauth_token_secret']);
			
			$token = $connection->oauth("oauth/access_token", array('oauth_verifier' => $oauth_verifier));
			$access_token = json_encode(array('token' => $token['oauth_token'], 'token_secret' => $token['oauth_token_secret']));
			
			$this->setAccessToken($access_token);
			$this->setRefreshToken($access_token);
			
			unset($_SESSION['twitter_oauth_token']);
			unset($_SESSION['twitter_oauth_token_secret']);
			
			return new Object();
		}
		
		/**
		 * @brief 토큰 새로고침 (트위터의 경우 유효기간이 없는 무제한 토큰므로 필요없음)
		 */
		function refreshToken()
		{
			$this->setAccessToken($this->refresh_token);
		}
		
		/**
		 * @brief 토큰파기 (트위터의 경우 따로 파기할 수 없음)
		 */
		function revokeToken()
		{
		}
		
		/**
		 * @brief 계정 정보 가져오기
		 */
		function takeAccountInfo()
		{
			if(!$this->access_token) return;
			
			$access_token = json_decode($this->access_token, true);
			
			$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $access_token['token'], $access_token['token_secret']);
			
			$profile = $connection->get("account/verify_credentials");
			
			return $profile;
		}
		
		/**
		 * @brief 활동쓰기 (트윗)
		 */
		function insertActivities($args)
		{
			if(!$this->access_token) return;
			
			$access_token = json_decode($this->access_token, true);
			
			$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $access_token['token'], $access_token['token_secret']);
			
			$status = $args->title.' '.$args->url;
			$connection->post("statuses/update", array('status' => $status));
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
			
			//계정 차단 확인
			if($this->config->sns_suspended_account == 'Y'){
				$access_token = json_decode($this->access_token, true);
				$connection = new TwitterOAuth($this->config->twitter_consumer_key, $this->config->twitter_consumer_secret, $access_token['token'], $access_token['token_secret']);			
				
				$user = $connection->get("users/show", array('user_id' => $profile['id']));
				if(!$user['id']) return new Object(-1, 'msg_sns_suspended_account');
			}
			
			//팔로우 제한
			if($this->config->sns_follower_count){
				if($this->config->sns_follower_count > $profile['followers_count']){
					$this->revokeToken();
					return new Object(-1, sprintf(Context::getLang('msg_not_sns_follower_count'), $this->config->sns_follower_count));
				}
			}
			
			$this->profile_id = $profile['id'];
			$this->profile_name = $profile['name'];
			$this->profile_image = $profile['profile_image_url'];
			$this->profile_url = 'https://twitter.com/'.$profile['screen_name'];
			
			//트위터의 경우 이메일을 확인할 수 없음.
			$this->profile_email = '';
			
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
			$extend->signature = $profile['description'];
			$extend->homepage = $profile['entities']['url']['urls'][0]['expanded_url'];
			
			return $extend;
		}
	}