<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollEXModel
 * @author KnDol (kndol@kndol.net)
 * @brief The model class for the pollEX modules
 */
class pollEXModel extends pollEX
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief The function examines if the user has already been pollEXed
	 */
	function isPolled($pollEX_srl)
	{
		$args = new stdClass;
		$args->pollEX_srl = $pollEX_srl;

		if(Context::get('is_logged'))
		{
			$logged_info = Context::get('logged_info');
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		}
		$output = executeQuery('pollEX.getPollLog', $args);
		if($output->data->count) return true;
		return false;
	}

	/**
	 * @brief Return the HTML data of the survey
	 * Return the result after checking if the pollEX has responses
	 */
	function getPollHtml($pollEX_srl, $style = '', $skin = 'default')
	{
		$args = new stdClass;
		$args->pollEX_srl = $pollEX_srl;
		// Get the information related to the survey
		$columnList = array('pollEX_count', 'stop_date');
		$output = executeQuery('pollEX.getPoll', $args, $columnList);
		if(!$output->data) return '';

		$pollEX = new stdClass;
		$pollEX->style = $style;
		$pollEX->pollEX_count = (int)$output->data->pollEX_count;
		$pollEX->stop_date = $output->data->stop_date;

		$columnList = array('pollEX_index_srl', 'title', 'checkcount', 'pollEX_count');
		$output = executeQuery('pollEX.getPollTitle', $args, $columnList);
		if(!$output->data) return;
		if(!is_array($output->data)) $output->data = array($output->data);

		$pollEX->pollEX = array();
		foreach($output->data as $key => $val)
		{
			$pollEX->pollEX[$val->pollEX_index_srl] = new stdClass;
			$pollEX->pollEX[$val->pollEX_index_srl]->title = $val->title;
			$pollEX->pollEX[$val->pollEX_index_srl]->checkcount = $val->checkcount;
			$pollEX->pollEX[$val->pollEX_index_srl]->pollEX_count = $val->pollEX_count;
		}

		$output = executeQuery('pollEX.getPollItem', $args);
		foreach($output->data as $key => $val)
		{
			$pollEX->pollEX[$val->pollEX_index_srl]->item[] = $val;
		}

		$pollEX->pollEX_srl = $pollEX_srl;
		// Only ongoing pollEX results
		if($pollEX->stop_date > date("Ymd"))
		{
			if($this->isPolled($pollEX_srl)) $tpl_file = "result";
			else $tpl_file = "form";
		}
		else
		{
			$tpl_file = "result";
		}

		Context::set('pollEX',$pollEX);
		Context::set('skin',$skin);
		// The skin for the default configurations, and the colorset configurations
		$tpl_path = sprintf("%sskins/%s/", $this->module_path, $skin);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Return the result's HTML
	 */
	function getPollResultHtml($pollEX_srl, $skin = 'default')
	{
		$args = new stdClass;
		$args->pollEX_srl = $pollEX_srl;
		// Get the information related to the survey
		$output = executeQuery('pollEX.getPoll', $args);
		if(!$output->data) return '';

		$pollEX = new stdClass;
		$pollEX->style = $style;
		$pollEX->pollEX_count = (int)$output->data->pollEX_count;
		$pollEX->stop_date = $output->data->stop_date;

		$columnList = array('pollEX_index_srl', 'title', 'checkcount', 'pollEX_count');
		$output = executeQuery('pollEX.getPollTitle', $args, $columnList);
		if(!$output->data) return;
		if(!is_array($output->data)) $output->data = array($output->data);

		$pollEX->pollEX = array();
		foreach($output->data as $key => $val)
		{
			$pollEX->pollEX[$val->pollEX_index_srl] = new stdClass;
			$pollEX->pollEX[$val->pollEX_index_srl]->title = $val->title;
			$pollEX->pollEX[$val->pollEX_index_srl]->checkcount = $val->checkcount;
			$pollEX->pollEX[$val->pollEX_index_srl]->pollEX_count = $val->pollEX_count;
		}

		$output = executeQuery('pollEX.getPollItem', $args);
		foreach($output->data as $key => $val)
		{
			$pollEX->pollEX[$val->pollEX_index_srl]->item[] = $val;
		}

		$pollEX->pollEX_srl = $pollEX_srl;

		$tpl_file = "result";

		Context::set('pollEX',$pollEX);
		// The skin for the default configurations, and the colorset configurations
		$tpl_path = sprintf("%sskins/%s/", $this->module_path, $skin);

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
	/** [TO REVIEW]
	 * @brief Selected pollEX - return the colorset of the skin
	 */
	function getPollGetColorsetList()
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
/* End of file pollEX.model.php */
/* Location: ./modules/pollEX/pollEX.model.php */
