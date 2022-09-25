<?php

namespace Joomla\Plugin\System\ConvertFormsGhsvs\Helper;

\defined('JPATH_BASE') or die;

use Joomla\Registry\Registry;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\Folder;

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

	/*
	Load CSS. See https://github.com/GHSVS-de/plg_system_convertformsghsvs/discussions/1.
	*/
	public static function loadCss($data)
	{
		$wa = self::getWa();
		$weight = 200;
		$version = self::getMediaVersion();

		// To reverse the loading order, if loadCSS is active in ConvertForms options.
		$compoParams = ComponentHelper::getParams('com_convertforms');

		if ($compoParams->get('loadCSS', true))
		{
			if ($wa)
			{
				$waName = self::$basepath . '.com_convertforms.overrule';
				$wa->getAsset('style', $waName)->setOption('weight', ++$weight);
				$wa->useStyle($waName);
			}
			else
			{
				HTMLHelper::_('stylesheet', 'com_convertforms/convertforms.css',
					['relative' => true, 'version' => $version]);
			}
		}

		if ($wa)
		{
			$waName = self::$basepath . '.override.template';
			$wa->getAsset('style', $waName)->setOption('weight', ++$weight);
			$wa->useStyle($waName);
		}
		else
		{
			HTMLHelper::_('stylesheet', self::$basepath . '.css',
				['relative' => true, 'version' => $version]);
		}

		if (!empty($data['params']['classsuffix'])
			&& ($classsuffix = trim($data['params']['classsuffix']))
			&& strpos($classsuffix, '_css') !== false)
		{
			foreach (array_map('trim', explode(' ', $classsuffix)) as $suffix)
			{
				if (substr($suffix, -4) === '_css')
				{
					$file = str_replace('_', '.', $suffix);

					if ($wa)
					{
						$waName = self::$basepath . '.' . $suffix;
						$wa->registerStyle($waName, $file,
						['version' => $version, 'weight' => ++$weight],
						)->useStyle($waName);
					}
					else
					{
						HTMLHelper::_('stylesheet', $file,
							['relative' => true, 'version' => $version]);
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
			self::$wa->getRegistry()->addRegistryFile('plugins/system/convertformsghsvs/joomla.asset.json');
		}

		return self::$wa;
	}

	public static function fixSmartTags(&$submission, $string = '')
	{
		/*
		Bug in ConvertForms, das {url.path} gelegentlich falsch auflöst unter Joomla 4 als Modul.
		Zu Unsinn wie https://example.org/component/convertforms
		*/
		if (!empty($submission->prepared_fields['url_path']->value_raw))
		{
			$uri = base64_decode($submission->prepared_fields['url_path']->value_raw);

			// Weil ConvertForms auch das in Email ausgibt, obwohl hidden. Gnaaah!
			$submission->prepared_fields['url_path']->value_html = $uri;

			foreach ($submission->form->emails as $key => $email)
			{
				if (isset($email['body']))
				{
					$submission->form->emails[$key]['body'] = str_replace('{url.path}', $uri, $submission->form->emails[$key]['body']);
				}
			}

			if (!empty($string))
			{
				$string = str_replace('{url.path}', $uri, $string);
				return $string;
			}
		}
		//return $submission;
	}

	public static function debug($submission, $app)
	{
		$debugPath = rtrim($app->get('tmp_path', JPATH_SITE . '/tmp'), '/');

		if (is_writable($debugPath))
		{
			$debugPath = $debugPath . '/plg_system_convertformsghsvs';

			if (!is_dir($debugPath ))
			{
				Folder::create($debugPath);
			}

			$debugFile = $debugPath . '/onConvertFormsSubmissionAfterSavePrepare.txt';

			file_put_contents($debugFile, 'Start onConvertFormsSubmissionAfterSavePrepare' . "\n\n");

			foreach ($submission as $key => $value)
			{
				if ($key !== 'prepared_fields')
				{
					file_put_contents($debugFile,
						"\n----$key\n" . print_r($key, true) . "\n", FILE_APPEND);
					file_put_contents($debugFile,
						print_r($value, true) . "\n----\n", FILE_APPEND);
				}
				else
				{
					foreach ($submission->prepared_fields as $key => $value)
					{
						$debugFile2 = $debugPath . "/prepared_fields_$key.txt";
						file_put_contents($debugFile2,
							"\n----prepared_fields_$key STARTS: \n" . "----\n");

						foreach ($submission->prepared_fields[$key] as $key2 => $value2)
						{
							// Das ist *RECURSION*-Schrott.
							if ($key2 === 'class')
							{
								continue;
							}

							file_put_contents($debugFile2,
								"\n----$key::$key2\n" . print_r($value2, true) . "\n----\n", FILE_APPEND);
						}
					}
				}
			}
		}
	}
}
