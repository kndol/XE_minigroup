<?php
    /**
     * Json DefaultClass, MVC Parent Class.
     * 
     * @class  Json
     * @author KnDol (kndol@kndol.net)
     **/
    
    class json extends ModuleObject {
        
        /**
         * @brief Json Install Function
         **/
        function moduleInstall() {
            return new Object();
        }
        
        /**
         * @brief Json Update Check Function
         */
        function checkUpdate() {
			$oDB = &DB::getInstance();
			$oModuleModel = &getModel('module');
			if(!$oDB->isColumnExists("ncenterlite_user_set", "reg_id"))
				return true;
			if(!$oModuleModel->getTrigger('ncenterlite._insertNotify', 'json', 'controller', 'triggerAfterInsertNotify', 'after'))
				return true;
			return false;
        }
		
		/**
		 * @brief update module
		 **/
		function moduleUpdate() {
			$oDB = &DB::getInstance();
			if(!$oDB->isColumnExists("ncenterlite_user_set", "reg_id")) $oDB->addColumn("ncenterlite_user_set", "reg_id", "text");
			
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');
			if(!$oModuleModel->getTrigger('ncenterlite._insertNotify', 'json', 'controller', 'triggerAfterInsertNotify', 'after'))
				$oModuleController->insertTrigger('ncenterlite._insertNotify', 'json', 'controller', 'triggerAfterInsertNotify', 'after');
			return new Object(0, 'success_updated');
		}
    }

	/**
	 * sort parents before children
	 *
	 * @param array   $objects input objects with attributes 'id' and 'parent'
	 * @param array   $result  (optional, reference) internal
	 * @param integer $parent  (optional) internal
	 * @param integer $depth   (optional) internal
	 * @return array           output
	 */
	function parent_sort(array $objects, array &$result=array(), $parent=0, $depth=0) {
		foreach ($objects as $key => $object) {
			if ($object->parent_srl == $parent) {
				$object->depth = $depth;
				array_push($result, $object);
				unset($objects[$key]);
				parent_sort($objects, $result, $object->menu_item_srl, $depth + 1);
			}
		}
		return $result;
	}
	
	function swapElement(&$array, $a, $b) {
		$out = array_splice($array, $a, 1);
		array_splice($array, $b, 0, $out);
	}
?>