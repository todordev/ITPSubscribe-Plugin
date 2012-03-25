<?php
/**
 * @package		 ITPrism Plugins
 * @subpackage	 ITPSubscribe
 * @copyright    Copyright (C) 2010 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * ITPSubscribe is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * ITPSubscribe Plugin
 *
 * @package		ITPrism Plugins
 * @subpackage	Social
 */
class plgContentITPSubscribe extends JPlugin {
    
	private $currentView    = "";
    private $currentTask    = "";
    private $currentOption  = "";
    
    public function __construct($subject, $params){
        
        parent::__construct($subject, $params);
    
        $app =& JFactory::getApplication();
        /* @var $app JApplication */

        if($app->isAdmin()) {
            return;
        }
        
        $this->currentView    = JRequest::getCmd("view");
        $this->currentTask    = JRequest::getCmd("task");
        $this->currentOption  = JRequest::getCmd("option");
        
    }
    
    public function onPrepareContent(&$article, &$params, $limitstart){
        
    if (!$article OR !isset($this->params) ) { return; };     
        
        $app =& JFactory::getApplication();
        /** @var $app JApplication **/

        if($app->isAdmin()) {
            return;
        }
        
        $doc   = JFactory::getDocument();
        /** @var $doc JDocumentHtml **/
        $docType = $doc->getType();
        
        // Check document type
        if(strcmp("html", $docType) != 0){
            return;
        }
        
        // Generate context value
        $context = $this->currentOption.".".$this->currentView;
        
        switch($this->currentOption) {
            case "com_content":
                if($this->isContentRestricted($article, $context)) {
                    return;
                }
                break;    
                
            case "com_k2":
                if($this->isK2Restricted($article, $context)) {
                    return;
                }
                break;
                
            case "com_virtuemart":
                if($this->isVirtuemartRestricted($article, $context)) {
                    return;
                }
                break;

            case "com_jevents":
                if($this->isJEventsRestricted($article, $context)) {
                    return;
                }
                break;
                
            case "com_easyblog":
                
                if($this->isEasyBlogRestricted($article, $context)) {
                    return;
                }
                break;
                    
            default:
                return;
                break;   
        }
        
        if($this->params->get("loadCss")) {
            $doc->addStyleSheet(JURI::root() . "plugins/content/itpsubscribe/style.css");
        }
        
        /*** Loading language file ***/
        JPlugin::loadLanguage('plg_itpsubscribe', JPATH_ADMINISTRATOR);
        
        $content = $this->getContent($article);
        $position = $this->params->get('position');
        
        switch($position){
            
            case 1:
                $article->text = $content . $article->text;
                break;
            
            case 2:
                $article->text = $article->text . $content;
                break;
            
            default:
                $article->text = $content . $article->text . $content;
                break;
        }
        
        return;
    }
    
