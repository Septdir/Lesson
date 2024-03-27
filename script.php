<?php
/*
 * @package     Joomla Lesson Package
 * @subpackage  pkg_lesson
 * @version    __DEPLOY_VERSION__
 * @author     Igor Berdicheskiy - septdir.ru
 * @copyright  Copyright (c) 2013 - 2024 Igor Berdichevksiy. All rights reserved.
 * @license    GNU/GPL license: https://www.gnu.org/copyleft/gpl.html
 * @link       https://septdir.ru
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Version;
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

return new class () implements ServiceProviderInterface {
	public function register(Container $container)
	{
		$container->set(InstallerScriptInterface::class, new class ($container->get(AdministratorApplication::class)) implements InstallerScriptInterface {
			/**
			 * The application object
			 *
			 * @var  AdministratorApplication
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected AdministratorApplication $app;

			/**
			 * The Database object.
			 *
			 * @var   DatabaseDriver
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected DatabaseDriver $db;

			/**
			 * Minimum Joomla version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected string $minimumJoomla = '4.2';

			/**
			 * Minimum PHP version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected string $minimumPhp = '7.4';

			/**
			 * Minimum MySQL version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected string $minimumMySQL = '8.0.21';

			/**
			 * Minimum MariaDb version required to install the extension.
			 *
			 * @var  string
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected string $minimumMariaDb = '10.4.1';

			/**
			 * Update methods.
			 *
			 * @var  array
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected array $updateMethods = [];

			/**
			 * Constructor.
			 *
			 * @param   AdministratorApplication  $app  The application object.
			 *
			 * @since __DEPLOY_VERSION__
			 */
			public function __construct(AdministratorApplication $app)
			{
				$this->app = $app;
				$this->db  = Factory::getContainer()->get('DatabaseDriver');
			}

			/**
			 * Function called after the extension is installed.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function install(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called after the extension is updated.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function update(InstallerAdapter $adapter): bool
			{
				// Refresh media version
				(new Version())->refreshMediaVersion();

				return true;
			}

			/**
			 * Function called after the extension is uninstalled.
			 *
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function uninstall(InstallerAdapter $adapter): bool
			{
				return true;
			}

			/**
			 * Function called before extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function preflight(string $type, InstallerAdapter $adapter): bool
			{
				// Check compatible
				if (!$this->checkCompatible())
				{
					return false;
				}

				return true;
			}

			/**
			 * Function called after extension installation/update/removal procedure commences.
			 *
			 * @param   string            $type     The type of change (install or discover_install, update, uninstall)
			 * @param   InstallerAdapter  $adapter  The adapter calling this method
			 *
			 * @return  boolean  True on success
			 *
			 * @since   __DEPLOY_VERSION__
			 */
			public function postflight(string $type, InstallerAdapter $adapter): bool
			{
				if ($type !== 'uninstall')
				{
					// Run updates script
					if ($type === 'update')
					{
						foreach ($this->updateMethods as $method)
						{
							if (method_exists($this, $method))
							{
								$this->$method($adapter);
							}
						}
					}
				}

				return true;
			}

			/**
			 * Method to check compatible.
			 *
			 * @throws  \Exception
			 *
			 * @return  bool True on success, False on failure.
			 *
			 * @since  __DEPLOY_VERSION__
			 */
			protected function checkCompatible(): bool
			{
				$app = Factory::getApplication();

				// Check joomla version
				if (!(new Version())->isCompatible($this->minimumJoomla))
				{
					$app->enqueueMessage(Text::sprintf('PKG_LESSON_ERROR_COMPATIBLE_JOOMLA', $this->minimumJoomla),
						'error');

					return false;
				}

				// Check PHP
				if (!(version_compare(PHP_VERSION, $this->minimumPhp) >= 0))
				{
					$app->enqueueMessage(Text::sprintf('PKG_LESSON_ERROR_COMPATIBLE_PHP', $this->minimumPhp),
						'error');

					return false;
				}

				// Check database version
				$db            = $this->db;
				$serverType    = $db->getServerType();
				$serverVersion = $db->getVersion();
				if ($serverType == 'mysql' && stripos($serverVersion, 'mariadb') !== false)
				{
					$serverVersion = preg_replace('/^5\.5\.5-/', '', $serverVersion);

					if (!(version_compare($serverVersion, $this->minimumMariaDb) >= 0))
					{
						$app->enqueueMessage(Text::sprintf('PKG_LESSON_ERROR_COMPATIBLE_DATABASE',
							$this->minimumMySQL, $this->minimumMariaDb), 'error');

						return false;
					}
				}
				elseif ($serverType == 'mysql' && !(version_compare($serverVersion, $this->minimumMySQL) >= 0))
				{
					$app->enqueueMessage(Text::sprintf('PKG_LESSON_ERROR_COMPATIBLE_DATABASE',
						$this->minimumMySQL, $this->minimumMariaDb), 'error');

					return false;
				}

				return true;
			}
		});
	}
};
