<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollexAdminController
 * @author KnDol (kndol@kndol.net)
 * @brief The admin controller class of the pollex module
 */
class pollexAdminController extends pollex
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
	function procPollexAdminInsertConfig()
	{
		$config = new stdClass;
		$config->skin = Context::get('skin');
		$config->colorset = Context::get('colorset');

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('pollex', $config);

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollexAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the polls selected in the administrator's page
	 */
	function procPollexAdminDeleteChecked()
	{
		// Display an error no post is selected
		$cart = Context::get('cart');

		if(is_array($cart)) $poll_srl_list = $cart;
		else $poll_srl_list= explode('|@|', $cart);

		$poll_count = count($poll_srl_list);
		if(!$poll_count) return $this->stop('msg_cart_is_null');
		// Delete the post
		for($i=0;$i<$poll_count;$i++)
		{
			$poll_index_srl = trim($poll_srl_list[$i]);
			if(!$poll_index_srl) continue;

			$output = $this->deletePollexTitle($poll_index_srl, true);
			if(!$output->toBool()) return $output;
		}

		$this->setMessage( sprintf(Context::getLang('msg_checked_poll_is_deleted'), $poll_count) );

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPollexAdminList');
		$this->setRedirectUrl($returnUrl);
	}

	function procPollexAdminAddCart()
	{
		$poll_index_srl = (int)Context::get('poll_index_srl');

		$oPollAdminModel = getAdminModel('pollex');
		//$columnList = array('comment_srl');
		$args = new stdClass;
		$args->pollexIndexSrlList = array($poll_index_srl);
		$args->list_count = 100;

		$output = $oPollAdminModel->getPollexList($args);

		if(is_array($output->data))
		{
			foreach($output->data AS $key=>$value)
			{
				if($_SESSION['pollex_management'][$value->poll_index_srl]) unset($_SESSION['pollex_management'][$value->poll_index_srl]);
				else $_SESSION['pollex_management'][$value->poll_index_srl] = true;
			}
		}
	}

	/**
	 * @brief Delete the pollex (when several questions are registered in one pollex, delete this question)
	 */
	function deletePollexTitle($poll_index_srl) 
	{
		$args = new stdClass;
		$dargs = new stdClass;

		$args->poll_index_srl = $poll_index_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = executeQueryArray('pollex.getPollexByDeletePollTitle', $args);
		if($output->toBool() && $output->data && $output->data[0]->count == 1)
		{
			$dargs->poll_srl = $output->data[0]->poll_srl;
		}

		$output = $oDB->executeQuery('pollex.deletePollexTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollex.deletePollexItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		if($dargs->poll_srl)
		{
			$output = executeQuery('pollex.deletePollex', $dargs);
			if(!$output)
			{
				$oDB->rollback();
				return $output;
			}

			$output = executeQuery('pollex.deletePollexLog', $dargs);
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
	 * @brief Delete the pollex (delete the entire pollex)
	 */
	function deletePollex($poll_srl)
	{
		$args = new stdClass;
		$args->poll_srl = $poll_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();

		$output = $oDB->executeQuery('pollex.deletePollex', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollex.deletePollexTitle', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$output = $oDB->executeQuery('pollex.deletePollexItem', $args);
		if(!$output)
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		return new Object();
	}
}
/* End of file pollex.admin.controller.php */
/* Location: ./modules/pollex/pollex.admin.controller.php */