	/**
     * 
     * Checks allowed articles, exluded categories/articles,... for component COM_CONTENT
     * @param object $article
     * @param string $context
     */
    private function isContentRestricted(&$article, $context) {
        
        // Check for currect context
        if(strpos($context, "com_content") === false) {
           return true;
        }
        
    	/** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        
        // Display form only in the view "article"
        if(strcmp("article", $this->currentView) != 0){
            return true;
        }
        
        $showInArticles     = $this->params->get('showInArticles');
        if(!$showInArticles AND (strcmp("article", $this->currentView) == 0)){
            return true;
        }
        
        $excludeArticles = $this->params->get('excludeArticles');
        if(!empty($excludeArticles)){
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        JArrayHelper::toInteger($excludeArticles);
        
        // Exluded categories
        $excludedCats           = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        JArrayHelper::toInteger($excludedCats);
        
        // Included Articles
        $includedArticles = $this->params->get('includeArticles');
        if(!empty($includedArticles)){
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        JArrayHelper::toInteger($includedArticles);
        
        if(!in_array($article->id, $includedArticles)) {
            // Check exluded articles
            if(in_array($article->id, $excludeArticles) OR in_array($article->catid, $excludedCats)){
                return true;
            }
        }
        
        return false;
    }
    
    private function isK2Restricted(&$article, $context) {
        
        // Check for currect context
        if(strpos($context, "com_k2") === false) {
           return true;
        }
        
        // Display form only in the view "Item"
    	if(strcmp("item", $this->currentView) != 0){
            return true;
        }
        
        // Restrict item view
        $displayInArticles     = $this->params->get('k2DisplayInArticles', 0);
        if(!$displayInArticles AND (strcmp("item", $this->currentView) == 0)){
            return true;
        }
        
    }
    
    /**
     * 
     * Do verifications for JEvent extension
     * @param jIcalEventRepeat $article
     * @param string $context
     */
    private function isJEventsRestricted(&$article, $context) {
        
        // Display buttons only in the description
        if (!is_a($article, "jIcalEventRepeat")) { 
            return true; 
        };
        
        // Check for currect context
        if(strpos($context, "com_jevents") === false) {
           return true;
        }
        
    	// Display form only in the task "icalrepeat.detail"
    	if(strcmp("icalrepeat.detail", $this->currentTask) != 0){
            return true;
        }
        
        $displayInEvents     = $this->params->get('jeDisplayInEvents', 0);
        if(!$displayInEvents AND (strcmp("icalrepeat.detail", $this->currentTask) == 0)){
            return true;
        }
        
    }
    
    private function isVirtuemartRestricted(&$article, $context) {
            
        // Check for currect context
        if(strpos($context, "com_virtuemart") === false) {
           return true;
        }
        
   		// Display form only in the view "productdetails"
    	if(strcmp("productdetails", $this->currentView) != 0){
            return true;
        }
        
        $displayInDetails     = $this->params->get('vmDisplayInDetails', 0);
        if(!$displayInDetails AND (strcmp("productdetails", $this->currentView) == 0)){
            return true;
        }
    }
    
	private function isEasyBlogRestricted(&$article, $context) {
            
        // Check for currect context
        if(strpos($context, "easyblog") === false) {
           return true;
        }
        
   		// Display form only in the view "productdetails"
    	if(strcmp("entry", $this->currentView) != 0){
            return true;
        }
        
        $displayInEntry     = $this->params->get('ebDisplayInEntry', 0);
        if(!$displayInEntry AND (strcmp("entry", $this->currentView) == 0)){
            return true;
        }
    }
    
    private function getContent(&$article){
        
    	$doc   = JFactory::getDocument();
        /** @var $doc JDocumentHtml **/
    	
        $iconType = $this->params->get("rss");
        
        // Get size
        $format  = explode("_", $iconType);
        $size    = explode("x", $format[1]);

        $bg  = JURI::root() . "plugins/content/itpsubscribe/bg" .$this->params->get("bg") .".png";
        $rss = JURI::root() . "plugins/content/itpsubscribe/rss" .$format[0].".png";
        $top = $this->params->get("top");
        $left = $this->params->get("left");
        $inputWidth = $this->params->get("iw");
            
        
        $css = ".itp-subs{
  background-image: url($bg);
  background-position: left;
  background-repeat: repeat-x;
  width: 95%;
  height: 40px;
  float:right;
  position:relative;
}

.itp-subscribe a.itp-rss-icon{
  background-image: url($rss);
  background-position: left;
  background-repeat: no-repeat;
  width: " . $size[0] . "px;
  height: " . $size[1] . "px;
  float:left;
  display:block;
  position: absolute;
  top: " . $top ."px;
  left: " . $left ."px;
  
}
";
        
        $doc->addStyleDeclaration($css);
        
        /* Let's show the content */
        $rssLink = $this->params->get('rssLink');
        
        $form = "";
        
        /*** Feedburner code ***/
        if($this->params->get("displayFeedburner")) {
$form .= '<form onsubmit="window.open(\'http://feedburner.google.com/fb/a/mailverify?uri=' .$this->params->get("feedburner_uri") .'\', \'popupwindow\', \'scrollbars=yes,width=550,height=520\');return true" target="popupwindow" method="post" action="http://feedburner.google.com/fb/a/mailverify">
    <input type="text" name="email" class="itps-em" />
    <input type="hidden" name="uri" value="' .$this->params->get("feedburner_uri") .'">
    <input type="hidden" value="'. $this->params->get("feedburner_l", "en_GB") .'" name="loc">
    <input type="submit" value="'. JText::_("PLG_ITPSUBSCRIBE_SUBMIT") . '" class="button">
</form>';
        }
        
        /*** Custom form code ***/
        if($this->params->get("displayCustomForm")) {
            $form .= $this->params->get("customHtmlFormCode");
        }
        
        /*** Generate the HTML code ***/
        return '
        <div class="itp-subscribe">
            <div class="itp-subs"><a href="' . $rssLink . '" class="itp-rss-icon" /></a>
                <div class="itps-text" >'. JText::sprintf("PLG_ITPSUBSCRIBE_SUBSCRIBE_VIA", $rssLink) . '
                '. $form .'
                </div>
            </div>
        </div>
        <div style="clear:both;">&nbsp;</div>
        ';
    
    }
}