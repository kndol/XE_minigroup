<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollEXAdminModel
 * @author KnDol (kndol@kndol.net)
 * @brief The admin model class of the pollEX module
 */
class pollEXAdminModel extends pollEX
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Get the list of pollEXs
	 */
	function getPollList($args)
	{
		$output = executeQueryArray('pollEX.getPollList', $args);
		if(!$output->toBool()) return $output;

		//if($output->data && !is_array($output->data)) $output->data = array($output->data);
		return $output;
	}

	/**
	 * @brief Get the list of pollEXs with member info
	 */
	function getPollListWithMember($args)
	{
		$output = executeQueryArray('pollEX.getPollListWithMember', $args);
		if(!$output->toBool()) return $output;

		return $output;
	}

	/**
	 * @brief Get the original pollEX
	 */
	function getPollAdminTarget()
	{
		$pollEX_srl = Context::get('pollEX_srl');
		$upload_target_srl = Context::get('upload_target_srl');

		$oDocumentModel = getModel('document');
		$oCommentModel = getModel('comment');

		$oDocument = $oDocumentModel->getDocument($upload_target_srl);

		if(!$oDocument->isExists()) $oComment = $oCommentModel->getComment($upload_target_srl);

		if($oComment && $oComment->isExists())
		{
			$this->add('document_srl', $oComment->get('document_srl'));
			$this->add('comment_srl', $oComment->get('comment_srl'));
		}
		elseif($oDocument->isExists())
		{
			$this->add('document_srl', $oDocument->get('document_srl'));
		}
		else return new Object(-1, 'msg_not_founded');
	}
}
/* End of file pollEX.admin.model.php */
/* Location: ./modules/pollEX/pollEX.admin.model.php */
