<?php
/**
 * @package		 ITPrism Plugins
 * @subpackage	 Social
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

        // @todo Remove it when the bug with '$article' fixed
        if(!isset($article) OR empty($article->id) OR !isset($this->params)) {
            return "";            
        }
        
        $content = $this->getContent($article);
        
        $place = $this->params->get('position');
        
        switch($place){
            
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
        
        $doc   = JFactory::getDocument();
        /* @var $doc JDocument */
        $docType = $doc->getType();
        
        // Check document type
        if(strcmp("html", $docType) != 0){
            return "";
        }
        
        $currentView = JRequest::getWord("view");
        
        // Check where we are able to show buttons?
        $showInArticles     = $this->params->get('showInArticles');
        $showInCategories   = $this->params->get('showInCategories');
        $showInFrontPage    = $this->params->get('showInFrontPage');
        
        /** Check for selected views, which will display the buttons. **/   
        /** If there is a specific set and do not match, return an empty string.**/
        if(!$showInArticles AND (strcmp("article", $currentView) == 0)){
            return "";
        }
        
        // Excluded Categories
        $excludedCats = $this->params->get('excludeCats');
        if(!empty($excludedCats)){
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        
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
            if(in_array($article->catid, $excludedCats) OR in_array($article->id, $excludeArticles)){
                return '';
            }
        }
        
        $iconType = $this->params->get("rss");
        
        // Get size
        $format  = explode("_", $iconType);
        $size    = explode("x", $format[1]);

        $bg  = JURI::root() . "plugins/content/itpsubscribe/images/bg" .$this->params->get("bg") .".png";
        $rss = JURI::root() . "plugins/content/itpsubscribe/images/rss" .$format[0].".png";
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