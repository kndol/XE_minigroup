<?php
/**
 * @class  minigroupAdminModel
 * @author 큰돌 (kndol@kndol.net)
 * @brief  minigroup 모듈의 admin model class
 **/

define(PENDING_MEMBER,   1);
define(ASSOCIATE_MEMBER, 2);
define(MEMBER,           3);
define(STAFF,            4);

class minigroupAdminModel extends minigroup
{
	function init()
	{
	}

	function getMinigroupList($args)
	{
		if(!$args->page) $args->page = 1;
		$this->_setSearchOption($args);
		$output = executeQueryArray('minigroup.getMinigroupList', $args);
		return $output;
	}

	function _setSearchOption(&$args)
	{
		switch($args->search_target)
		{
			case 'title':
			case 'domain':
				$args->{'s_'.$args->search_target} = $args->search_keyword;
				break;
		}
	}

	/**
	 * @brief 주어진 그룹의 srl 구하기
	 **/
	function getMinigroupSiteGroupSrl($site_srl, $group_no)
	{
		if (!is_numeric($group_no)) return new Object(-1,'msg_invalid_request');

		$args->site_srl = $site_srl;
		$args->title = sprintf("\$user_lang->default_group%d", $group_no);
		
		$output = executeQueryArray('minigroup.getSiteGroupSrl', $args);
		if(!$output->toBool()) return $output;

		return $output->data[0]->group_srl;
	}

	/**
	 * @brief 운영진 그룹 멤버들의 srl 구하기
	 **/
	function getMinigroupSiteStaffMemberSrls($site_srl)
	{
		$oModuleModel = &getModel('module');
		$member_srls = array();

		$args->site_srl = $site_srl;
		$args->group_srl = $this->getMinigroupSiteGroupSrl($site_srl, STAFF);
		$output = executeQueryArray('minigroup.getSiteGroupMemberSrls', $args);
		if(!$output->toBool()) return $output;

		foreach ($output->data as &$value) {
		 	 array_push($member_srls, $value->member_srl);
		}

		return $member_srls;
	}

	/**
	 * @brief ','로 구분된 회원 목록에 있는 회원들의 srl 구하기
	 **/
	function getMinigroupMemberSrlsFromMemberList($member_list)
	{
		$oModuleModel = &getModel('module');
		$oMemberModel = &getModel('member');
		$member_srls = array();
		$member_config = $oMemberModel->getMemberConfig();
		$use_email = ($member_config->identifier == 'email_address');
		
		$args->member_list = $member_list;
		$output = ($use_email) ? executeQueryArray('minigroup.getMemberSrlsByEmail', $args) : executeQueryArray('minigroup.getMemberSrlsByUserID', $args);
		if(!$output->toBool()) return $output;

		foreach ($output->data as &$value) {
		 	 array_push($member_srls, $value->member_srl);
		}

		return $member_srls;
	}
}
/* End of file minigroup.admin.model.php */
/* Location: ./modules/minigroup/minigroup.admin.model.php */