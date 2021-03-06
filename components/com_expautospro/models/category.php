<?php

/* * **************************************************************************************\
 * *   @name		EXP Autos  2.0                                                  **
 * *   @package          Joomla 1.6                                                      **
 * *   @author		EXP TEAM::Alexey Kurguz (Grusha)                                **
 * *   @copyright	Copyright (C) 2005 - 2011  EXP TEAM::Alexey Kurguz (Grusha)     **
 * *   @link             http://www.feellove.eu                                          **
 * *   @license		Commercial License                                              **
  \*************************************************************************************** */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modellist');

class ExpautosproModelCategory extends JModelList {

    protected $_item = null;
    protected $_articles = null;
    protected $_siblings = null;
    protected $_children = null;
    protected $_parent = null;
    protected $_category = null;
    protected $_categories = null;

    public function __construct($config = array()) {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = array(
                'id', 'a.id',
                'state', 'a.state',
                'ordering', 'a.ordering'
            );
        }

        parent::__construct($config);
    }

    public function getItems() {
        // Invoke the parent getItems method to get the main list
        $items = parent::getItems();

        // Convert the params field into an object, saving original in _params
        for ($i = 0, $n = count($items); $i < $n; $i++) {
            $item = &$items[$i];
            if (!isset($this->_params)) {
                $params = new JRegistry();
                $params->loadString($item->params);
                $item->params = $params;
            }
        }

        return $items;
    }

    protected function getListQuery() {
        $user = JFactory::getUser();
        $groups = implode(',', $user->getAuthorisedViewLevels());

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);

        // Select required fields from the categories.
        $query->select($this->getState('list.select', 'a.*'));
        $query->from($db->quoteName('#__expautos_admanager') . ' AS a');
        $query->where('a.access IN (' . $groups . ')');

        // Filter by category.
        if ($categoryId = $this->getState('category.id')) {
            $query->where('a.catid = ' . (int) $categoryId);
            $query->join('LEFT', '#__categories AS c ON c.id = a.catid');
            $query->where('c.access IN (' . $groups . ')');
        }

        // Filter by state
        $state = $this->getState('filter.published');
        if (is_numeric($state)) {
            $query->where('a.state = ' . (int) $state);
        }


        // Filter by language
        if ($this->getState('filter.language')) {
            $query->where('a.language in (' . $db->Quote(JFactory::getLanguage()->getTag()) . ',' . $db->Quote('*') . ')');
        }

        // Add the list ordering clause.
        $query->order($db->escape($this->getState('list.ordering', 'a.ordering')) . ' ' . $db->escape($this->getState('list.direction', 'ASC')));

        return $query;
    }

    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_expautospro');
        $db = $this->getDbo();

        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'));
        $this->setState('list.limit', $limit);

        $limitstart = JRequest::getVar('limitstart', 0, '', 'int');
        $this->setState('list.start', $limitstart);

        $menuParams = new JRegistry();
        if ($menu = $app->getMenu()->getActive()) {
            $menuParams->loadString($menu->params);
        }
        $mergedParams = clone $params;
        $mergedParams->merge($menuParams);

        $orderCol = JRequest::getCmd('filter_order', $mergedParams->get('initial_sort', 'ordering'));
        if (!in_array($orderCol, $this->filter_fields)) {
            $orderCol = 'ordering';
        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = JRequest::getCmd('filter_order_Dir', 'ASC');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
            $listOrder = 'ASC';
        }
        $this->setState('list.direction', $listOrder);

        $id = JRequest::getVar('id', 0, '', 'int');
        $this->setState('category.id', $id);

        $user = JFactory::getUser();
        if ((!$user->authorise('core.edit.state', 'com_expautospro')) && (!$user->authorise('core.edit', 'com_expautospro'))) {
            // limit to published for people who can't edit or edit.state.
            $this->setState('filter.published', 1);

            // Filter by start and end dates.
            $this->setState('filter.publish_date', true);
        }
        $this->setState('filter.language', $app->getLanguageFilter());

        // Load the parameters.
        $this->setState('params', $params);
    }

    public function getCategory() {
        if (!is_object($this->_item)) {
            $app = JFactory::getApplication();
            $menu = $app->getMenu();
            $active = $menu->getActive();
            $params = new JRegistry();

            if ($active) {
                $params->loadString($active->params);
            }

            $options = array();
            $options['countItems'] = $params->get('show_cat_items', 1) || $params->get('show_empty_categories', 0);
            $categories = JCategories::getInstance('Expautospro', $options);
            $this->_item = $categories->get($this->getState('category.id', 'root'));
            if (is_object($this->_item)) {
                $this->_children = $this->_item->getChildren();
                $this->_parent = false;
                if ($this->_item->getParent()) {
                    $this->_parent = $this->_item->getParent();
                }
                $this->_rightsibling = $this->_item->getSibling();
                $this->_leftsibling = $this->_item->getSibling(false);
            } else {
                $this->_children = false;
                $this->_parent = false;
            }
        }

        return $this->_item;
    }

    public function getParent() {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }
        return $this->_parent;
    }

    function &getLeftSibling() {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }
        return $this->_leftsibling;
    }

    function &getRightSibling() {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }
        return $this->_rightsibling;
    }

    function &getChildren() {
        if (!is_object($this->_item)) {
            $this->getCategory();
        }
        return $this->_children;
    }

}
