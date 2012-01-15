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
    
    public function __construct($subject, $params){
        
        parent::__construct($subject, $params);
    
    }
    
    public function onPrepareContent(&$article, &$params, $limitstart){
        
        $app =& JFactory::getApplication();
        /* @var $app JApplication */

        if($app->isAdmin()) {
            return;
        }
        
        $doc   = JFactory::getDocument();
        /* @var $doc JDocumentHtml */
        $docType = $doc->getType();
        
        // Check document type
        if(strcmp("html", $docType) != 0){
            return;
        }
        
        $currentOption = JRequest::getCmd("option");
        
        if(($currentOption != "com_content") OR !isset($article) OR empty($article->id) OR !isset($this->params)) {
            return;            
        }
        
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
        
        return true;
    }
    
    private function getContent(&$article){
        
        // Check where we are able to show buttons?
        $showInArticles     = $this->params->get('showInArticles');
        $showInCategories   = $this->params->get('showInCategories');
        $showInSections     = $this->params->get('showInSections');
        $showInFrontPage    = $this->params->get('showInFrontPage');
        
        $currentView = JRequest::getWord("view");
        
        /** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        if(!$showInArticles AND (strcmp("article", $currentView) == 0)){
            return "";
        }
        
        if(!$showInCategories AND (strcmp("category", $currentView) == 0)){
            return "";
        }
        
        if(!$showInSections AND (strcmp("section", $currentView) == 0)){
            return "";
        }
        
        if(!$showInFrontPage AND (strcmp("frontpage", $currentView) == 0)){
            return "";
        }
        
        // Excluded Categories
        $excludedCats = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        
        // Excluded Sections
        $excludeSections = $this->params->get('excludeSections');
        if(!empty($excludeSections)){
            $excludeSections = explode(',', $excludeSections);
        }
        settype($excludeSections, 'array');
        
        // Excluded Articles
        $excludeArticles = $this->params->get('excludeArticles');
        if(!empty($excludeArticles)){
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        
        // Included Articles
        $includedArticles = $this->params->get('includeArticles');
        if(!empty($includedArticles)){
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        
        // Check for included and exluded views
        if(!in_array($article->id, $includedArticles)) {
            // Check exluded places
            if(in_array($article->catid, $excludedCats) OR in_array($article->sectionid, $excludeSections) OR in_array($article->id, $excludeArticles)){
                return '';
            }
        }
        
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
        $doc   = JFactory::getDocument();
        /* @var $doc JDocumentHtml */
        
        $doc->addStyleDeclaration($css);
        
        $style  = JURI::root() . "plugins/content/itpsubscribe/style.css";
        $doc->addStyleSheet($style);
        
        /* Let's show the content */
        $rssLink = $this->params->get('rssLink');
        
        $form = "";
        
        // Feedburner code
        if($this->params->get("displayFeedburner")) {
$form .= '<form onsubmit="window.open(\'http://feedburner.google.com/fb/a/mailverify?uri=' .$this->params->get("feedburner_uri") .'\', \'popupwindow\', \'scrollbars=yes,width=550,height=520\');return true" target="popupwindow" method="post" action="http://feedburner.google.com/fb/a/mailverify">
    <input type="text" name="email" id="itps-em" />
    <input type="hidden" name="uri" value="' .$this->params->get("feedburner_uri") .'">
    <input type="hidden" value="en_US" name="loc">
    <input type="submit" value="Submit" class="button">
</form>';
        }
        
        // Custom code
        if($this->params->get("displayCustomForm")) {
            $form .= $this->params->get("customHtmlFormCode");
        }
        
        return '
        <div class="itp-subscribe">
            <div class="itp-subs"><a href="' . $rssLink . '" class="itp-rss-icon" /></a>
                <div id="itps-text" ><p>Subscribe via <a href="' . $rssLink . '"  />RSS</a> or Email:</p>
                '. $form .'
                </div>
            </div>
        </div>
        <div style="clear:both;">&nbsp;</div>
        ';
    
    }
}