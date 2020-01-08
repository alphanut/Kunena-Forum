<?php
/**
 * Kunena Component
 *
 * @package         Kunena.Template.Aurelia
 * @subpackage      Layout.User
 *
 * @copyright       Copyright (C) 2008 - 2020 Kunena Team. All rights reserved.
 * @license         https://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link            https://www.kunena.org
**/

namespace Kunena\Forum\Site;

defined('_JEXEC') or die();

use Joomla\CMS\Language\Text;
use function defined;

$user              = $this->user;
$this->ktemplate   = \Kunena\Forum\Libraries\KunenaFactory::getTemplate();
$avatar            = $user->getAvatarImage($this->ktemplate->params->get('avatarType'), 'thumb');
$config            = \Kunena\Forum\Libraries\Config::getInstance();
$show              = $config->showuserstats;
$optional_username = \Kunena\Forum\Libraries\KunenaFactory::getTemplate()->params->get('optional_username');

if ($show)
{
	if ($config->showkarma)
	{
		$canseekarma = $user->canSeeKarma();
	}

	$rankImage    = $user->getRank($this->category_id, 'image');
	$rankTitle    = $user->getRank($this->category_id, 'title');
	$personalText = $user->getPersonalText();
}
?>

<div class="col-md-2">
	<ul class="unstyled center profilebox">
		<li>
			<strong><?php echo $user->getLink(null, null, '', '', null, $this->category_id); ?></strong>
		</li>

		<?php if ($optional_username)
			:
			?>
			<li>
				[<?php echo $user->getLinkNoStyle('', '', 'kpost-username-optional') ?>]
			</li>
		<?php endif; ?>

		<?php if ($avatar)
			:
			?>
			<li>
				<?php echo $user->getLink($avatar, null, '', '', null, 0, $config->avataredit); ?>
			</li>
			<?php if (isset($this->topic_starter) && $this->topic_starter)
			:
			?>
			<span class="hidden-sm hidden-md topic-starter"><?php echo Text::_('COM_KUNENA_TOPIC_AUTHOR') ?></span>
		<?php endif;
			?>
			<?php // If (!$this->topic_starter && $user->isModerator()) :
			?><!--
					<span class="topic-moderator"><?php // Echo Text::_('COM_KUNENA_MODERATOR')
			?></span>
				--><?php // Endif;
			?>

		<?php endif; ?>
		<?php if ($user->exists() && $config->user_status) : ?>
			<li>
				<?php echo $this->subLayout('User/Item/Status')->set('user', $user); ?>
			</li>
		<?php endif; ?>
	</ul>
</div>
<div class="col-md-2">
	<br>
	<ul class="profilebox center">
		<?php if (!empty($rankTitle))
			:
			?>
			<li>
				<?php echo $this->escape($rankTitle); ?>
			</li>
		<?php endif; ?>

		<?php if (!empty($rankImage))
			:
			?>
			<li>
				<?php echo $rankImage; ?>
			</li>
		<?php endif; ?>

		<?php if (!empty($personalText))
			:
			?>
			<li>
				<?php echo $personalText; ?>
			</li>
		<?php endif; ?>
	</ul>
</div>
<div class="col-md-2">
	<br>
	<?php if ($user->posts >= 1)
		:
		?>
		<li>
			<strong> <?php echo Text::_('COM_KUNENA_POSTS'); ?> </strong>
			<span> <?php echo Text::sprintf((int) $user->posts); ?> </span>
		</li>
	<?php endif; ?>

	<?php if ($canseekarma && $config->showkarma) : ?>
		<li>
			<strong> <?php echo Text::_('COM_KUNENA_KARMA'); ?>:</strong>
			<span> <?php $this->subLayout('Widget/Karma')->setLayout('minus')->set('topicicontype', $this->ktemplate->params->get('topicicontype'))->set('userid', $user->userid).$this->subLayout('Widget/Karma')->setLayout('plus')->set('topicicontype', $this->ktemplate->params->get('topicicontype'))->set('userid', $user->userid); ?> </span>
		</li>
	<?php endif; ?>

	<?php if ($show && isset($user->thankyou) && $config->showthankyou) : ?>
		<li>
			<strong> <?php echo Text::_('COM_KUNENA_THANK_YOU_RECEIVED'); ?>:</strong>
			<span> <?php echo Text::sprintf((int) $user->thankyou); ?> </span>
		</li>
	<?php endif; ?>
	<?php
	if (isset($user->points))
		:
		?>
		<li>
			<strong> <?php echo Text::_('COM_KUNENA_AUP_POINTS'); ?> </strong>
			<span> <?php echo (int) $user->points; ?> </span>
		</li>
	<?php endif; ?>
	<?php
	if ($show && !empty($user->medals))
		:
		?>
		<li>
			<?php echo implode(' ', $user->medals); ?>
		</li>
	<?php endif; ?>
</div>
<div class="col-md-3">
	<br>
	<li>
		<strong> <?php echo Text::_('COM_KUNENA_MYPROFILE_GENDER'); ?>:</strong>
		<span> <?php echo $user->getGender(); ?> </span>
	</li>
	<li>
		<strong> <?php echo Text::_('COM_KUNENA_MYPROFILE_BIRTHDATE'); ?>:</strong>
		<span> <?php echo \Kunena\Forum\Libraries\KunenaDate::getInstance($user->birthdate)->toSpan('date', 'ago', 'utc'); ?> </span>
	</li>
	<?php echo $this->subLayout('Widget/Module')->set('position', 'kunena_profile_horizontal'); ?>
	<?php echo $this->subLayout('Widget/Module')->set('position', 'kunena_profile_horizontal_' . $user->userid); ?>
	<?php echo $this->subLayout('Widget/Module')->set('position', 'kunena_profile_horizontal_' . $user->rank); ?>
</div>
