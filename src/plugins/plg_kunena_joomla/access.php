<?php
/**
 * Kunena Plugin
 *
 * @package         Kunena.Plugins
 * @subpackage      Joomla
 *
 * @copyright       Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
 **/

namespace Kunena\Forum\Plugin\Kunena\Joomla;

defined('_JEXEC') or die();

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\User\User;
use Kunena\Forum\Libraries\Database\KunenaDatabaseObject;
use Kunena\Forum\Libraries\Forum\Category\Category;
use Kunena\Forum\Libraries\Forum\KunenaForum;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use StdClass;
use function defined;

/**
 * Kunena Access Control for Joomla 2.5+
 *
 * @since   Kunena 6.0
 */
class AccessJoomla
{
	/**
	 * @var     null
	 * @since   Kunena 6.0
	 */
	protected static $viewLevels = null;

	/**
	 * @var     null
	 * @since   Kunena 6.0
	 */
	protected $params = null;

	/**
	 * @param   object  $params  params
	 *
	 * @since   Kunena 6.0
	 */
	public function __construct($params)
	{
		$this->params = $params;
	}

	/**
	 * Get list of supported access types.
	 *
	 * List all access types you want to handle. All names must be less than 20 characters.
	 * Examples: joomla.level, mycomponent.groups, mycomponent.vipusers
	 *
	 * @return  array    Supported access types.
	 * @since   Kunena 6.0
	 */
	public function getAccessTypes()
	{
		static $accesstypes = ['joomla.level', 'joomla.group'];

		return $accesstypes;
	}

	/**
	 * Get access groups for the selected category.
	 *
	 * @param   Category  $category  Category
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 */
	public function getCategoryAccess(Category $category)
	{
		$list = [];

		if ($category->accesstype == 'joomla.group')
		{
			$groupname  = $this->getGroupName($category->accesstype, $category->pub_access);
			$accessname = Text::sprintf($category->pub_recurse ? 'COM_KUNENA_A_GROUP_X_PLUS' : 'COM_KUNENA_A_GROUP_X_ONLY', $groupname ? Text::_($groupname) : Text::_('COM_KUNENA_NOBODY'));

			$list["joomla.group.{$category->pub_access}"] = ['type'  => 'joomla.group', 'id' => $category->pub_access, 'alias' => $accessname,
			                                                 'title' => $accessname];

			$groupname = $this->getGroupName($category->accesstype, $category->admin_access);

			if ($groupname && $category->pub_access != $category->admin_access)
			{
				$accessname                                     = Text::sprintf($category->admin_recurse ? 'COM_KUNENA_A_GROUP_X_PLUS' : 'COM_KUNENA_A_GROUP_X_ONLY', Text::_($groupname));
				$list["joomla.group.{$category->admin_access}"] = ['type'  => 'joomla.group', 'id' => $category->admin_access, 'alias' => $accessname,
				                                                   'title' => $accessname];
			}
		}
		else
		{
			$groupname                                = $this->getGroupName($category->accesstype, $category->access);
			$list["joomla.level.{$category->access}"] = ['type'  => 'joomla.level', 'id' => $category->access, 'alias' => $groupname,
			                                             'title' => $groupname];
		}

		return $list;
	}

	/**
	 * Get group name in selected access type.
	 *
	 * @param   string  $accesstype  Access type.
	 * @param   int     $id          Group id.
	 *
	 * @return  string|null
	 *
	 * @since   Kunena 6.0
	 */
	public function getGroupName($accesstype, $id = null)
	{
		static $groups = [];

		if (!isset($groups[$accesstype]))
		{
			// Cache all group names.
			$db    = Factory::getDBO();
			$query = $db->getQuery(true);
			$query->select('id, title');

			if ($accesstype == 'joomla.group')
			{
				$query->from($db->quoteName('#__usergroups'));
				$db->setQuery($query);
			}
			elseif ($accesstype == 'joomla.level')
			{
				$query->from($db->quoteName('#__viewlevels'));
				$db->setQuery($query);
			}
			else
			{
				return '';
			}

			$groups[$accesstype] = $db->loadObjectList('id');
		}

		if ($id !== null)
		{
			return isset($groups[$accesstype][$id]) ? $groups[$accesstype][$id]->title : Text::_('COM_KUNENA_NOBODY');
		}

		return $groups[$accesstype];
	}

