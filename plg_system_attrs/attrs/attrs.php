<?php defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Input\Input;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

class plgSystemAttrs extends CMSPlugin
{
    private
        $input,
        $isAdmin;

    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->input = new Input();
        $this->isAdmin = Factory::getApplication()->isClient('administrator');
        $this->loadLanguage();
    }
    
    public function onContentPrepareForm($form, $data)
    {
        if (!$this->isAdmin) {
            return;
        }

        if (!($form instanceof Form)) {
            return;
        }

        $view = $this->input->getCmd('view', '');
        $option = $this->input->getCmd('option', '');
        $layout = $this->input->getCmd('layout', '');
        $formname = $form->getName();

        $isSystem = $option == 'com_config' && $formname == 'com_config.application';
        $isMenu = $option == 'com_menus' && $formname == 'com_menus.item';
        $isArticle = $option == 'com_content' && $formname == 'com_content.article';
        $isCategory = $option == 'com_categories' && $formname == 'com_categories.category' . $this->input->getCmd('extension', '');
        $isModule = $option == 'com_modules' && $formname == 'com_modules.module';
        $isPlugin = $option == 'com_plugins' && $formname == 'com_plugins.plugin';;
        
        $tp = $isSystem ? 'destsystem' : '';
        if (!$tp) {
            $tp = $isMenu ? 'destmenu' : '';
        }
        if (!$tp) {
            $tp = $isArticle ? 'destarticles' : '';
        }
        if (!$tp) {
            $tp = $isCategory ? 'destcategories' : '';
        }
        if (!$tp) {
            $tp = $isModule ? 'destmodules' : '';
        }
        if (!$tp) {
            $tp = $isPlugin ? 'destplugins' : '';
        }

        if (!$tp) {
            return;
        }

        $fields = $this->getData($tp);
        if (!$fields) {
            return;
        }

        if (is_array($data)) {
            $data = (object)$data;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?><form>';
        
        if ($isSystem) {
            $xml .= '<fieldset name="cookie">';
            $xml .= '<field name="attrssystemspacer" type="spacer" hr="true" />';
            $xml .= '<field name="attrssystemtitle" type="note" label="' . Text::_('PLG_ATTRS_TAB_LABEL') . '"/>';
        }
        if ($isMenu || $isCategory || $isPlugin) {
            $xml .= '<fields name="params"><fieldset name="attrs" label="' . Text::_('PLG_ATTRS_TAB_LABEL') . '">';
        }
        if ($isArticle) {
            $xml .= '<fields name="attribs"><fieldset name="attrs" label="' . Text::_('PLG_ATTRS_TAB_LABEL') . '">';
        }
        if ($isModule) {
            $xml .= '<fieldset name="attrs" label="' . Text::_('PLG_ATTRS_TAB_LABEL') . '"><fields name="params">';
        }
        
        foreach ($fields as $f) {
            
            $name = ' name="attrs_' . $f->name . '"';
            $label = ' label="' . $f->title . '"';
            $class = $f->class ? ' class="' . $f->class . '"' : ' class="input-xlarge"';
            
            switch ($f->tp) {
                case 'text':
                    $xml .= '<field type="text"' . $name . $label . $class . ($f->filter ? ' filter="' . $f->filter . '"' : '') . '/>';
                    break;

                case 'textarea':
                    $xml .= '<field type="textarea"' . $name . $label . $class . ($f->filter ? ' filter="' . $f->filter . '"' : '') . ' rows="5"/>';
                    break;

                case 'editor':
                    $xml .= '<field type="editor"' . $name . $label . ' filter="raw"/>';
                    break;

                case 'list':
                    $xml .= '<field type="list"' . $name . $label . $class . ($f->multiple ? ' multiple="true"' : '') . '>';
                    foreach ($f->val as $val) {
                        $xml .= '<option value="' . $val['vname'] . '">' . $val['vtitle'] . '</option>';
                    }
                    $xml .= '</field>';
                    break;

                case 'media':
                    $xml .= '<field type="media"' . $name . $label . $class . '/>';
                    break;
            }
        }
        
        if ($isSystem) {
            $xml .= '</fieldset>';
        } elseif ($isModule) {
            $xml .= '</fields></fieldset>';
        } else {
            $xml .= '</fieldset></fields>';
        }

        $xml .= '</form>';
        
        $xml = new \SimpleXMLElement($xml);
        $form->setFields($xml, null, false);
        
        return true;
    }

    protected function getData($tp)
    {
        $db = Factory::getDbo();

        $query = $db->getQuery(true)
            ->select('name, title, tp, val, multiple, filter, class')
            ->from('#__attrs')
            ->where($tp . ' = 1')
            ->where('published = 1')
            ->order('id asc');

        $list = $db->setQuery($query)->loadObjectList();

        foreach ($list as &$item) {
            if ($item->tp === 'list' && $item->val !== '') {
                $item->val = json_decode($item->val, true);
            }
        }

        return $list;
    }
}