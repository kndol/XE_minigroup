<?php
/* Copyright (C) KnDol <http://www.kndol.net> */
/**
 * @class  pollex
 * @author KnDol (kndol@kndol.net)
 * @brief The parent class of the pollex module
 */
class pollex extends ModuleObject
{
	private $triggers = array(
		array('name' =>'document.insertDocument',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerInsertDocumentPoll',
			'position'=>'after'),
		array('name' =>'comment.insertComment',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerInsertCommentPoll',
			'position'=>'after'),
		array('name' =>'document.updateDocument',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerUpdateDocumentPoll',
			'position'=>'after'),
		array('name' =>'comment.updateComment',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerUpdateCommentPoll',
			'position'=>'after'),
		array('name' =>'document.deleteDocument',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerDeleteDocumentPoll',
			'position'=>'after'),
		array('name' =>'comment.deleteComment',
			'module'=>'pollex',
			'type' => 'controller',
			'func' => 'triggerDeleteCommentPoll',
			'position'=>'after')
	);
	private $tables = array('pollex', 'pollex_title', 'pollex_item', 'pollex_log');
	
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	function moduleInstall()
	{
		// Register in the action forward (to use in administrator mode)
		$oModuleController = &getController('module');

		// Set the default skin
		$config = new stdClass;
		$config->skin = 'default';
		$config->colorset = 'normal';
		$oModuleController->insertModuleConfig('pollex', $config);
		// A pollex connection to add/update/delete posts/comments
        foreach($this->triggers as $trigger)
        {
            $oModuleController->insertTrigger(
                $trigger['name'],
                $trigger['module'],
                $trigger['type'],
                $trigger['func'],
                $trigger['position']
            );
        }

		return new Object();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		// A pollex connection to add/update/delete posts/comments
        foreach($this->triggers as $trigger)
        {
        	if (!$oModuleModel->getTrigger(
                $trigger['name'],
                $trigger['module'],
                $trigger['type'],
                $trigger['func'],
                $trigger['position']
            )) return true;
        }

		return false;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// A pollex connection to add/update/delete posts/comments
        foreach($this->triggers as $trigger)
        {
        	if (!$oModuleModel->getTrigger(
                $trigger['name'],
                $trigger['module'],
                $trigger['type'],
                $trigger['func'],
                $trigger['position']
            ))
            {
	            $oModuleController->insertTrigger(
	                $trigger['name'],
	                $trigger['module'],
	                $trigger['type'],
	                $trigger['func'],
	                $trigger['position']
	            );
	        }
        }

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief Uninstall pollex module
	 */
	function moduleUninstall()
	{
		$oDB = &DB::getInstance();
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');

        foreach($this->triggers as $trigger)
        {
            if ($oModuleModel->getTrigger(
            	$trigger['name'],
            	$trigger['module'],
            	$trigger['type'],
            	$trigger['func'],
            	$trigger['position']
            ))
            {
	            $oModuleController->deleteTrigger(
	                $trigger['name'],
	                $trigger['module'],
	                $trigger['type'],
	                $trigger['func'],
	                $trigger['position']
	            );
	        }
        }
        foreach($this->tables as $table)
        {
			if($oDB->isTableExists($table)) $oDB->DropTable($table);
		}
		return new Object();
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
	}
}
/* End of file pollex.class.php */
/* Location: ./modules/pollex/pollex.class.php */