	/**
	 * Get HTML list of the available groups
	 *
	 * @param   string  $accesstype  Access type.
	 * @param   int     $category    Group id.
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 */
	public function getAccessOptions($accesstype, $category)
	{
		$html = [];

		if (!$accesstype || $accesstype == 'joomla.level')
		{
			$html ['joomla-level']['access'] = [
				'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_LEVEL_TITLE'),
				'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_LEVEL_DESC') . '<br /><br />' . Text::_('PLG_KUNENA_JOOMLA_ACCESS_LEVEL_DESC_J25'),
				'input' => HTMLHelper::_('access.assetgrouplist', 'access', $category->accesstype == 'joomla.level' ? $category->access : 1),
			];

			if (!$category->isSection())
			{
				$html ['joomla-level']['post']  = [
					'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_POST_TITLE'),
					'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_POST_DESC'),
					'input' => HTMLHelper::_('access.usergroup', 'params-joomla-level[access_post][]',
						$category->params->get('access_post', [2, 6, 8]), 'multiple="multiple" class="inputbox form-control" size="10"', false
					),
				];
				$html ['joomla-level']['reply'] = [
					'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_REPLY_TITLE'),
					'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_REPLY_DESC'),
					'input' => HTMLHelper::_('access.usergroup', 'params-joomla-level[access_reply][]',
						$category->params->get('access_reply', [2, 6, 8]), 'multiple="multiple" class="inputbox form-control" size="10"', false
					),
				];
			}
		}

		if (!$accesstype || $accesstype == 'joomla.group')
		{
			$yesno    = [];
			$yesno [] = HTMLHelper::_('select.option', 0, Text::_('COM_KUNENA_NO'));
			$yesno [] = HTMLHelper::_('select.option', 1, Text::_('COM_KUNENA_YES'));

			$html ['joomla-group']['pub_access'] = [
				'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_TITLE'),
				'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_DESC') . '<br /><br />' .
					Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_DESC2') . '<br /><br />' .
					Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_DESC_J25'),
				'input' => HTMLHelper::_('access.usergroup', 'pub_access', $category->pub_access, 'class="inputbox form-control" size="10"', false),
			];

			$html ['joomla-group']['pub_recurse']  = [
				'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_CHILDS_TITLE'),
				'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_PRIMARY_CHILDS_DESC'),
				'input' => HTMLHelper::_('select.genericlist', $yesno, 'pub_recurse', 'class="inputbox form-control" size="1"', 'value', 'text', $category->pub_recurse),
			];
			$html ['joomla-group']['admin_access'] = [
				'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_TITLE'),
				'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_DESC') . '<br /><br />' .
					Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_DESC2') . '<br /><br />' .
					Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_DESC_J25'),
				'input' => HTMLHelper::_('access.usergroup', 'admin_access', $category->admin_access, 'class="inputbox form-control" size="10"', false),
			];

			$html ['joomla-group']['admin_recurse'] = [
				'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_CHILDS_TITLE'),
				'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUP_SECONDARY_CHILDS_DESC'),
				'input' => HTMLHelper::_('select.genericlist', $yesno, 'admin_recurse', 'class="inputbox form-control" size="1"', 'value', 'text', $category->admin_recurse),
			];

			if (!$category->isSection())
			{
				$html ['joomla-group']['post']  = [
					'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_POST_TITLE'),
					'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_POST_DESC'),
					'input' => HTMLHelper::_('access.usergroup', 'params-joomla-group[access_post][]',
						$category->params->get('access_post', [2, 6, 8]), 'multiple="multiple" class="inputbox form-control" size="10"', false
					),
				];
				$html ['joomla-group']['reply'] = [
					'title' => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_REPLY_TITLE'),
					'desc'  => Text::_('PLG_KUNENA_JOOMLA_ACCESS_GROUPS_REPLY_DESC'),
					'input' => HTMLHelper::_('access.usergroup', 'params-joomla-group[access_reply][]',
						$category->params->get('access_reply', [2, 6, 8]), 'multiple="multiple" class="inputbox form-control" size="10"', false
					),
				];
			}
		}

		return $html;
	}

	/**
	 * Load moderators and administrators for listed categories.
	 *
	 * This function is used to add category administrators and moderators to listed categories. In addition
	 * integration can also add global administrators (catid=0).
	 *
	 * Results may be cached.
	 *
	 * @param   array  $categories  List of categories, null = all.
	 *
	 * @return  array of (catid=>userid)
	 *
	 * @since   Kunena 6.0
	 */
	public function loadCategoryRoles(array $categories = null)
	{
		$list = [];

		// Currently we have only global administrators in Joomla
		$admins = array_merge($this->getAuthorisedUsers('core.admin', 'com_kunena'), $this->getAuthorisedUsers('core.manage', 'com_kunena'));

		foreach ($admins as $userid)
		{
			$item              = new StdClass;
			$item->user_id     = (int) $userid;
			$item->category_id = 0;
			$item->role        = KunenaForum::ADMINISTRATOR;
			$list[]            = $item;
		}

		return $list;
	}

	/**
	 * @param   string  $action  action
	 * @param   null    $asset   asset
	 *
	 * @return  array
	 * @since   Kunena 6.0
	 */
	protected function getAuthorisedUsers($action, $asset = null)
	{
		$action = strtolower(preg_replace('#[\s\-]+#', '.', trim($action)));
		$asset  = strtolower(preg_replace('#[\s\-]+#', '.', trim($asset)));

		// Default to the root asset node.
		if (empty($asset))
		{
			$asset = 1;
		}

		// Get all asset rules
		$rules = Access::getAssetRules($asset, true);
		$data  = $rules->getData();

		// Get all action rules for the asset
		$groups = [];

		if (!empty($data[$action]))
		{
			$groups = $data[$action]->getData();
		}

		// Split groups into allow and deny list
		$allow = [];
		$deny  = [];

		foreach ($groups as $groupid => $access)
		{
			if ($access)
			{
				$allow[] = $groupid;
			}
			else
			{
				$deny[] = $groupid;
			}
		}

		// Get userids
		if ($allow)
		{
			// These users can do the action
			$allow = $this->getUsersByGroup($allow, true);
		}

		if ($deny)
		{
			// But these users have explicit deny for the action
			$deny = $this->getUsersByGroup($deny, true);
		}

		// Remove denied users from allowed users list
		return array_diff($allow, $deny);
	}

	/**
	 * Method to return a list of user Ids contained in a Group (derived from Joomla 1.6)
	 *
	 * @param   int|array  $groupId    The group Id
	 * @param   boolean    $recursive  Recursively include all child groups (optional)
	 * @param   array      $inUsers    Only list selected users.
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 */
	protected function getUsersByGroup($groupId, $recursive = false, $inUsers = [])
	{
		// Get a database object.
		$db = Factory::getDbo();

		$rec = $recursive ? '>=' : '=';

		if (empty($groupId))
		{
			return [];
		}

		if (is_array($groupId))
		{
			$groupId = implode(',', $groupId);
		}

		$inUsers = implode(',', $inUsers);

		// First find the users contained in the group
		$query = $db->getQuery(true);
		$query->select('DISTINCT(user_id)')
			->from($db->quoteName('#__usergroups', 'ug1'))
			->innerJoin($db->quoteName('#__usergroups', 'ug2') . ' ON ug2.lft ' . $rec . ' ug1.lft AND ug1.rgt ' . $rec . ' ug2.rgt')
			->innerJoin($db->quoteName('#__user_usergroup_map', 'm') . ' ON ug2.id = m.group_id')
			->where('ug1.id IN ( ' . $groupId . ')');

		if ($inUsers)
		{
			$query->andWhere('user_id IN (' . $inUsers . ')');
		}

		$db->setQuery($query);

		$result = (array) $db->loadColumn();

		// Clean up any NULL values, just in case
		$result = ArrayHelper::toInteger($result);

		return $result;
	}

	/**
	 * Authorise user actions in a category.
	 *
	 * Function returns a list of authorised actions. Missing actions are threaded as inherit.
	 *
	 * @param   Category  $category  category
	 * @param   int       $userid    userid
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 */
	public function getAuthoriseActions(Category $category, $userid)
	{
		$category->params = new Registry($category->params);
		$groups           = (array) Access::getGroupsByUser($userid, true);
		$post             = array_intersect($groups, (array) $category->params->get('access_post', [2, 6, 8]));
		$reply            = array_intersect($groups, (array) $category->params->get('access_reply', [2, 6, 8]));

		return ['topic.create' => !empty($post), 'topic.reply' => !empty($reply), 'topic.post.reply' => !empty($reply)];
	}

	// Internal functions

	/**
	 * Authorise list of categories.
	 *
	 * Function accepts array of id indexed \Kunena\Forum\Libraries\Forum\Category\Category objects and removes
	 * unauthorised categories from the list.
	 *
	 * Results for the current user are saved into session.
	 *
	 * @param   int    $userid      User who needs the authorisation (null=current user, 0=visitor).
	 * @param   array  $categories  List of categories in access type.
	 *
	 * @return  array where category ids are in the keys.
	 *
	 * @since   Kunena 6.0
	 */
	public function authoriseCategories($userid, array &$categories)
	{
		$user = Factory::getUser($userid);

		// WORKAROUND: Joomla! 2.5.6 bug returning NULL if $userid = 0 and session is corrupted.
		if (!($user instanceof User))
		{
			$user = User::getInstance();
		}

		$accesslevels = (array) $user->getAuthorisedViewLevels();
		$groups_r     = (array) Access::getGroupsByUser($user->id, true);
		$groups       = (array) Access::getGroupsByUser($user->id, false);

		$catlist = [];

		foreach ($categories as $category)
		{
			// Check against Joomla access level
			if ($category->accesstype == 'joomla.level')
			{
				if (in_array($category->access, $accesslevels))
				{
					$catlist[$category->id] = $category->id;
				}
			}
			// Check against Joomla user group
			elseif ($category->accesstype == 'joomla.group')
			{
				$pub_access   = in_array($category->pub_access, $category->pub_recurse ? $groups_r : $groups);
				$admin_access = in_array($category->admin_access, $category->admin_recurse ? $groups_r : $groups);

				if ($pub_access || $admin_access)
				{
					$catlist[$category->id] = $category->id;
				}
			}
		}

		return $catlist;
	}

	/**
	 * Authorise list of userids to topic or category.
	 *
	 * @param   mixed  $topic    Category or topic.
	 * @param   array  $userids  list(allow, deny).
	 *
	 * @return  array
	 *
	 * @since   Kunena 6.0
	 */
	public function authoriseUsers(KunenaDatabaseObject $topic, array &$userids)
	{
		$allow = $deny = [];

		if (empty($userids))
		{
			return [$allow, $deny];
		}

		$category = $topic->getCategory();

		if ($category->accesstype == 'joomla.level')
		{
			// Check against Joomla access levels
			$groups = $this->getGroupsByViewLevel($category->access);
			$allow  = $this->getUsersByGroup($groups, true, $userids);
		}
		elseif ($category->accesstype == 'joomla.group')
		{
			if ($category->pub_access <= 0)
			{
				return [$allow, $deny];
			}

			// Check against Joomla user groups
			$public = $this->getUsersByGroup($category->pub_access, $category->pub_recurse, $userids);
			$admin  = $category->admin_access && $category->admin_access != $category->pub_access ? $this->getUsersByGroup($category->admin_access,
				$category->admin_recurse, $userids
			) : [];
			$allow  = array_merge($public, $admin);
		}

		return [$allow, $deny];
	}

	/**
	 * Method to return a list of groups which have view level (derived from Joomla 1.6)
	 *
	 * @param   integer  $viewlevel  viewlevel
	 *
	 * @return    array    List of view levels for which the user is authorised.
	 *
	 * @since   Kunena 6.0
	 */
	protected function getGroupsByViewLevel($viewlevel)
	{
		// Only load the view levels once.
		if (empty(self::$viewLevels))
		{
			// Get a database object.
			$db = Factory::getDBO();

			// Build the base query.
			$query = $db->getQuery(true);
			$query->select('id, rules');
			$query->from($db->quoteName('#__viewlevels'));

			// Set the query for execution.
			$db->setQuery($query);

			// Build the view levels array.
			foreach ($db->loadAssocList() as $level)
			{
				self::$viewLevels[$level['id']] = (array) json_decode($level['rules']);
			}
		}

		return isset(self::$viewLevels[$viewlevel]) ? self::$viewLevels[$viewlevel] : [];
	}
}
