<?php
/**
 * @package         ITPrism Plugins
 * @subpackage      ITPSubscribe
 * @author          Todor Iliev
 * @copyright       Copyright (C) 2013 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license         http://www.gnu.org/copyleft/gpl.html GNU/GPL
 */

use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

// no direct access
defined('_JEXEC') or die;

/**
 * ITPSubscribe Plugin
 *
 * @package        ITPrism Plugins
 * @subpackage     Social
 */
class plgContentITPSubscribe extends JPlugin
{
    private $currentView = '';
    private $currentTask = '';
    private $currentOption = '';

    /**
     * @param    string   $context The context of the content being passed to the plugin.
     * @param    stdClass $article The article object.  Note $article->text is also available
     * @param    Registry $params  The article params
     * @param    int      $page    The 'page' number
     *
     * @return    void
     * @since    1.6
     */
    public function onContentPrepare($context, &$article, &$params, $page = 0)
    {
        if (!$article OR !isset($this->params)) {
            return;
        }

        // Check for correct trigger
        if (strcmp('on_content_prepare', $this->params->get('trigger_place')) !== 0) {
            return;
        }

        // Generate content
        $content = $this->processGenerating($context, $article, $params, $page = 0);

        // If there is no result, return void.
        if ($content === null) {
            return;
        }

        $position = $this->params->get('position');

        switch ($position) {

            case 1: // Top
                $article->text = $content . $article->text;
                break;

            case 2: // Bottom
                $article->text .= $content;
                break;

            default: // Both
                $article->text = $content . $article->text . $content;
                break;
        }
    }

    /**
     * Add the form into the article before content.
     *
     * @param    string   $context The context of the content being passed to the plugin.
     * @param    stdClass $article The article object.  Note $article->text is also available
     * @param    Registry $params  The article params
     * @param    int      $page    The 'page' number
     *
     * @return string
     */
    public function onContentBeforeDisplay($context, &$article, &$params, $page = 0)
    {
        // Check for correct trigger
        if (strcmp('on_content_before_display', $this->params->get('trigger_place')) !== 0) {
            return '';
        }

        // Generate content
        $content = $this->processGenerating($context, $article, $params, $page = 0);

        // If there is no result, return empty string.
        if ($content === null) {
            return '';
        }

        return $content;
    }

    /**
     * Add the form into the article after content.
     *
     * @param    string   $context The context of the content being passed to the plugin.
     * @param    stdClass $article The article object.  Note $article->text is also available
     * @param    Registry $params  The article params
     * @param    int      $page    The 'page' number
     *
     * @return string
     */
    public function onContentAfterDisplay($context, &$article, &$params, $page = 0)
    {
        // Check for correct trigger
        if (strcmp('on_content_after_display', $this->params->get('trigger_place')) !== 0) {
            return '';
        }

        // Generate content
        $content = $this->processGenerating($context, $article, $params, $page = 0);

        // If there is no result, return empty string.
        if ($content === null) {
            return '';
        }

        return $content;
    }

    /**
     * Execute the process of buttons generating.
     *
     * @param string   $context
     * @param stdClass $article
     * @param Registry $params
     * @param int      $page
     *
     * @return NULL|string
     */
    private function processGenerating($context, &$article, &$params, $page = 0)
    {
        $app = JFactory::getApplication();
        /** @var $app JApplicationSite */

        if ($app->isAdmin()) {
            return null;
        }

        $doc = JFactory::getDocument();
        /**  @var $doc JDocumentHtml */

        // Check document type
        $docType = $doc->getType();
        if (strcmp('html', $docType) !== 0) {
            return null;
        }

        // Get request data
        $this->currentOption = $app->input->getCmd('option');
        $this->currentView   = $app->input->getCmd('view');
        $this->currentTask   = $app->input->getCmd('task');

        if ($this->isRestricted($article, $context, $params)) {
            return null;
        }

        if ($this->params->get('loadCss')) {
            $doc->addStyleSheet(JUri::root() . 'plugins/content/itpsubscribe/style.css');
        }

        // Load language file
        $this->loadLanguage();

        // Generate and return content
        return $this->getContent();
    }

    private function isRestricted(&$article, $context, &$params)
    {
        switch ($this->currentOption) {
            case 'com_content':
                $result = $this->isContentRestricted($article, $context);
                break;

            case 'com_k2':
                $result = $this->isK2Restricted($article, $context, $params);
                break;

            case 'com_virtuemart':
                $result = $this->isVirtuemartRestricted($article, $context);
                break;

            case 'com_jevents':
                $result = $this->isJEventsRestricted($article, $context);
                break;

            case 'com_eshop':
                $result = $this->isEshopRestricted($article, $context);
                break;

            case 'com_jshopping':
                $result = $this->isJoomShoppingRestricted($article, $context);
                break;

            case 'com_hikashop':
                $result = $this->isHikaShopRestricted($article, $context);
                break;

            default:
                $result = true;
                break;
        }

        return $result;
    }

