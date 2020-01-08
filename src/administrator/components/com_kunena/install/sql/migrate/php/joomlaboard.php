<?php
/**
 * Kunena Component
 *
 * @package        Kunena.Installer
 *
 * @copyright      Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license        https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link           https://www.kunena.org
 **/

namespace Kunena\Forum\Administrator\Migrate\Php;

defined('_JEXEC') or die();

use function defined;

/**
 * Class KunenaMigratorJoomlaboard
 *
 * @since   Kunena 6.0
 */
class KunenaMigratorJoomlaboard
{
	/**
	 * @var     array
	 * @since   Kunena 6.0
	 */
	protected $versions = [
		['version' => '1.0', 'date' => '1000-01-01', 'table' => 'sb_messages', 'column' => 'id'],
	];

	/**
	 * @return  KunenaMigratorJoomlaboard|null
	 *
	 * @since   Kunena 6.0
	 */
	public static function getInstance()
	{
		static $instance = null;

		if (!$instance)
		{
			$instance = new KunenaMigratorJoomlaboard;
		}

		return $instance;
	}

	/**
	 * Detect JoomlaBoard version.
	 *
	 * @return  string  JoomlaBoard version or null.
	 *
	 * @since   Kunena 6.0
	 */
	public function detect()
	{
		foreach ($this->versions as $version)
		{
			if (\Kunena\Forum\Libraries\Installer::getTableColumn($version['table'], $version['column']))
			{
				return $version->version;
			}
		}

		return null;
	}
}
