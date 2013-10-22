<?php
/**
 * @package		 ITPrism Plugins
 * @subpackage	 ITPSubscribe
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      http://www.gnu.org/copyleft/gpl.html GNU/GPL
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
    
    /**
	 * @param	string	The context of the content being passed to the plugin.
	 * @param	object	The article object.  Note $article->text is also available
	 * @param	object	The article params
	 * @param	int		The 'page' number
	 *
	 * @return	void
	 * @since	1.6
	 */
	public function onContentPrepare($context, &$article, &$params, $page = 0) {

	    if (!$article OR !isset($this->params)) { return; }
        
        // Check for correct trigger
        if(strcmp("on_content_prepare", $this->params->get("trigger_place")) != 0) {
            return;
        }
        
        // Generate content
        $content      = $this->processGenerating($context, $article, $params, $page = 0);
        
        // If there is no result, return void.
        if(is_null($content)) { return; }
        
        $position = $this->params->get('position');
        
        switch($position){
            
            case 1: // Top
                $article->text = $content . $article->text;
                break;
            
            case 2: // Bottom
                $article->text = $article->text . $content;
                break;
            
            default: // Both
                $article->text = $content . $article->text . $content;
                break;
        }
        
        return;
    }
    
    /**
     * Add the form into the article before content.
     *
     * @param	string	The context of the content being passed to the plugin.
     * @param	object	The article object.  Note $article->text is also available
     * @param	object	The article params
     * @param	int		The 'page' number
     *
     * @return string
     */
    public function onContentBeforeDisplay($context, &$article, &$params, $page = 0) {
    
        // Check for correct trigger
        if(strcmp("on_content_before_display", $this->params->get("trigger_place")) != 0) {
            return "";
        }
    
        // Generate content
        $content = $this->processGenerating($context, $article, $params, $page = 0);
    
        // If there is no result, return empty string.
        if(is_null($content)) { return ""; }
    
        return $content;
    }
    
    /**
     * Add the form into the article after content.
     *
     * @param	string	The context of the content being passed to the plugin.
     * @param	object	The article object.  Note $article->text is also available
     * @param	object	The article params
     * @param	int		The 'page' number
     *
     * @return string
     */
    public function onContentAfterDisplay($context, &$article, &$params, $page = 0) {
        
        // Check for correct trigger
        if(strcmp("on_content_after_display", $this->params->get("trigger_place")) != 0) {
            return "";
        }
    
        // Generate content
        $content = $this->processGenerating($context, $article, $params, $page = 0);
    
        // If there is no result, return empty string.
        if(is_null($content)) { return ""; }
    
        return $content;
    }
    
    /**
     * Execute the process of buttons generating.
     *
     * @param string    $context
     * @param object    $article
     * @param JRegistry $params
     * @param number    $page
     * @return NULL|string
     */
    private function processGenerating($context, &$article, &$params, $page = 0) {
    
        $app = JFactory::getApplication();
        /** @var $app JSite **/
    
        if($app->isAdmin()) {
            return null;
        }
    
        $doc     = JFactory::getDocument();
        /**  @var $doc JDocumentHtml **/
    
        // Check document type
        $docType = $doc->getType();
        if(strcmp("html", $docType) != 0){
            return null;
        }
    
        // Get request data
        $this->currentOption  = $app->input->getCmd("option");
        $this->currentView    = $app->input->getCmd("view");
        $this->currentTask    = $app->input->getCmd("task");
    
        if($this->isRestricted($article, $context, $params)) {
            return null;
        }
    
        if($this->params->get("loadCss")) {
            $doc->addStyleSheet(JURI::root() . "plugins/content/itpsubscribe/style.css");
        }
    
        // Load language file
        $this->loadLanguage();
    
        // Generate and return content
        return $this->getContent($article, $context);
    
    }
    
    private function isRestricted($article, $context, $params) {
    	
    	$result = false;
    	
    	switch($this->currentOption) {
            case "com_content":
                $result = $this->isContentRestricted($article, $context);
            break;
	                
            case "com_k2":
                $result = $this->isK2Restricted($article, $context, $params);
                break;
                
            case "com_virtuemart":
                $result = $this->isVirtuemartRestricted($article, $context);
                break;

            case "com_jevents":
                $result = $this->isJEventsRestricted($article, $context);
                break;

            case "com_easyblog":
                $result = $this->isEasyBlogRestricted($article, $context);
                break;
                
            case "com_vipportfolio":
                $result = $this->isVipPortfolioRestricted($article, $context);
                break;
                
            case "com_zoo":
                $result = $this->isZooRestricted($article, $context);
                break;    
                
             case "com_jshopping":
                $result = $this->isJoomShoppingRestricted($article, $context);
                break;  

            case "com_hikashop":
                $result = $this->isHikaShopRestricted($article, $context);
                break; 
                
            case "com_vipquotes":
                $result = $this->isVipQuotesRestricted($article, $context);
                break;
                
            default:
                $result = true;
                break;   
        }
        
        return $result;
        
    }
    
	/**
     * 
     * Checks allowed articles, exluded categories/articles,... for component COM_CONTENT
     * @param object $article
     * @param string $context
     */
    private function isContentRestricted(&$article, $context) {
        
        // Check for correct context
        if(( strpos($context, "com_content") === false ) OR empty($article->id)) {
           return true;
        }
        
    	/** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        $showInArticles     = $this->params->get('showInArticles');
        if(!$showInArticles AND (strcmp("article", $this->currentView) == 0)){
            return true;
        }
        
        // Will be displayed in view "categories"?
        $showInCategories   = $this->params->get('showInCategories');
        if(!$showInCategories AND (strcmp("category", $this->currentView) == 0)){
            return true;
        }
        
        // Will be displayed in view "featured"?
        $showInFeatured   = $this->params->get('showInFeatured');
        if(!$showInFeatured AND (strcmp("featured", $this->currentView) == 0)){
            return true;
        }
        
        // Exclude articles
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
    
	/**
     * 
     * This method does verification for K2 restrictions
     * @param jIcalEventRepeat $article
     * @param string $context
     */
    private function isK2Restricted(&$article, $context, $params) {
        
        // Check for correct context
        if(strpos($context, "com_k2") === false) {
           return true;
        }
        
        if($article instanceof TableK2Category){
            return true;
        }
        
        $displayInItemlist     = $this->params->get('k2DisplayInItemlist', 0);
        if(!$displayInItemlist AND (strcmp("itemlist", $this->currentView) == 0)){
            return true;
        }
        
        $displayInArticles     = $this->params->get('k2DisplayInArticles', 0);
        if(!$displayInArticles AND (strcmp("item", $this->currentView) == 0)){
            return true;
        }
        
        // Exclude articles
        $excludeArticles = $this->params->get('k2_exclude_articles');
        if(!empty($excludeArticles)){
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        JArrayHelper::toInteger($excludeArticles);
        
        // Exluded categories
        $excludedCats           = $this->params->get('k2_exclude_cats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        JArrayHelper::toInteger($excludedCats);
        
        // Included Articles
        $includedArticles = $this->params->get('k2_include_articles');
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
        
        // Check for correct context
        if(strpos($context, "com_jevents") === false) {
           return true;
        }

        // Display only in task 'icalrepeat.detail'
        if(strcmp("icalrepeat.detail", $this->currentTask) != 0) {
           return true;
        }
        
        $displayInEvents     = $this->params->get('jeDisplayInEvents', 0);
        if(!$displayInEvents){
            return true;
        }
        
        return false;
    }
    
    /**
     * 
     * This method does verification for VirtueMart restrictions
     * @param stdClass $article
     * @param string $context
     */
    private function isVirtuemartRestricted(&$article, $context) {
            
        // Check for correct context
        if(strpos($context, "com_virtuemart") === false) {
           return true;
        }
        
        // Display content only in the view "productdetails"
        if(strcmp("productdetails", $this->currentView) != 0){
            return true;
        }
        
        // Display content only in the view "productdetails"
        $displayInDetails     = $this->params->get('vmDisplayInDetails', 0);
        if(!$displayInDetails){
            return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_vipportfolio"
     * @param object $article
     * @param string $context
     */
	private function isVipPortfolioRestricted(&$article, $context) {

        // Check for correct context
        if(strpos($context, "com_vipportfolio") === false) {
           return true;
        }
        
	    // Verify the option for displaying in layout "lineal"
        $displayInLineal     = $this->params->get('vipportfolio_lineal', 0);
        if(!$displayInLineal){
            return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_zoo"
     * @param object $article
     * @param string $context
     */
	private function isZooRestricted(&$article, $context) {
	    
        // Check for correct context
        if(false === strpos($context, "com_zoo")) {
           return true;
        }
        
	    // Verify the option for displaying in view "item"
        $displayInItem     = $this->params->get('zoo_display', 0);
        if(!$displayInItem){
            return true;
        }
        
	    // Check for valid view or task
	    // I have check for task because if the user comes from view category, the current view is "null" and the current task is "item"
        if( (strcmp("item", $this->currentView) != 0 ) AND (strcmp("item", $this->currentTask) != 0 )){
            return true;
        }
        
        // A little hack used to prevent multiple displaying of buttons, becaues
        // if there is more than one textares the buttons will be displayed in everyone.
        static $numbers = 0;
        if($numbers == 1) {
            return true;
        }
        $numbers = 1;
        
        return false;
    }
    
    /**
     * 
     * It's a method that verify restriction for the component "com_easyblog"
     * @param object $article
     * @param string $context
     */
	private function isEasyBlogRestricted(&$article, $context) {
        $allowedViews = array("categories", "entry", "latest", "tags");   
        // Check for correct context
        if(strpos($context, "easyblog") === false) {
           return true;
        }
        
        // Only put buttons in allowed views
        if(!in_array($this->currentView, $allowedViews)) {
        	return true;
        }
        
   		// Verify the option for displaying in view "categories"
        $displayInCategories     = $this->params->get('ebDisplayInCategories', 0);
        if(!$displayInCategories AND (strcmp("categories", $this->currentView) == 0)){
            return true;
        }
        
   		// Verify the option for displaying in view "latest"
        $displayInLatest     = $this->params->get('ebDisplayInLatest', 0);
        if(!$displayInLatest AND (strcmp("latest", $this->currentView) == 0)){
            return true;
        }
        
		// Verify the option for displaying in view "entry"
        $displayInEntry     = $this->params->get('ebDisplayInEntry', 0);
        if(!$displayInEntry AND (strcmp("entry", $this->currentView) == 0)){
            return true;
        }
        
	    // Verify the option for displaying in view "tags"
        $displayInTags     = $this->params->get('ebDisplayInTags', 0);
        if(!$displayInTags AND (strcmp("tags", $this->currentView) == 0)){
            return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_joomshopping"
     * @param object $article
     * @param string $context
     */
	private function isJoomShoppingRestricted(&$article, $context) {
        
        // Check for correct context
        if(false === strpos($context, "com_content.article")) {
           return true;
        }
        
	    // Check for enabled functionality for that extension
        $displayInDetails     = $this->params->get('joomshopping_display', 0);
        if(!$displayInDetails OR !isset($article->product_id)){
            return true;
        }
        
        return false;
    }
    
	/**
     * 
     * It's a method that verify restriction for the component "com_hikashop"
     * @param object $article
     * @param string $context
     */
	private function isHikaShopRestricted(&$article, $context) {
	    
        // Check for correct context
        if(false === strpos($context, "text")) {
           return true;
        }
        
	    // Display content only in the view "product"
        if(strcmp("product", $this->currentView) != 0){
            return true;
        }
        
	    // Check for enabled functionality for that extension
        $displayInDetails     = $this->params->get('hikashop_display', 0);
        if(!$displayInDetails){
            return true;
        }
        
        return false;
    }
    
    /**
     * Do verification for Vip Quotes extension. Is it restricted?
     *
     * @param ojbect $article
     * @param string $context
     */
    private function isVipQuotesRestricted(&$article, $context) {
    
        // Check for correct context
        if(strpos($context, "com_vipquotes") === false) {
            return true;
        }
    
        // Display only in view 'quote'
        $allowedViews = array("author", "quote");
        if(!in_array($this->currentView, $allowedViews)) {
            return true;
        }
    
        $displayOnViewQuote     = $this->params->get('vipquotes_display_quote', 0);
        if(!$displayOnViewQuote){
            return true;
        }
    
        $displayOnViewAuthor     = $this->params->get('vipquotes_display_author', 0);
        if(!$displayOnViewAuthor){
            return true;
        }
    
        return false;
    }
    
    private function getContent(&$article){
        
        $doc   = JFactory::getDocument();
        /** @var $doc JDocumentHtml **/
        
        $iconType = $this->params->get("rss");
        
        // Get size
        $format  = explode("_", $iconType);
        $size    = explode("x", $format[1]);

        $bg      = JURI::root() . "plugins/content/itpsubscribe/images/bg" .$this->params->get("bg") .".png";
        $rss     = JURI::root() . "plugins/content/itpsubscribe/images/rss" .$format[0].".png";
        $top     = $this->params->get("top");
        $left    = $this->params->get("left");
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
        
        // Let's display the content
        $rssLink = $this->params->get('rssLink');
        
        $form = "";
        
        // Feedburner code
        if($this->params->get("displayFeedburner")) {
$form .= '<form onsubmit="window.open(\'http://feedburner.google.com/fb/a/mailverify?uri=' .$this->params->get("feedburner_uri") .'\', \'popupwindow\', \'scrollbars=yes,width=550,height=520\');return true" target="popupwindow" method="post" action="http://feedburner.google.com/fb/a/mailverify">
    <input type="text" name="email" class="itps-em" />
    <input type="hidden" name="uri" value="' .$this->params->get("feedburner_uri") .'" />
    <input type="hidden" value="'. $this->params->get("feedburner_l", "en_GB") .'" name="loc" />
    <input type="submit" value="'. JText::_("PLG_CONTENT_ITPSUBSCRIBE_SUBMIT") . '" class="button" />
</form>';
        }
        
        // Custom form code 
        if($this->params->get("displayCustomForm")) {
            $form .= $this->params->get("customHtmlFormCode");
        }
        
        // Generate the HTML code 
        return '
        <div class="itp-subscribe">
            <div class="itp-subs"><a href="' . $rssLink . '" class="itp-rss-icon" ></a>
                <div class="itps-text" >'. JText::sprintf("PLG_CONTENT_ITPSUBSCRIBE_SUBSCRIBE_VIA", $rssLink) . '
                	'. $form .'
                </div>
            </div>
        </div>
        <div style="clear:both;">&nbsp;</div>
        ';
    
    }
}