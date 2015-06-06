<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollEXAdminView
 * @author KnDol (kndol@kndol.net)
 * @brief The admin view class of the pollEX module
 */
class pollEXAdminView extends pollEX
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Administrator's Page
	 */
	function dispPollAdminList()
	{
		// Arrange the search options
		$search_target = trim(Context::get('search_target'));
		$search_keyword = trim(Context::get('search_keyword'));

		$args = new stdClass();
		if($search_target && $search_keyword)
		{
			switch($search_target)
			{
				case 'title' :
					if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
					$args->s_title= $search_keyword;
					break;
				case 'regdate' :
					$args->s_regdate = $search_keyword;
					break;
				case 'ipaddress' :
					$args->s_ipaddress= $search_keyword;
					break;
			}
		}
		// Options to get a list of pages
		$args->page = Context::get('page');
		$args->list_count = 50; // The number of posts to show on one page
		$args->page_count = 10; // The number of pages to display in the page navigation

		$args->sort_index = 'P.list_order'; // Sorting value

		// Get the list
		$oPollAdminModel = getAdminModel('pollEX');
		$output = $oPollAdminModel->getPollListWithMember($args);

		// check pollEX type. document or comment
		if(is_array($output->data))
		{
			$uploadTargetSrlList = array();
			foreach($output->data as $value)
			{
				$uploadTargetSrlList[] = $value->upload_target_srl;
			}

			$oDocumentModel = getModel('document');
			$targetDocumentOutput = $oDocumentModel->getDocuments($uploadTargetSrlList);
			if(!is_array($targetDocumentOutput)) $targetDocumentOutput = array();

			$oCommentModel = getModel('comment');
			$columnList = array('comment_srl', 'document_srl');
			$targetCommentOutput = $oCommentModel->getComments($uploadTargetSrlList, $columnList);
			if(!is_array($targetCommentOutput)) $targetCommentOutput = array();

			foreach($output->data as $value)
			{
				if(array_key_exists($value->upload_target_srl, $targetDocumentOutput))
					$value->document_srl = $value->upload_target_srl;

				if(array_key_exists($value->upload_target_srl, $targetCommentOutput))
				{
					$value->comment_srl = $value->upload_target_srl;
					$value->document_srl = $targetCommentOutput[$value->comment_srl]->document_srl;
				}
			}
		}

		// Configure the template variables
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);
		Context::set('pollEX_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('module_list', $module_list);

		$security = new Security();
		$security->encodeHTML('pollEX_list..title', 'pollEX_list..nick_name');
		// Set a template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('pollEX_list');
	}

	/**
	 * @brief Confgure the pollEX skin and colorset
	 */
	function dispPollAdminConfig()
	{
		$oModuleModel = getModel('module');
		// Get the configuration information
		$config = $oModuleModel->getModuleConfig('pollEX');
		Context::set('config', $config);
		// Get the skin information
		$skin_list = $oModuleModel->getSkins($this->module_path);
		Context::set('skin_list', $skin_list);

		if(!$skin_list[$config->skin]) $config->skin = "default";
		// Set the skin colorset once the configurations is completed
		Context::set('colorset_list', $skin_list[$config->skin]->colorset);

		$security = new Security();
		$security->encodeHTML('config..');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('colorset_list..name','colorset_list..title');

		// Set a template
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('config');
	}

	/**
	 * @brief Poll Results
	 */
	function dispPollAdminResult()
	{
		// Popup layout
		$this->setLayoutFile("popup_layout");
		// Draw results
		$args = new stdClass();
		$args->pollEX_srl = Context::get('pollEX_srl');
		$args->pollEX_index_srl = Context::get('pollEX_index_srl');

		$output = executeQuery('pollEX.getPoll', $args);
		if(!$output->data) return $this->stop('msg_pollEX_not_exists');

		$pollEX = new stdClass();
		$pollEX->stop_date = $output->data->stop_date;
		$pollEX->pollEX_count = $output->data->pollEX_count;

		$output = executeQuery('pollEX.getPollTitle', $args);
		if(!$output->data)
		{
			return $this->stop('msg_pollEX_not_exists');
		}

		$tmp = &$pollEX->pollEX[$args->pollEX_index_srl];
		$tmp->title = $output->data->title;
		$tmp->checkcount = $output->data->checkcount;
		$tmp->pollEX_count = $output->data->pollEX_count;

		$output = executeQuery('pollEX.getPollItem', $args);
		foreach($output->data as $val)
		{
			$tmp->item[] = $val;
		}

		$pollEX->pollEX_srl = $pollEX_srl;

		Context::set('pollEX',$pollEX);
		// Configure the skin and the colorset for the default configuration
		$oModuleModel = getModel('module');
		$pollEX_config = $oModuleModel->getModuleConfig('pollEX');
		Context::set('pollEX_config', $pollEX_config);

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('result');
	}
}
/* End of file pollEX.admin.view.php */
/* Location: ./modules/pollEX/pollEX.admin.view.php */
