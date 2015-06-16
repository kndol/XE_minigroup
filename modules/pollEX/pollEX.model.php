<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollexModel
 * @author KnDol (kndol@kndol.net)
 * @brief The model class for the pollex modules
 */
class pollexModel extends pollex
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief The function examines if the user has already been polled
	 */
	function isPolled($poll_srl)
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		}
		$output = executeQuery('pollex.getPollexLog', $args);
		if($output->data->count) return true;
		return false;
	}

	/**
	 * @brief Return the HTML data of the survey
	 * Return the result after checking if the pollex has responses
	 */
	function getPollexHtml($poll_srl, $style = '', $skin = 'default', $show_retult = false)
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;
		// Get the information related to the survey
		$columnList = array('member_srl', 'poll_count', 'stop_date', 'option');
		$output = executeQuery('pollex.getPollex', $args, $columnList);
		if(!$output->data) return '';
		$logged_info = Context::get('logged_info');

		$pollex = new stdClass;
		$pollex->style = $style;
		$pollex->researcher = $logged_info->is_admin=='Y' || $logged_info->member_srl == $output->data->member_srl;
		$pollex->poll_count = (int)$output->data->poll_count;
		$pollex->stop_date = $output->data->stop_date;
		$pollex->option = unserialize($output->data->option);

		$columnList = array('poll_index_srl', 'title', 'checkcount', 'poll_count');
		$output = executeQuery('pollex.getPollexTitle', $args, $columnList);
		if(!$output->data) return;
		if(!is_array($output->data)) $output->data = array($output->data);

		$pollex->poll = array();
		foreach($output->data as $key => $val)
		{
			$pollex->poll[$val->poll_index_srl] = new stdClass;
			$pollex->poll[$val->poll_index_srl]->title = $val->title;
			$pollex->poll[$val->poll_index_srl]->checkcount = $val->checkcount;
			$pollex->poll[$val->poll_index_srl]->poll_count = $val->poll_count;
		}

		$output = executeQuery('pollex.getPollexItemWithMember', $args);
debugPrint($output);
		$i = -1;
		foreach($output->data as $key => $val)
		{
			if ($i<0 || $pollex->poll[$val->poll_index_srl]->item[$i]->poll_item_srl != $val->poll_item_srl)
			{
				if ($i<0) $pollex->poll[$val->poll_index_srl]->item = array();
				$pollex->poll[$val->poll_index_srl]->item[++$i] = new stdClass;
				$pollex->poll[$val->poll_index_srl]->item[$i]->poll_item_srl = $val->poll_item_srl;
				$pollex->poll[$val->poll_index_srl]->item[$i]->poll_srl = $val->poll_srl;
				$pollex->poll[$val->poll_index_srl]->item[$i]->poll_index_srl = $val->poll_index_srl;
				$pollex->poll[$val->poll_index_srl]->item[$i]->upload_target_srl = $val->upload_target_srl;
				$pollex->poll[$val->poll_index_srl]->item[$i]->title = $val->title;
				$pollex->poll[$val->poll_index_srl]->item[$i]->poll_count = $val->poll_count;
				$pollex->poll[$val->poll_index_srl]->item[$i]->members = array();
			}
			if ($val->member_srl != '')
			{
				$pollex->poll[$val->poll_index_srl]->item[$i]->members[$val->member_srl] = new stdClass;
				$pollex->poll[$val->poll_index_srl]->item[$i]->members[$val->member_srl]->member_srl = $val->member_srl;
				$pollex->poll[$val->poll_index_srl]->item[$i]->members[$val->member_srl]->user_name = $val->user_name;
				$pollex->poll[$val->poll_index_srl]->item[$i]->members[$val->member_srl]->regdate = $val->regdate;
			}
		}
debugPrint($pollex);

		$pollex->poll_srl = $poll_srl;
		// Only ongoing pollex results
		if(!$show_retult && $pollex->stop_date > date("Ymd"))
		{
			if($this->isPolled($poll_srl)) $tpl_file = "result";
			else $tpl_file = "form";
		}
		else
		{
			$tpl_file = "result";
		}

		Context::set('poll',$pollex);
		Context::set('skin',$skin);
		// The skin for the default configurations, and the colorset configurations
		$tpl_path = sprintf("%sskins/%s/", $this->module_path, $skin);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Return the result's HTML
	 */
	function getPollexResultHtml($poll_srl, $skin = 'default')
	{
		return $this->getPollexHtml($poll_srl, '', $skin, true);
	}

	/** [TO REVIEW]
	 * @brief Selected pollex - return the colorset of the skin
	 */
	function getPollexGetColorsetList()
	{
		$skin = Context::get('skin');

		$oModuleModel = getModel('module');
		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

		for($i=0;$i<count($skin_info->colorset);$i++)
		{
			$colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
			$colorset_list[] = $colorset;
		}

		if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
		$this->add('colorset_list', $colorsets);
	}
}
/* End of file pollex.model.php */
/* Location: ./modules/pollex/pollex.model.php */
