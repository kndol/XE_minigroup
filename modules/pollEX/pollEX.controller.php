<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollEXController
 * @author KnDol (kndol@kndol.net)
 * @brief Controller class for pollEX module
 */
class pollEXController extends pollEX
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief after a qeustion is created in the popup window, register the question during the save time
	 */
	function procPollInsert()
	{
		$stop_date = Context::get('stop_date');
		if($stop_date < date('Ymd'))
		{
			$stop_date = date('YmdHis', $_SERVER['REQUEST_TIME']+60*60*24*365);
		}

		$logged_info = Context::get('logged_info');
		$vars = Context::getRequestVars();
		$args = new stdClass;
		$tmp_args = array();

		unset($vars->_filter);
		unset($vars->error_return_url);
		unset($vars->stop_date);

		foreach($vars as $key => $val)
		{
			if(stripos($key, 'tidx'))
			{
				continue;
			}

			$tmp_arr = explode('_', $key);

			$pollEX_index = $tmp_arr[1];
			if(!$pollEX_index)
			{
				continue;
			}

			if(!trim($val))
			{
				continue;
			}

			if($tmp_args[$pollEX_index] == NULL)
			{
				$tmp_args[$pollEX_index] = new stdClass;
			}

			if(!is_array($tmp_args[$pollEX_index]->item))
			{
				$tmp_args[$pollEX_index]->item = array();
			}

			if($logged_info->is_admin != 'Y')
			{
				$val = htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
			}

			switch($tmp_arr[0])
			{
				case 'title':
					$tmp_args[$pollEX_index]->title = $val;
					break;
				case 'checkcount':
					$tmp_args[$pollEX_index]->checkcount = $val;
					break;
				case 'item':
					$tmp_args[$pollEX_index]->item[] = $val;
					break;
			}
		}

		foreach($tmp_args as $key => $val)
		{
			if(!$val->checkcount)
			{
				$val->checkcount = 1;
			}

			if($val->title && count($val->item))
			{
				$args->pollEX[] = $val;
			}
		}

		if(!count($args->pollEX)) return new Object(-1, 'cmd_null_item');

		$args->stop_date = $stop_date;

		// Configure the variables
		$pollEX_srl = getNextSequence();
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$oDB = &DB::getInstance();
		$oDB->begin();

		// Register the pollEX
		$pollEX_args = new stdClass;
		$pollEX_args->pollEX_srl = $pollEX_srl;
		$pollEX_args->member_srl = $member_srl;
		$pollEX_args->list_order = $pollEX_srl*-1;
		$pollEX_args->stop_date = $args->stop_date;
		$pollEX_args->pollEX_count = 0;
		$output = executeQuery('pollEX.insertPoll', $pollEX_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Individual pollEX registration
		foreach($args->pollEX as $key => $val)
		{
			$title_args = new stdClass;
			$title_args->pollEX_srl = $pollEX_srl;
			$title_args->pollEX_index_srl = getNextSequence();
			$title_args->title = $val->title;
			$title_args->checkcount = $val->checkcount;
			$title_args->pollEX_count = 0;
			$title_args->list_order = $title_args->pollEX_index_srl * -1;
			$title_args->member_srl = $member_srl;
			$title_args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('pollEX.insertPollTitle', $title_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// Add the individual survey items
			foreach($val->item as $k => $v)
			{
				$item_args = new stdClass;
				$item_args->pollEX_srl = $pollEX_srl;
				$item_args->pollEX_index_srl = $title_args->pollEX_index_srl;
				$item_args->title = $v;
				$item_args->pollEX_count = 0;
				$item_args->upload_target_srl = $upload_target_srl;
				$output = executeQuery('pollEX.insertPollItem', $item_args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();

		$this->add('pollEX_srl', $pollEX_srl);
		$this->setMessage('success_registed');
	}

	/**
	 * @brief Accept the pollEX
	 */
	function procPoll()
	{
		$pollEX_srl = Context::get('pollEX_srl');
		$pollEX_srl_indexes = Context::get('pollEX_srl_indexes');
		$tmp_item_srls = explode(',',$pollEX_srl_indexes);
		for($i=0;$i<count($tmp_item_srls);$i++)
		{
			$srl = (int)trim($tmp_item_srls[$i]);
			if(!$srl) continue;
			$item_srls[] = $srl;
		}

		// If there is no response item, display an error
		if(!count($item_srls)) return new Object(-1, 'msg_check_pollEX_item');
		// Make sure is the pollEX has already been taken
		$oPollModel = getModel('pollEX');
		if($oPollModel->isPolled($pollEX_srl)) return new Object(-1, 'msg_already_pollEX');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$args = new stdClass;
		$args->pollEX_srl = $pollEX_srl;
		// Update all pollEX responses related to the post
		$output = executeQuery('pollEX.updatePoll', $args);
		$output = executeQuery('pollEX.updatePollTitle', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Record each pollEXs selected items
		$args->pollEX_item_srl = implode(',',$item_srls);
		$output = executeQuery('pollEX.updatePollItems', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Log the respondent's information
		$log_args = new stdClass;
		$log_args->pollEX_srl = $pollEX_srl;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$log_args->member_srl = $member_srl;
		$log_args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$output = executeQuery('pollEX.insertPollLog', $log_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		$skin = Context::get('skin');
		if(!$skin || !is_dir(_XE_PATH_ . 'modules/pollEX/skins/'.$skin)) $skin = 'default';
		// Get tpl
		$tpl = $oPollModel->getPollHtml($pollEX_srl, '', $skin);

		$this->add('pollEX_srl', $pollEX_srl);
		$this->add('tpl',$tpl);
		$this->setMessage('success_pollEX');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Preview the results
	 */
	function procPollViewResult()
	{
		$pollEX_srl = Context::get('pollEX_srl');

		$skin = Context::get('skin');
		if(!$skin || !is_dir(_XE_PATH_ . 'modules/pollEX/skins/'.$skin)) $skin = 'default';

		$oPollModel = getModel('pollEX');
		$tpl = $oPollModel->getPollResultHtml($pollEX_srl, $skin);

		$this->add('pollEX_srl', $pollEX_srl);
		$this->add('tpl',$tpl);
	}

	/**
	 * @brief pollEX list
	 */
	function procPollGetList()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
		$pollEXSrls = Context::get('pollEX_srls');
		if($pollEXSrls) $pollEXSrlList = explode(',', $pollEXSrls);

		global $lang;
		if(count($pollEXSrlList) > 0)
		{
			$oPollAdminModel = getAdminModel('pollEX');
			$args = new stdClass;
			$args->pollEXIndexSrlList = $pollEXSrlList;
			$output = $oPollAdminModel->getPollListWithMember($args);
			$pollEXList = $output->data;

			if(is_array($pollEXList))
			{
				foreach($pollEXList AS $key=>$value)
				{
					if($value->checkcount == 1) $value->checkName = $lang->single_check;
					else $value->checkName = $lang->multi_check;
				}
			}
		}
		else
		{
			$pollEXList = array();
			$this->setMessage($lang->no_documents);
		}

		$this->add('pollEX_list', $pollEXList);
	}

	/**
	 * @brief A pollEX synchronization trigger when a new post is registered
	 */
	function triggerInsertDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollEX synchronization trigger when a new comment is registered
	 */
	function triggerInsertCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollEX synchronization trigger when a post is updated
	 */
	function triggerUpdateDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollEX synchronization trigger when a comment is updated
	 */
	function triggerUpdateCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollEX deletion trigger when a post is removed
	 */
	function triggerDeleteDocumentPoll(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return new Object();
		// Get the pollEX
		$args = new stdClass();
		$args->upload_target_srl = $document_srl;
		$output = executeQuery('pollEX.getPollByTargetSrl', $args);
		if(!$output->data) return new Object();

		$pollEX_srl = $output->data->pollEX_srl;
		if(!$pollEX_srl) return new Object();

		$args->pollEX_srl = $pollEX_srl;

		$output = executeQuery('pollEX.deletePoll', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollLog', $args);
		if(!$output->toBool()) return $output;

		return new Object();
	}

	/**
	 * @brief A pollEX deletion trigger when a comment is removed
	 */
	function triggerDeleteCommentPoll(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		if(!$comment_srl) return new Object();
		// Get the pollEX
		$args = new stdClass();
		$args->upload_target_srl = $comment_srl;
		$output = executeQuery('pollEX.getPollByTargetSrl', $args);
		if(!$output->data) return new Object();

		$pollEX_srl = $output->data->pollEX_srl;
		if(!$pollEX_srl) return new Object();

		$args->pollEX_srl = $pollEX_srl;

		$output = executeQuery('pollEX.deletePoll', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollEX.deletePollLog', $args);
		if(!$output->toBool()) return $output;

		return new Object();
	}

	/**
	 * @brief As post content's pollEX is obtained, synchronize the pollEX using the document number
	 */
	function syncPoll($upload_target_srl, $content)
	{
		$match_cnt = preg_match_all('!<img([^\>]*)pollEX_srl=(["\']?)([0-9]*)(["\']?)([^\>]*?)\>!is',$content, $matches);
		for($i=0;$i<$match_cnt;$i++)
		{
			$pollEX_srl = $matches[3][$i];

			$args = new stdClass;
			$args->pollEX_srl = $pollEX_srl;
			$output = executeQuery('pollEX.getPoll', $args);
			$pollEX = $output->data;

			if($pollEX->upload_target_srl) continue;

			$args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('pollEX.updatePollTarget', $args);
			$output = executeQuery('pollEX.updatePollTitleTarget', $args);
			$output = executeQuery('pollEX.updatePollItemTarget', $args);
		}
	}
}
/* End of file pollEX.controller.php */
/* Location: ./modules/pollEX/pollEX.controller.php */
