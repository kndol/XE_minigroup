<?php
	/**
	 * @class  socialxeLibrary
     * @author CONORY (http://www.conory.com)
	 * @brief The library class of the socialxe module
	 */
	
	class socialxeLibrary extends socialxe
	{
		protected $service = NULL;
		protected $profile_id = NULL;
		protected $profile_email = NULL;
		protected $profile_name = NULL;
		protected $profile_image = NULL;
		protected $profile_url = NULL;
		protected $profile_info = NULL;
		protected $profile_verified = NULL;
		protected $access_token = NULL;
		protected $refresh_token = NULL;
		
		function __construct($service)
		{
			$this->service = $service;
			$this->config = $this->getConfig();
		}
		
		function createAuthUrl()
		{
		}
		
		function authenticate()
		{
			return new Object();
		}
		
		function refreshToken()
		{
		}
		
		function revokeToken()
		{
		}
		
		function takeAccountInfo()
		{
		}
		
		function setProfile()
		{
			return new Object();
		}
		
		function setAccessToken($access_token)
		{
			if(!$access_token) return;
			
			$this->access_token = $access_token;
		}
		
		function setRefreshToken($refresh_token)
		{
			if(!$refresh_token) return;
			
			$this->refresh_token = $refresh_token;
		}
		
		function set($key, $val)
		{
			if(!isset($this->{$key}) || !$val) return;

			$this->{$key} = $val;
		}
		
		function get($key)
		{
			if($key && isset($this->{$key})){
				return $this->{$key};
			}else{
				return array(
					'service' => $this->getService(),
					'profile_info' => $this->getProfileInfo(),
					'access_token' => $this->getAccessToken(),
					'refresh_token' => $this->getRefreshToken()
				);
			}
		}
		
		function getId()
		{
			return $this->profile_id;
		}
		
		function getEmail()
		{
			return $this->profile_email;
		}
		
		function getName()
		{
			return $this->profile_name;
		}
		
		function getProfileImage()
		{
			return $this->profile_image;
		}
		
		function getProfileUrl()
		{
			return $this->profile_url;
		}
		
		function getProfileInfo()
		{
			return $this->profile_info;
		}
		
		function getProfileExtend()
		{
			if(!$this->profile_info) return;
			
			$extend = new stdClass;
			$extend->signature = '';
			$extend->homepage = '';
			$extend->blog = '';
			$extend->birthday = '';
			$extend->gender = '';
			
			return $extend;
		}
		
		function getVerified()
		{
			return $this->profile_verified;
		}
		
		function getAccessToken()
		{
			return $this->access_token;
		}
		
		function getRefreshToken()
		{
			return $this->refresh_token;
		}
		
		function getService()
		{
			return $this->service;
		}
	}