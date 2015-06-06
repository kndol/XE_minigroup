<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollEXAdminController
 * @author KnDol (kndol@kndol.net)
 * @brief The admin controller class of the pollEX module
 */
class pollEXAdminController extends pollEX
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Save the configurations
	 */
	function procPollAdminInsertConfig()
	{
		$config = new stdClass;
		$config->skin = Context::get('skin');
		$config->colorset = Context::get('colorset');

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('pollEX', $config);

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the pollEXs selected in the administrator's page
	 */
	function procPollAdminDeleteChecked()
	{
		// Display an error no post is selected
		$cart = Context::get('cart');

		if(is_array($cart)) $pollEX_srl_list = $cart;
		else $pollEX_srl_list= explode('|@|', $cart);

		$pollEX_count = count($pollEX_srl_list);
		if(!$pollEX_count) return $this->stop('msg_cart_is_null');
		// Delete the post
		for($i=0;$i<$pollEX_count;$i++)
		{
			$pollEX_index_srl = trim($pollEX_srl_list[$i]);
			if(!$pollEX_index_srl) continue;

			$output = $this->deletePollTitle($pollEX_index_srl, true);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage( sprintf(Context::getLang('msg_checked_pollEX_is_deleted'), $pollEX_count) );

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollAdminList');
		$this->setRedirectUrl($returnUrl);
	}

	function procPollAdminAddCart()
	{
		$pollEX_index_srl = (int)Context::get('pollEX_index_srl');

		$oPollAdminModel = getAdminModel('pollEX');
		//$columnList = array('comment_srl');
		$args = new stdClass;
		$args->pollEXIndexSrlList = array($pollEX_index_srl);
		$args->list_count = 100;

		$output = $oPollAdminModel->getPollList($args);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$value)
			{
				if($_SESSION['pollEX_management'][$value->pollEX_index_srl]) unset($_SESSION['pollEX_management'][$value->pollEX_index_srl]);
				else $_SESSION['pollEX_management'][$value->pollEX_index_srl] = true;
			}
		}
	}

	/**
	 * @brief Delete the pollEX (when several questions are registered in one pollEX, delete this question)
	 */
	function deletePollTitle($pollEX_index_srl) 
	{
		$args = new stdClass;
		$dargs = new stdClass;

		$args->pollEX_index_srl = $pollEX_index_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = executeQueryArray('pollEX.getPollByDeletePollTitle', $args);
		if($output->toBool() && $output->data && $output->data[0]->count == 1)
		{
			$dargs->pollEX_srl = $output->data[0]->pollEX_srl;
		}

		$output = $oDB->executeQuery('pollEX.deletePollTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollEX.deletePollItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		if($dargs->pollEX_srl)
		{
			$output = executeQuery('pollEX.deletePoll', $dargs);
			if(!$output)
			{
				$oDB->rollback();
				return $output;
			}

			$output = executeQuery('pollEX.deletePollLog', $dargs);
			if(!$output)
			{
				$oDB->rollback();
				return $output;
			}
		}
		$oDB->commit();

		return new Object();
	}

	/**
	 * @brief Delete the pollEX (delete the entire pollEX)
	 */
	function deletePoll($pollEX_srl)
	{
		$args = new stdClass;
		$args->pollEX_srl = $pollEX_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = $oDB->executeQuery('pollEX.deletePoll', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollEX.deletePollTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollEX.deletePollItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		return new Object();
	}
}
/* End of file pollEX.admin.controller.php */
/* Location: ./modules/pollEX/pollEX.admin.controller.php */
