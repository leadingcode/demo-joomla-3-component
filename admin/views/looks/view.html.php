<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Vast Development Method 
/-------------------------------------------------------------------------------------------------------/

	@version		1.0.5
	@build			20th March, 2016
	@created		5th August, 2015
	@package		Demo
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Demo View class for the Looks
 */
class DemoViewLooks extends JViewLegacy
{
	/**
	 * Looks view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			DemoHelper::addSubmenu('looks');
		}

		// Check for errors.
		if (count($errors = $this->get('Errors')))
                {
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		// Assign data to the view
		$this->items 		= $this->get('Items');
		$this->pagination 	= $this->get('Pagination');
		$this->state		= $this->get('State');
		$this->user 		= JFactory::getUser();
		$this->listOrder	= $this->escape($this->state->get('list.ordering'));
		$this->listDirn		= $this->escape($this->state->get('list.direction'));
		$this->saveOrder	= $this->listOrder == 'ordering';
                // get global action permissions
		$this->canDo		= DemoHelper::getActions('look');
		$this->canEdit		= $this->canDo->get('look.edit');
		$this->canState		= $this->canDo->get('look.edit.state');
		$this->canCreate	= $this->canDo->get('look.create');
		$this->canDelete	= $this->canDo->get('look.delete');
		$this->canBatch	= $this->canDo->get('core.batch');

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
                        // load the batch html
                        if ($this->canCreate && $this->canEdit && $this->canState)
                        {
                                $this->batchDisplay = JHtmlBatch_::render();
                        }
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::_('COM_DEMO_LOOKS'), 'eye-open');
		JHtmlSidebar::setAction('index.php?option=com_demo&view=looks');
                JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
                {
			JToolBarHelper::addNew('look.add');
		}

                // Only load if there are items
                if (DemoHelper::checkArray($this->items))
		{
                        if ($this->canEdit)
                        {
                            JToolBarHelper::editList('look.edit');
                        }

                        if ($this->canState)
                        {
                            JToolBarHelper::publishList('looks.publish');
                            JToolBarHelper::unpublishList('looks.unpublish');
                            JToolBarHelper::archiveList('looks.archive');

                            if ($this->canDo->get('core.admin'))
                            {
                                JToolBarHelper::checkin('looks.checkin');
                            }
                        }

                        // Add a batch button
                        if ($this->canBatch && $this->canCreate && $this->canEdit && $this->canState)
                        {
                                // Get the toolbar object instance
                                $bar = JToolBar::getInstance('toolbar');
                                // set the batch button name
                                $title = JText::_('JTOOLBAR_BATCH');
                                // Instantiate a new JLayoutFile instance and render the batch button
                                $layout = new JLayoutFile('joomla.toolbar.batch');
                                // add the button to the page
                                $dhtml = $layout->render(array('title' => $title));
                                $bar->appendButton('Custom', $dhtml, 'batch');
                        } 

                        if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
                        {
                            JToolbarHelper::deleteList('', 'looks.delete', 'JTOOLBAR_EMPTY_TRASH');
                        }
                        elseif ($this->canState && $this->canDelete)
                        {
                                JToolbarHelper::trash('looks.trash');
                        }

			if ($this->canDo->get('core.export') && $this->canDo->get('look.export'))
			{
				JToolBarHelper::custom('looks.exportData', 'download', '', 'COM_DEMO_EXPORT_DATA', true);
			}
                }

		if ($this->canDo->get('core.import') && $this->canDo->get('look.import'))
		{
			JToolBarHelper::custom('looks.importData', 'upload', '', 'COM_DEMO_IMPORT_DATA', false);
		}

                // set help url for this view if found
                $help_url = DemoHelper::getHelpUrl('looks');
                if (DemoHelper::checkString($help_url))
                {
                        JToolbarHelper::help('COM_DEMO_HELP_MANAGER', false, $help_url);
                }

                // add the options comp button
                if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
                {
                        JToolBarHelper::preferences('com_demo');
                }

                if ($this->canState)
                {
			JHtmlSidebar::addFilter(
				JText::_('JOPTION_SELECT_PUBLISHED'),
				'filter_published',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions'), 'value', 'text', $this->state->get('filter.published'), true)
			);
                        // only load if batch allowed
                        if ($this->canBatch)
                        {
                            JHtmlBatch_::addListSelection(
                                JText::_('COM_DEMO_KEEP_ORIGINAL_STATE'),
                                'batch[published]',
                                JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
                            );
                        }
		}

		JHtmlSidebar::addFilter(
			JText::_('JOPTION_SELECT_ACCESS'),
			'filter_access',
			JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text', $this->state->get('filter.access'))
		);

		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			JHtmlBatch_::addListSelection(
                                JText::_('COM_DEMO_KEEP_ORIGINAL_ACCESS'),
                                'batch[access]',
                                JHtml::_('select.options', JHtml::_('access.assetgroups'), 'value', 'text')
			);
                }  

		// Set Add Selection
		$this->addOptions = $this->getTheAddSelections();
		if ($this->addOptions)
		{
			// Add Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_LOOK_ADD_LABEL').' -',
				'filter_add',
				JHtml::_('select.options', $this->addOptions, 'value', 'text', $this->state->get('filter.add'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Add Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_LOOK_ADD_LABEL').' -',
					'batch[add]',
					JHtml::_('select.options', $this->addOptions, 'value', 'text')
				);
			}
		}

		// Set Acronym Selection
		$this->acronymOptions = $this->getTheAcronymSelections();
		if ($this->acronymOptions)
		{
			// Acronym Filter
			JHtmlSidebar::addFilter(
				'- Select '.JText::_('COM_DEMO_LOOK_ACRONYM_LABEL').' -',
				'filter_acronym',
				JHtml::_('select.options', $this->acronymOptions, 'value', 'text', $this->state->get('filter.acronym'))
			);

			if ($this->canBatch && $this->canCreate && $this->canEdit)
			{
				// Acronym Batch Selection
				JHtmlBatch_::addListSelection(
					'- Keep Original '.JText::_('COM_DEMO_LOOK_ACRONYM_LABEL').' -',
					'batch[acronym]',
					JHtml::_('select.options', $this->acronymOptions, 'value', 'text')
				);
			}
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		$document = JFactory::getDocument();
		$document->setTitle(JText::_('COM_DEMO_LOOKS'));
		$document->addStyleSheet(JURI::root() . "administrator/components/com_demo/assets/css/looks.css");
	}

        /**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 50)
		{
                        // use the helper htmlEscape method instead and shorten the string
			return DemoHelper::htmlEscape($var, $this->_charset, true);
		}
                // use the helper htmlEscape method instead.
		return DemoHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.sorting' => JText::_('JGRID_HEADING_ORDERING'),
			'a.published' => JText::_('JSTATUS'),
			'a.name' => JText::_('COM_DEMO_LOOK_NAME_LABEL'),
			'a.description' => JText::_('COM_DEMO_LOOK_DESCRIPTION_LABEL'),
			'a.add' => JText::_('COM_DEMO_LOOK_ADD_LABEL'),
			'a.acronym' => JText::_('COM_DEMO_LOOK_ACRONYM_LABEL'),
			'a.website' => JText::_('COM_DEMO_LOOK_WEBSITE_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	} 

	protected function getTheAddSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('add'));
		$query->from($db->quoteName('#__demo_look'));
		$query->order($db->quoteName('add') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			// get model
			$model = $this->getModel();
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $add)
			{
				// Translate the add selection
				$text = $model->selectionTranslation($add,'add');
				// Now add the add and its text to the options array
				$filter[] = JHtml::_('select.option', $add, JText::_($text));
			}
			return $filter;
		}
		return false;
	}

	protected function getTheAcronymSelections()
	{
		// Get a db connection.
		$db = JFactory::getDbo();

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select the text.
		$query->select($db->quoteName('acronym'));
		$query->from($db->quoteName('#__demo_look'));
		$query->order($db->quoteName('acronym') . ' ASC');

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		$results = $db->loadColumn();

		if ($results)
		{
			$results = array_unique($results);
			$filter = array();
			foreach ($results as $acronym)
			{
				// Now add the acronym and its text to the options array
				$filter[] = JHtml::_('select.option', $acronym, $acronym);
			}
			return $filter;
		}
		return false;
	}
}
