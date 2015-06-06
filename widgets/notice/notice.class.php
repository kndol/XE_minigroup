<?php
    /**
     * @class notice
     * @author KnDol (kndol@kndol.net)
     * @brief widget to display notices
     * @version 0.1
     **/

    class notice extends WidgetHandler {

        /**
         * @brief Widget handler
         *
         * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
         * After generating the result, do not print but return it.
         **/

        function proc($args) {
            // Targets to sort
            if(!in_array($args->order_target, array('list_order','update_order'))) $args->order_target = 'list_order';
            // Sort order
            if(!in_array($args->order_type, array('asc','desc'))) $args->order_type = 'asc';
            // The number of displayed lists
            $args->list_count = (int)$args->list_count;
            if($args->list_count==0) $args->list_count = "";
            // Cut the length of the title
            if(!$args->subject_cut_size) $args->subject_cut_size = 0;
            // Cut the length of contents
            if(!$args->content_cut_size) $args->content_cut_size = 0;
            // Display time of the latest post
            if(!$args->duration_new) $args->duration_new = 24;
            // Viewing options
            $args->option_view_arr = explode(',',$args->option_view);
            // markup options
            if(!$args->markup_type) $args->markup_type = 'table';
            // Set variables used internally
            $oModuleModel = &getModel('module');
            $module_srls = $args->modules_info = $args->module_srls_info = $args->mid_lists = array();
            $site_module_info = Context::get('site_module_info');
            // Apply to all modules in the site if a target module is not specified
            if(!$args->module_srls){
                unset($obj);
                $obj->site_srl = (int)$site_module_info->site_srl;
                $output = executeQueryArray('widgets.notice.getMids', $obj);
                if($output->data) {
                    foreach($output->data as $key => $val) {
                        $args->modules_info[$val->mid] = $val;
                        $args->module_srls_info[$val->module_srl] = $val;
                        $args->mid_lists[$val->module_srl] = $val->mid;
                        $module_srls[] = $val->module_srl;
                    }
                }

                $args->modules_info = $oModuleModel->getMidList($obj);
            // Apply to the module only if a target module is specified
            } else {
                $obj->module_srls = $args->module_srls;
                $output = executeQueryArray('widgets.notice.getMids', $obj);
                if($output->data) {
                    foreach($output->data as $key => $val) {
                        $args->modules_info[$val->mid] = $val;
                        $args->module_srls_info[$val->module_srl] = $val;
                        $module_srls[] = $val->module_srl;
                    }
                    $idx = explode(',',$args->module_srls);
                    for($i=0,$c=count($idx);$i<$c;$i++) {
                        $srl = $idx[$i];
                        if(!$args->module_srls_info[$srl]) continue;
                        $args->mid_lists[$srl] = $args->module_srls_info[$srl]->mid;
                    }
                }
            }
            // Exit if no module is found
            if(!count($args->modules_info)) return Context::get('msg_not_founded');
            $args->module_srl = implode(',',$module_srls);

            /**
             * Method is separately made because content extraction, articles, and other elements exist
             **/
            // tab type
            if($args->tab_type == 'none' || $args->tab_type == '') {
				$notice_items = $this->_getDocumentItems($args);
            // If not a tab type
            } else {
                $notice_items = array();

                foreach($args->mid_lists as $module_srl => $mid){
                    $args->module_srl = $module_srl;
                    $notice_items[$module_srl] = $this->_getDocumentItems($args);
                }
            }

            $output = $this->_compile($args,$notice_items);
            return $output;
        }

        function _getDocumentItems($args) {
            // Get model object from the document module
            $oDocumentModel = &getModel('document');
            // Get categories
            $obj->module_srl = $args->module_srl;
            $output = executeQueryArray('widgets.notice.getCategories',$obj);
            if($output->toBool() && $output->data) {
                foreach($output->data as $key => $val) {
                    $category_lists[$val->module_srl][$val->category_srl] = $val;
                }
            }
            // Get a list of documents
            $obj->module_srl = $args->module_srl;
            $obj->category_srl = $args->category_srl;
            $obj->sort_index = $args->order_target;
            $obj->order_type = $args->order_type=="desc"?"asc":"desc";
            $obj->list_count = $args->list_count;
			$obj->statusList = array('PUBLIC');
			if ($args->content_type == "notices") {
				$obj->is_notice = "Y";
	            $output = executeQueryArray('widgets.notice.getNotices', $obj);
			}
			else {
	            $output = executeQueryArray('widgets.notice.getNewestDocuments', $obj);
			}
            if(!$output->toBool() || !$output->data) return;
            // If the result exists, make each document as an object
            $notice_items = array();
//            $first_thumbnail_idx = -1;
            if(count($output->data)) {
                foreach($output->data as $key => $attribute) {
                    $oDocument = new documentItem();
                    $oDocument->setAttribute($attribute, false);
                    $GLOBALS['XE_DOCUMENT_LIST'][$oDocument->document_srl] = $oDocument;
                    $document_srls[] = $oDocument->document_srl;
                }
                $oDocumentModel->setToAllDocumentExtraVars();

                for($i=0,$c=count($document_srls);$i<$c;$i++) {
                    $oDocument = $GLOBALS['XE_DOCUMENT_LIST'][$document_srls[$i]];
                    $document_srl = $oDocument->document_srl;
                    $module_srl = $oDocument->get('module_srl');
                    $category_srl = $oDocument->get('category_srl');
//                    $thumbnail = $oDocument->getThumbnail($args->thumbnail_width,$args->thumbnail_height,$args->thumbnail_type);

                    $notice_item = new noticeItem( $args->module_srls_info[$module_srl]->browser_title );
                    $notice_item->setModuleSrl($module_srl);
                    $notice_item->adds($oDocument->getObjectVars());
                    $notice_item->add('original_content', $oDocument->get('content'));
                    $notice_item->setTitle($oDocument->getTitle());
                    $notice_item->setCategory( $category_lists[$module_srl][$category_srl]->title );
                    $notice_item->setDomain( $args->module_srls_info[$module_srl]->domain );
                    $notice_item->setContent( $oDocument->get('content') );
                    $notice_item->setLink( getSiteUrl($domain,'','document_srl',$document_srl) );
                    $notice_item->setSrl($document_srl);
//                    $notice_item->setThumbnail($thumbnail);
                    $notice_item->setExtraImages($oDocument->printExtraImages($args->duration_new * 60 * 60));
                    $notice_item->add('mid', $args->mid_lists[$module_srl]);
//                    if($first_thumbnail_idx==-1 && $thumbnail) $first_thumbnail_idx = $i;
                    $notice_items[] = $notice_item;
                }

//                $notice_items[0]->setFirstThumbnailIdx($first_thumbnail_idx);
            }

			$oSecurity = new Security($notice_items);
			$oSecurity->encodeHTML('..variables.user_name', '..variables.nick_name');

            return $notice_items;
        }

        function _compile($args,$notice_items){
            $oTemplate = &TemplateHandler::getInstance();

            // Set variables for widget
            $widget_info->modules_info = $args->modules_info;
            $widget_info->option_view_arr = $args->option_view_arr;
            $widget_info->list_count = (int)$args->list_count>0 ? (int)$args->list_count : 0;
            $widget_info->subject_cut_size = (int)$args->subject_cut_size>0 ? (int)$args->subject_cut_size : 0;
            $widget_info->content_cut_size = (int)$args->content_cut_size>0 ? (int)$args->subject_cut_size : 0;

            $widget_info->duration_new = (int)$args->duration_new*60*60;
            $widget_info->mid_lists = $args->mid_lists;

            $widget_info->show_browser_title = $args->show_browser_title;
            $widget_info->show_category = $args->show_category;
//            $widget_info->show_comment_count = $args->show_comment_count;
//            $widget_info->show_trackback_count = $args->show_trackback_count;
            $widget_info->show_icon = $args->show_icon;

            $widget_info->tab_type = $args->tab_type;
            $widget_info->show_random = $args->show_random;
            $widget_info->bg_image = $args->bg_image;
            $widget_info->bg_width = $args->bg_width ? $args->bg_width : 0;
            $widget_info->bg_height = $args->bg_height ? $args->bg_height : 0;
            
            $isMobile = Mobile::isFromMobilePhone();
            $widget_info->min_width = ($isMobile) ? "1" : ($args->min_width ? $args->min_width : "200px");
            $widget_info->max_width = ($isMobile) ? "1" : ($args->max_width ? $args->max_width : "400px");
            $widget_info->min_height = ($isMobile) ? "1" : ($args->min_height ? $args->min_height : "200px");
            $widget_info->max_height = ($isMobile) ? "1" : ($args->max_height ? $args->max_height : "400px");
            $widget_info->widget_height = $args->widget_height ? $args->widget_height : "480px";
            $widget_info->use_closebutton = $args->use_closebutton;
            $widget_info->expiredays = (int)$args->expiredays;
            $widget_info->zindex = $args->zindex ? (int)$args->zindex : 50;
            $widget_info->title_size = $args->title_size ? $args->title_size : "16pt";
            $widget_info->content_size = $args->content_size ? $args->content_size : "10pt";
            $widget_info->resize_images = $args->resize_images;
            $widget_info->resize_videos = $args->resize_videos;
            
            // If it is a tab type, list up tab items and change key value(module_srl) to index 
            if($args->tab_type != 'none' && $args->tab_type) {
                $tab = array();
                foreach($args->mid_lists as $module_srl => $mid){
                    if(!is_array($notice_items[$module_srl]) || !count($notice_items[$module_srl])) continue;

                    unset($tab_item);
                    $tab_item->title = $notice_items[$module_srl][0]->getBrowserTitle();
                    $tab_item->notice_items = $notice_items[$module_srl];
                    $tab_item->domain = $notice_items[$module_srl][0]->getDomain();
                    $tab_item->url = getSiteUrl($tab_item->domain, '','mid',$mid);
                    $tab[] = $tab_item;
                }
                $widget_info->tab = $tab;
            } else {
                $widget_info->notice_items = $notice_items;
            }
            unset($args->option_view_arr);
            unset($args->modules_info);

            $widget_info->skin_path = $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);

            Context::set('colorset', $args->colorset);
            Context::set('widget_info', $widget_info);

            return $oTemplate->compile($tpl_path, "notices");
        }
    }

    class noticeItem extends Object {

        var $browser_title = null;
//        var $has_first_thumbnail_idx = false;
//        var $first_thumbnail_idx = null;
        var $contents_link = null;
        var $domain = null;

        function noticeItem($browser_title=''){
            $this->browser_title = $browser_title;
        }
/*
        function setContentsLink($link){
            $this->contents_link = $link;
        }
        function setFirstThumbnailIdx($first_thumbnail_idx){
            if(is_null($this->first_thumbnail) && $first_thumbnail_idx>-1) {
                $this->has_first_thumbnail_idx = true;
                $this->first_thumbnail_idx= $first_thumbnail_idx;
            }
        }
*/
        function setExtraImages($extra_images){
            $this->add('extra_images',$extra_images);
        }
        function setDomain($domain) {
            static $default_domain = null;
            if(!$domain) {
                if(is_null($default_domain)) $default_domain = Context::getDefaultUrl();
                $domain = $default_domain;
            }
            $this->domain = $domain;
        }
        function setLink($url){
            $this->add('url',$url);
        }
        function setSrl($srl){
        	$this->add('srl',$srl);
        }
        function setModuleSrl($module_srl){
            $this->add('module_srl',$module_srl);
        }
        function setTitle($title){
            $this->add('title',$title);
        }
/*
        function setThumbnail($thumbnail){
            $this->add('thumbnail',$thumbnail);
        }
*/
        function setContent($content) {
            $this->add('content',$content);
        }
        function setRegdate($regdate){
            $this->add('regdate',$regdate);
        }
        function setNickName($nick_name){
            $this->add('nick_name',$nick_name);
        }
        function setUserName($user_name){
            $this->add('user_name',$user_name);
        }
/*
        // Save author's homepage url. By misol
        function setAuthorSite($site_url){
            $this->add('author_site',$site_url);
        }
*/
        function setCategory($category){
            $this->add('category',$category);
        }
        function getBrowserTitle(){
            return $this->browser_title;
        }
        function getDomain() {
            return $this->domain;
        }
/*
        function getContentsLink(){
            return $this->contents_link;
        }
*/
        function getLink(){
            return $this->get('url');
        }
        function getSrl(){
            return $this->get('srl');
        }
        function getModuleSrl(){
            return $this->get('module_srl');
        }
        function getTitle($cut_size = 0, $tail='...'){
            $title = strip_tags($this->get('title'));

            if($cut_size && $cut_size > 0) $title = cut_str($title, $cut_size, $tail);

            $attrs = array();
            if($this->get('title_bold') == 'Y') $attrs[] = 'font-weight:bold';
            if($this->get('title_color') && $this->get('title_color') != 'N') $attrs[] = 'color:#'.$this->get('title_color');

            if(count($attrs)) $title = sprintf("<span style=\"%s\">%s</span>", implode(';', $attrs), htmlspecialchars($title));
//            if(count($attrs)) $title = sprintf("<span style=\"%s\">%s</span>", implode(';', $attrs), $title);

            return $title;
        }
        function getContent($cut_size = 0, $tail = '...'){
            $content = $this->get('content');
            // Replace < , >, "
            $content = str_replace(array('&lt;','&gt;','&quot;','&nbsp;'), array('<','>','"',' '), $content);

            // Truncate string
            if ($cut_size && $cut_size > 0) $content = trim(cut_str($content, $cut_size, $tail));

            $content = preg_replace('/<p> <\/p>/is', '<p>&nbsp;</p>', $content);
            $content = preg_replace('/  /is', ' &nbsp;', $content);

			return $content;
        }
        function getCategory(){
            return $this->get('category');
        }
        function getNickName(){
            return $this->get('nick_name');
        }
        function getUserName(){
            return $this->get('user_name');
        }
/*
        function getAuthorSite(){
            return $this->get('author_site');
        }
        function getCommentCount(){
            $comment_count = $this->get('comment_count');
            return $comment_count>0 ? $comment_count : '';
        }
        function getTrackbackCount(){
            $trackback_count = $this->get('trackback_count');
            return $trackback_count>0 ? $trackback_count : '';
        }
*/
        function getRegdate($format = 'Y.m.d H:i:s') {
            return zdate($this->get('regdate'), $format);
        }
        function printExtraImages() {
            return $this->get('extra_images');
        }
/*
        function haveFirstThumbnail() {
            return $this->has_first_thumbnail_idx;
        }
        function getThumbnail(){
            return $this->get('thumbnail');
        }
*/
        function getMemberSrl() {
            return $this->get('member_srl');
        }
    }
?>