<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollexAdminModel
 * @author KnDol (kndol@kndol.net)
 * @brief The admin model class of the pollex module
 */
class pollexAdminModel extends pollex
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Get the list of polls
	 */
	function getPollexList($args)
	{
		$output = executeQueryArray('pollex.getPollexList', $args);
		if(!$output->toBool()) return $output;

		//if($output->data && !is_array($output->data)) $output->data = array($output->data);
		return $output;
	}

	/**
	 * @brief Get the list of polls with member info
	 */
	function getPollexListWithMember($args)
	{
		$output = executeQueryArray('pollex.getPollexListWithMember', $args);
		if(!$output->toBool()) return $output;

		return $output;
	}

	/**
	 * @brief Get the original pollex
	 */
	function getPollexAdminTarget()
	{
		$poll_srl = Context::get('poll_srl');
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
/* End of file pollex.admin.model.php */
/* Location: ./modules/pollex/pollex.admin.model.php */
