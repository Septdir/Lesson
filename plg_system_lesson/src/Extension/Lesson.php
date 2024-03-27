<?php
/*
 * @package     RadicalMart Package
 * @subpackage  plg_system_radicalmart
 * @version     __DEPLOY_VERSION__
 * @author      RadicalMart Team - radicalmart.ru
 * @copyright   Copyright (c) 2024 RadicalMart. All rights reserved.
 * @license     GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link        https://radicalmart.ru/
 */

namespace Joomla\Plugin\System\Lesson\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Factory\MVCFactoryAwareTrait;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

class Lesson extends CMSPlugin implements SubscriberInterface
{
	use MVCFactoryAwareTrait;
	use DatabaseAwareTrait;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    bool
	 *
	 * @since  __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onContentPrepareForm' => 'onContentPrepareForm',
			'onContentPrepareData' => 'onContentPrepareData',
		];
	}

	public function onContentPrepareForm(Event $event)
	{
		/** @var Form $form */
		$form = $event->getArgument('0');
		if ($form->getName() === 'com_content.article')
		{
			$form->loadFile(JPATH_PLUGINS . '/system/lesson/forms/com_content.article.xml');
		}

	}

	public function onContentPrepareData(Event $event)
	{
		$context = $event->getArgument('0');
		if ($context !== 'com_content.article')
		{
			return;
		}

		$data = $event->getArgument('1');
		if (is_object($data))
		{
			$data->my_filed_data = 'aasdsd';
		}
		elseif (is_array($data))
		{
			$data['my_filed_data'] = 'aasdsd';
		}
		$event->setArgument('1', $data);
	}
}