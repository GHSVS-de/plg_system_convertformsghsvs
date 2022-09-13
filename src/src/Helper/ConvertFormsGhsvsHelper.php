<?php

namespace Joomla\Plugin\System\ConvertFormsGhsvs\Helper;

\defined('JPATH_BASE') or die;

use Joomla\CMS\Filter\InputFilter;
use Joomla\Registry\Registry;
use Joomla\CMS\Filesystem\Folder;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

class ConvertFormsGhsvsHelper
{
	protected static $loaded = [];

	protected static $basepath = 'plg_system_convertformsghsvs';

	protected static $isJ3 = true;

	protected static $wa = null;

	protected static function init()
	{
		self::$isJ3 = version_compare(JVERSION, '4', 'lt');
	}

	public static function loadCSS($app, $db)
	{
		$jinput = $app->input;
		$wa = self::getWa();
		$weight = 200;
		$version = self::getMediaVersion();

		if ($wa)
		{
			$waName = 'plg_system_convertformsghsvs.override.template';
			$wa->getAsset('style', $waName)->setOption('weight', ++$weight);
			$wa->useStyle($waName);
		}
		else
		{
			HTMLHelper::_('stylesheet', 'plg_system_convertformsghsvs.css',
				['relative' => true, 'version' => $version]);
		}

		if ($jinput->get('option', '') === 'com_convertforms' && $jinput->get('view', '') === 'form')
		{
			if (($form_id = (int) $app->getMenu()->getActive()->getParams()->get('form_id', 0)))
			{
				$query = $db->getQuery(true)
					->select($db->qn('params'))
					->from($db->qn('#__convertforms'))
					->where($db->qn('id') . ' = ' . $form_id);
				$db->setQuery($query);
				$params = $db->loadResult();

				if ($params && strpos($params, '_css') !== false) {
					$params = new Registry($params);
					$classsuffix = $params->get('classsuffix');

					if (strpos($classsuffix, '_css') !== false) {
						foreach (array_map('trim', explode(' ', $classsuffix)) as $file)
						{
							if (substr($file, -4) === '_css')
							{
								$file = str_replace('_', '.', $file);

								if ($wa)
								{
									$waName = 'plg_system_convertformsghsvs.' . str_replace('/', '.', $file);
									$wa->registerStyle(
									$waName,
									$file,
									['version' => $version, 'weight' => ++$weight],
									)->useStyle($waName);
								}
								else
								{
									HTMLHelper::_('stylesheet', $file,
										['relative' => true, 'version' => self::getMediaVersion(), 'pathOnly' => false]);
								}
							}
						}
					}
				}
			}
		}
	}

	public static function getMediaVersion()
	{
		if (!isset(self::$loaded[__METHOD__]))
		{
			self::$loaded[__METHOD__] = json_decode(file_get_contents(
				__DIR__ . '/../../package.json'
			))->version;
		}

		return self::$loaded[__METHOD__];
	}

	/*
	csp_nonce of HTTP Header plugin
	*/
	public static function getNonce($app)
	{
		if (!isset(self::$loaded[__METHOD__]))
		{
			if (self::$loaded[__METHOD__] = $app->get('csp_nonce', ''))
			{
				self::$loaded[__METHOD__] = ' nonce="' . self::$loaded[__METHOD__] . '"';
			}
		}

		return self::$loaded[__METHOD__];
	}

	/*
	At the moment just for adding $version in some cases.
	*/
	public static function cloneAndUseWamAsset(String $type, String $wamName, array $options)
	{
		$wa = self::getWa();
		$war = $wa->getRegistry();
		$asset = $war->get($type, $wamName);
		$war->remove($type, $wamName);
		$war->add(
			$type,
			$war->createAsset(
				$wamName,
				$asset->getUri(false),
				array_merge($asset->getOptions(), $options),
				// $asset->getAttributes(),
				// $asset->getDependencies(),
			)
		);
		$wa->useAsset($type, $wamName);
	}

	public static function getWa()
	{
		self::init();

		if (self::$isJ3 === false && empty(self::$wa))
		{
			self::$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
			// self::$wa->getRegistry()->addExtensionRegistryFile('plg_system_convertformsghsvs');
			self::$wa->getRegistry()->addRegistryFile('plugins/plg_system_convertformsghsvs/joomla.asset.json');
		}

		return self::$wa;
	}
}
