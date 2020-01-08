<?php
/**
 * Kunena Component
 *
 * @package       Kunena.Administrator
 * @subpackage    Views
 *
 * @copyright     Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license       https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link          https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Views;

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Kunena\Forum\Libraries\View;
use function defined;

/**
 * Email view for Kunena backend
 *
 * @since 5.0
 */
class KunenaAdminViewEmail extends View
{
	/**
	 * @param   null  $tpl  tpl
	 *
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 *
	 * @throws  \Exception
	 */
	public function displayDefault($tpl = null)
	{
		$this->state      = $this->get('state');
		$this->group      = $this->state->get('group');
		$this->items      = $this->get('items');
		$this->pagination = $this->get('Pagination');

		$document = Factory::getApplication()->getDocument();
		$document->setTitle(Text::_('COM_KUNENA_A_EMAIL_MANAGER'));

		$this->setToolbar();
		$this->display();
	}

	/**
	 * Set the toolbar on log manager
	 *
	 * @return  void
	 *
	 * @since   Kunena 6.0
	 */
	protected function setToolbar()
	{
		// Get the toolbar object instance
		$bar = Toolbar::getInstance('toolbar');

		// Set the titlebar text
		ToolbarHelper::title(Text::_('COM_KUNENA') . ': ' . Text::_('COM_KUNENA_A_EMAIL_MANAGER'));
	}
}