    /**
     * Checks allowed articles, exluded categories/articles,... for component COM_CONTENT
     *
     * @param stdClass $article
     * @param string   $context
     *
     * @return bool
     */
    private function isContentRestricted(&$article, $context)
    {
        // Check for correct context
        if ((strpos($context, 'com_content') === false) OR empty($article->id)) {
            return true;
        }

        /** Check for selected views, which will display the buttons. **/
        /** If there is a specific set and do not match, return an empty string.**/
        $showInArticles = $this->params->get('showInArticles');
        if (!$showInArticles AND (strcmp('article', $this->currentView) === 0)) {
            return true;
        }

        // Will be displayed in view 'categories'?
        $showInCategories = $this->params->get('showInCategories');
        if (!$showInCategories AND (strcmp('category', $this->currentView) === 0)) {
            return true;
        }

        // Will be displayed in view 'featured'?
        $showInFeatured = $this->params->get('showInFeatured');
        if (!$showInFeatured AND (strcmp('featured', $this->currentView) === 0)) {
            return true;
        }

        // Exclude articles
        $excludeArticles = $this->params->get('excludeArticles');
        if (!empty($excludeArticles)) {
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        $excludeArticles = ArrayHelper::toInteger($excludeArticles);

        // Exluded categories
        $excludedCats = $this->params->get('excludeCats');
        if (!empty($excludedCats)) {
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        $excludedCats = ArrayHelper::toInteger($excludedCats);

        // Included Articles
        $includedArticles = $this->params->get('includeArticles');
        if (!empty($includedArticles)) {
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        $includedArticles = ArrayHelper::toInteger($includedArticles);

        if (!in_array((int)$article->id, $includedArticles, true)) {
            // Check excluded articles
            if (in_array((int)$article->id, $excludeArticles, true) OR in_array((int)$article->catid, $excludedCats, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * This method does verification for K2 restrictions
     *
     * @param jIcalEventRepeat $article
     * @param string           $context
     *
     * @return bool
     */
    private function isK2Restricted(&$article, $context, &$params)
    {
        // Check for correct context
        if (strpos($context, 'com_k2') === false) {
            return true;
        }

        if ($article instanceof TableK2Category) {
            return true;
        }

        $displayInItemlist = $this->params->get('k2DisplayInItemlist', 0);
        if (!$displayInItemlist AND (strcmp('itemlist', $this->currentView) === 0)) {
            return true;
        }

        $displayInArticles = $this->params->get('k2DisplayInArticles', 0);
        if (!$displayInArticles AND (strcmp('item', $this->currentView) === 0)) {
            return true;
        }

        // Exclude articles
        $excludeArticles = $this->params->get('k2_exclude_articles');
        if (!empty($excludeArticles)) {
            $excludeArticles = explode(',', $excludeArticles);
        }
        settype($excludeArticles, 'array');
        $excludeArticles = ArrayHelper::toInteger($excludeArticles);

        // Excluded categories
        $excludedCats = $this->params->get('k2_exclude_cats');
        if (!empty($excludedCats)) {
            $excludedCats = explode(',', $excludedCats);
        }
        settype($excludedCats, 'array');
        $excludedCats = ArrayHelper::toInteger($excludedCats);

        // Included Articles
        $includedArticles = $this->params->get('k2_include_articles');
        if (!empty($includedArticles)) {
            $includedArticles = explode(',', $includedArticles);
        }
        settype($includedArticles, 'array');
        $includedArticles = ArrayHelper::toInteger($includedArticles);

        if (!in_array((int)$article->id, $includedArticles, true)) {
            // Check excluded articles
            if (in_array((int)$article->id, $excludeArticles, true) OR in_array((int)$article->catid, $excludedCats, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Do verifications for JEvent extension
     *
     * @param jIcalEventRepeat $article
     * @param string           $context
     *
     * @return bool
     */
    private function isJEventsRestricted(&$article, $context)
    {
        // Display buttons only in the description
        if (!is_a($article, 'jIcalEventRepeat')) {
            return true;
        }

        // Check for correct context
        if (strpos($context, 'com_jevents') === false) {
            return true;
        }

        // Display only in task 'icalrepeat.detail'
        if (strcmp('icalrepeat.detail', $this->currentTask) !== 0) {
            return true;
        }

        $displayInEvents = $this->params->get('jeDisplayInEvents', 0);
        if (!$displayInEvents) {
            return true;
        }

        return false;
    }

    /**
     * This method does verification for VirtueMart restrictions
     *
     * @param stdClass $article
     * @param string   $context
     *
     * @return bool
     */
    private function isVirtuemartRestricted(&$article, $context)
    {
        // Check for correct context
        if (strpos($context, 'com_virtuemart') === false) {
            return true;
        }

        // Display content only in the view 'productdetails'
        if (strcmp('productdetails', $this->currentView) !== 0) {
            return true;
        }

        // Display content only in the view 'productdetails'
        $displayInDetails = $this->params->get('vmDisplayInDetails', 0);
        if (!$displayInDetails) {
            return true;
        }

        return false;
    }

    /**
     * It's a method that verify restriction for the component 'com_joomshopping'
     *
     * @param object $article
     * @param string $context
     *
     * @return bool
     */
    private function isJoomShoppingRestricted(&$article, $context)
    {
        // Check for correct context
        if (false === strpos($context, 'com_content.article')) {
            return true;
        }

        // Check for enabled functionality for that extension
        $displayInDetails = $this->params->get('joomshopping_display', 0);
        if (!$displayInDetails OR !isset($article->product_id)) {
            return true;
        }

        return false;
    }

    /**
     * It's a method that verify restriction for the component 'com_hikashop'
     *
     * @param object $article
     * @param string $context
     *
     * @return bool
     */
    private function isHikaShopRestricted(&$article, $context)
    {
        // Check for correct context
        if (false === strpos($context, 'text')) {
            return true;
        }

        // Display content only in the view 'product'
        if (strcmp('product', $this->currentView) !== 0) {
            return true;
        }

        // Check for enabled functionality for that extension
        $displayInDetails = $this->params->get('hikashop_display', 0);
        if (!$displayInDetails) {
            return true;
        }

        return false;
    }

    /**
     * Do verification for EShop (com_eshop) extension. Is it restricted?
     *
     * @param stdClass $article
     * @param string   $context
     *
     * @return bool
     */
    private function isEshopRestricted(&$article, $context)
    {
        // Check for correct context
        if (strpos($context, 'text') === false) {
            return true;
        }

        // Display only in view 'quote'
        $allowedViews = array('product');
        if (!in_array($this->currentView, $allowedViews, true)) {
            return true;
        }

        $displayOnViewDetails = $this->params->get('eshop_display_details', 0);
        if (!$displayOnViewDetails) {
            return true;
        }

        return false;
    }

    private function getContent()
    {
        $doc   = JFactory::getDocument();
        /** @var $doc JDocumentHtml **/

        $iconType = $this->params->get('rss');

        // Get size
        $format  = explode('_', $iconType);
        $size    = explode('x', $format[1]);

        $bg      = JUri::root() . 'plugins/content/itpsubscribe/images/bg' .$this->params->get('bg') .'.png';
        $rss     = JUri::root() . 'plugins/content/itpsubscribe/images/rss' .$format[0].'.png';
        $top     = $this->params->get('top');
        $left    = $this->params->get('left');

$css = '.itp-subs{
  background-image: url('.$bg.');
}

.itp-subscribe-plugin a.itp-rss-icon{
  background-image: url('.$rss.');
  width: ' . $size[0] . 'px;
  height: ' . $size[1] . 'px;
  top: ' . $top .'px;
  left: ' . $left .'px;
}';

        $doc->addStyleDeclaration($css);

        $form = '';
        
        // Feedburner code
        if($this->params->get('displayFeedburner')) {
            $form .= '
            <form onsubmit="window.open(\'http://feedburner.google.com/fb/a/mailverify?uri=' .$this->params->get('feedburner_uri') .'\', \'popupwindow\', \'scrollbars=yes,width=550,height=520\');return true" target="popupwindow" method="post" action="http://feedburner.google.com/fb/a/mailverify">
                <input type="text" name="email" class="itps-em" />
                <input type="hidden" name="uri" value="' .$this->params->get('feedburner_uri') .'" />
                <input type="hidden" value="'. $this->params->get('feedburner_l', 'en_GB') .'" name="loc" />
                <input type="submit" value="'. JText::_('PLG_CONTENT_ITPSUBSCRIBE_SUBMIT') . '" class="btn btn-primary" />
            </form>';
        }
        
        // Let's display the content
        $rssLink = $this->params->get('rssLink');

        // Custom form code
        if ($this->params->get('displayCustomForm')) {
            $form .= $this->params->get('customHtmlFormCode');
        }

        // Generate the HTML code 
        return '
        <div class="itp-subscribe-plugin">
            <div class="itp-subs"><a href="' . $rssLink . '" class="itp-rss-icon" ></a>
                <div class="itps-text" >' . JText::sprintf('PLG_CONTENT_ITPSUBSCRIBE_SUBSCRIBE_VIA', $rssLink) . '
                	' . $form . '
                </div>
            </div>
        </div>
        <div style="clear:both;">&nbsp;</div>
        ';
    }
}