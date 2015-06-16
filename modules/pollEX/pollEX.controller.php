<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollexController
 * @author KnDol (kndol@kndol.net)
 * @brief Controller class for pollex module
 */
class pollexController extends pollex
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
	function procPollexInsert()
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

			$poll_index = $tmp_arr[1];
			if(!$poll_index)
			{
				continue;
			}

			if(!trim($val))
			{
				continue;
			}

			if($tmp_args[$poll_index] == NULL)
			{
				$tmp_args[$poll_index] = new stdClass;
			}

			if(!is_array($tmp_args[$poll_index]->item))
			{
				$tmp_args[$poll_index]->item = array();
			}

			if($logged_info->is_admin != 'Y')
			{
				$val = htmlspecialchars($val, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
			}

			switch($tmp_arr[0])
			{
				case 'title':
					$tmp_args[$poll_index]->title = $val;
					break;
				case 'checkcount':
					$tmp_args[$poll_index]->checkcount = $val;
					break;
				case 'item':
					$tmp_args[$poll_index]->item[] = $val;
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
				$args->pollex[] = $val;
			}
		}

		if(!count($args->pollex)) return new Object(-1, 'cmd_null_item');

		$args->stop_date = $stop_date;

		$option = new stdClass();
		$option->show_result = $vars->opt_show_result;
		$option->voter_result = $vars->opt_voter_result;
		$option->expire_result = $vars->opt_expire_result;
		$option->show_voters = $vars->opt_show_voters;

		// Configure the variables
		$poll_srl = getNextSequence();
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$oDB = &DB::getInstance();
		$oDB->begin();

		// Register the pollex
		$pollex_args = new stdClass;
		$pollex_args->poll_srl = $poll_srl;
		$pollex_args->member_srl = $member_srl;
		$pollex_args->list_order = $poll_srl*-1;
		$pollex_args->stop_date = $args->stop_date;
		$pollex_args->poll_count = 0;
		$pollex_args->option = serialize($option);
		$output = executeQuery('pollex.insertPollex', $pollex_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		// Individual pollex registration
		foreach($args->pollex as $key => $val)
		{
			$title_args = new stdClass;
			$title_args->poll_srl = $poll_srl;
			$title_args->poll_index_srl = getNextSequence();
			$title_args->title = $val->title;
			$title_args->checkcount = $val->checkcount;
			$title_args->poll_count = 0;
			$title_args->list_order = $title_args->poll_index_srl * -1;
			$title_args->member_srl = $member_srl;
			$title_args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('pollex.insertPollexTitle', $title_args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			// Add the individual survey items
			foreach($val->item as $k => $v)
			{
				$item_args = new stdClass;
				$item_args->poll_srl = $poll_srl;
				$item_args->poll_index_srl = $title_args->poll_index_srl;
				$item_args->title = $v;
				$item_args->poll_count = 0;
				$item_args->upload_target_srl = $upload_target_srl;
				$output = executeQuery('pollex.insertPollexItem', $item_args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();

		$this->add('poll_srl', $poll_srl);
		$this->setMessage('success_registed');
	}

	/**
	 * @brief Accept the pollex
	 */
	function procPollex()
	{
		$poll_srl = Context::get('poll_srl');
		$poll_srl_indexes = Context::get('poll_srl_indexes');
		$tmp_item_srls = explode(',',$poll_srl_indexes);
		for($i=0;$i<count($tmp_item_srls);$i++)
		{
			$srl = (int)trim($tmp_item_srls[$i]);
			if(!$srl) continue;
			$item_srls[] = $srl;
		}

		// If there is no response item, display an error
		if(!count($item_srls)) return new Object(-1, 'msg_check_poll_item');
		// Make sure is the pollex has already been taken
		$oPollModel = getModel('pollex');
		if($oPollModel->isPolled($poll_srl)) return new Object(-1, 'msg_already_poll');

		$oDB = &DB::getInstance();
		$oDB->begin();

		$args = new stdClass;
		$args->poll_srl = $poll_srl;
		// Update all pollex responses related to the post
		$output = executeQuery('pollex.updatePollex', $args);
		$output = executeQuery('pollex.updatePollexTitle', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Record each polls selected items
		$poll_item_srl = implode(',',$item_srls);
		$args->poll_item_srl = $poll_item_srl;
		$output = executeQuery('pollex.updatePollexItems', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		// Log the respondent's information
		$log_args = new stdClass;
		$log_args->poll_srl = $poll_srl;
		$log_args->poll_item_srl = $poll_item_srl;

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl?$logged_info->member_srl:0;

		$log_args->member_srl = $member_srl;
		$log_args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$output = executeQuery('pollex.insertPollexLog', $log_args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		$skin = Context::get('skin');
		if(!$skin || !is_dir(_XE_PATH_ . 'modules/pollex/skins/'.$skin)) $skin = 'default';
		// Get tpl
		$tpl = $oPollModel->getPollexHtml($poll_srl, '', $skin);

		$this->add('poll_srl', $poll_srl);
		$this->add('tpl',$tpl);
		$this->setMessage('success_poll');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollexAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Preview the results
	 */
	function procPollexViewResult()
	{
		$poll_srl = Context::get('poll_srl');

		$skin = Context::get('skin');
		if(!$skin || !is_dir(_XE_PATH_ . 'modules/pollex/skins/'.$skin)) $skin = 'default';

		$oPollModel = getModel('pollex');
		$tpl = $oPollModel->getPollexResultHtml($poll_srl, $skin);

		$this->add('poll_srl', $poll_srl);
		$this->add('tpl',$tpl);
	}

	/**
	 * @brief pollex list
	 */
	function procPollexGetList()
	{
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
		$pollexSrls = Context::get('poll_srls');
		if($pollexSrls) $pollexSrlList = explode(',', $pollexSrls);

		global $lang;
		if(count($pollexSrlList) > 0)
		{
			$oPollAdminModel = getAdminModel('pollex');
			$args = new stdClass;
			$args->pollexIndexSrlList = $pollexSrlList;
			$output = $oPollAdminModel->getPollexListWithMember($args);
			$pollexList = $output->data;

			if(is_array($pollexList))
			{
				foreach($pollexList AS $key=>$value)
				{
					if($value->checkcount == 1) $value->checkName = $lang->single_check;
					else $value->checkName = $lang->multi_check;
				}
			}
		}
		else
		{
			$pollexList = array();
			$this->setMessage($lang->no_documents);
		}

		$this->add('poll_list', $pollexList);
	}

	/**
	 * @brief A pollex synchronization trigger when a new post is registered
	 */
	function triggerInsertDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollex synchronization trigger when a new comment is registered
	 */
	function triggerInsertCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollex synchronization trigger when a post is updated
	 */
	function triggerUpdateDocumentPoll(&$obj)
	{
		$this->syncPoll($obj->document_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollex synchronization trigger when a comment is updated
	 */
	function triggerUpdateCommentPoll(&$obj)
	{
		$this->syncPoll($obj->comment_srl, $obj->content);
		return new Object();
	}

	/**
	 * @brief A pollex deletion trigger when a post is removed
	 */
	function triggerDeleteDocumentPoll(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return new Object();
		// Get the pollex
		$args = new stdClass();
		$args->upload_target_srl = $document_srl;
		$output = executeQuery('pollex.getPollexByTargetSrl', $args);
		if(!$output->data) return new Object();

		$poll_srl = $output->data->poll_srl;
		if(!$poll_srl) return new Object();

		$args->poll_srl = $poll_srl;

		$output = executeQuery('pollex.deletePollex', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexLog', $args);
		if(!$output->toBool()) return $output;

		return new Object();
	}

	/**
	 * @brief A pollex deletion trigger when a comment is removed
	 */
	function triggerDeleteCommentPoll(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		if(!$comment_srl) return new Object();
		// Get the pollex
		$args = new stdClass();
		$args->upload_target_srl = $comment_srl;
		$output = executeQuery('pollex.getPollexByTargetSrl', $args);
		if(!$output->data) return new Object();

		$poll_srl = $output->data->poll_srl;
		if(!$poll_srl) return new Object();

		$args->poll_srl = $poll_srl;

		$output = executeQuery('pollex.deletePollex', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexItem', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexTitle', $args);
		if(!$output->toBool()) return $output;

		$output = executeQuery('pollex.deletePollexLog', $args);
		if(!$output->toBool()) return $output;

		return new Object();
	}

	/**
	 * @brief As post content's pollex is obtained, synchronize the pollex using the document number
	 */
	function syncPoll($upload_target_srl, $content)
	{
		$match_cnt = preg_match_all('!<img([^\>]*)poll_srl=(["\']?)([0-9]*)(["\']?)([^\>]*?)\>!is',$content, $matches);
		for($i=0;$i<$match_cnt;$i++)
		{
			$poll_srl = $matches[3][$i];

			$args = new stdClass;
			$args->poll_srl = $poll_srl;
			$output = executeQuery('pollex.getPollex', $args);
			$pollex = $output->data;

			if($pollex->upload_target_srl) continue;

			$args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('pollex.updatePollexTarget', $args);
			$output = executeQuery('pollex.updatePollexTitleTarget', $args);
			$output = executeQuery('pollex.updatePollexItemTarget', $args);
		}
	}
}
/* End of file pollex.controller.php */
/* Location: ./modules/pollex/pollex.controller.php */
