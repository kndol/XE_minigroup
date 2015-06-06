<?php
/**
 * @class  minigroup
 * @author (kndol@kndol.net)
 * @brief  minigroup package
 **/

class minigroup extends ModuleObject
{
	private $triggers = array(
		array('name' =>'display',
			'module'=>'minigroup',
			'type' => 'controller',
			'func' => 'triggerMemberMenu',
			'position'=>'before'),
		array('name' =>'moduleHandler.proc',
			'module'=>'minigroup',
			'type' => 'controller',
			'func' => 'triggerApplyLayout',
			'position'=>'after'),
		array('name' =>'moduleHandler.init',
			'module'=>'minigroup',
			'type' => 'controller',
			'func' => 'triggerApplyMLayout',
			'position'=>'after')
	);

	/**
	 * constructor
	 *
	 * @return void
	 */
	function minigroup()
	{
	}

	/**
	 * @brief 설치시 추가 작업이 필요할 시 구현
	 **/
	function moduleInstall()
	{
		$oModuleController = &getController('module');
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

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
		$oDB->createTableByXmlFile($this->module_path.'schemas/minigroups.xml');

		return new Object();
	}


	/**
	 * @brief 설치가 이상이 없는지 체크
	 **/
	function checkUpdate()
	{
		$oModuleModel = &getModel('module');
		$oDB = &DB::getInstance();

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
		if(!$oDB->isTableExists("minigroups")) return true;

		return false;
	}

	/**
	 * @brief 업데이트 실행
	 **/
	function moduleUpdate()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		$oDB = &DB::getInstance();

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
		if(!$oDB->isTableExists("minigroups")) $oDB->createTableByXmlFile($this->module_path.'schemas/minigroups.xml');

		return new Object(0, 'success_updated');
	}

	/**
	 * @brief 삭제시 동작
	 */
	function moduleUninstall()
	{
		$oModuleModel = &getModel('module');
		$oModuleController = &getController('module');
		$oDB = &DB::getInstance();

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
		if($oDB->isTableExists("minigroups"))
		{
			$oDB->DropTable("minigroups");
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
/* End of file minigroup.class.php */
/* Location: ./modules/minigroup/minigroup.class.php */