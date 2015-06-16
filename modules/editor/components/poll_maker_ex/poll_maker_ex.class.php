<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  poll_maker_ex
 * @author KnDol (kndol@kndol.net)
 * @brief this provides poll for XE.
 */
class poll_maker_ex extends EditorHandler
{
	// editor_sequence from the editor must attend mandatory wearing ....
	var $editor_sequence = 0;
	var $component_path = '';

	/**
	 * @brief editor_sequence and components out of the path
	 */
	function poll_maker_ex($editor_sequence, $component_path)
	{
		$this->editor_sequence = $editor_sequence;
		$this->component_path = $component_path;
	}

	/**
	 * @brief popup window to display in popup window request is to add content
	 */
	function getPopupContent()
	{
		// Wanted Skins survey
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins(_XE_PATH_ . 'modules/pollex/');
		Context::set('skin_list', $skin_list);
		// Pre-compiled source code to compile template return to
		$tpl_path = $this->component_path.'tpl';
		$tpl_file = 'popup.html';

		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}

	/**
	 * @brief Editor of the components separately if you use a unique code to the html code for a method to change
	 *
	 * Images and multimedia, seolmundeung unique code is required for the editor component added to its own code, and then
	 * DocumentModule:: transContent() of its components transHtml() method call to change the html code for your own
	 */
	function transHTML($xml_obj)
	{
		$poll_srl = $xml_obj->attrs->poll_srl;
		$skin = $xml_obj->attrs->skin;
		if(!$skin) $skin = 'default';

		preg_match('/width([^[:digit:]]+)([0-9]+)/i',$xml_obj->attrs->style,$matches);
		$width = $matches[2];
		if(!$width) $width = 400;
		$style = sprintf('width:%dpx', $width);
		// poll model object creation to come get it return html
		$oPollexModel = getModel('pollex');
		return $oPollexModel->getPollexHtml($poll_srl, $style, $skin);
	}
}
/* End of file poll_maker_ex.class.php */
/* Location: ./modules/editor/components/poll_maker_ex/poll_maker_ex.class.php */
