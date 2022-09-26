<?php
defined('_JEXEC') or die;

if (version_compare(JVERSION, '4', 'lt'))
{
	JLoader::registerNamespace(
		'Joomla\Plugin\System\ConvertFormsGhsvs',
		__DIR__ . '/src',
		false,
		false,
		'psr4'
	);
}

/*
Siehe auch plugins/convertforms.

Siehe auch plugins/convertformstools.

Um die events zu finden, suche nach "onConvertForms" in den Dateien der gesamten seite.

$this->app->triggerEvent('onConvertFormsFileUpload', [&$destination_file, $tmpData]);

 JFactory::getApplication()->triggerEvent('onConvertFormsSubmissionBeforeSave', [&$data]);

Dieses Event dient der Bearbeitung der Submission-Datas VOR onConvertFormsSubmissionAfterSave
JFactory::getApplication()->triggerEvent('onConvertFormsSubmissionAfterSavePrepare', [&$submission]);

*/

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;
use Joomla\Plugin\System\ConvertFormsGhsvs\Helper\ConvertFormsGhsvsHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Filesystem\Folder;


class PlgSystemConvertFormsGhsvs extends CMSPlugin
{
	/**
	 * Application object
	 *
	 * @var    \Joomla\CMS\Application\CMSApplication
	 * @since  4.0.0
	 */
	protected $app;

	/**
	 * Database driver
	 *
	 * @var    \Joomla\Database\DatabaseInterface
	 * @since  4.0.0
	 */
	protected $db;

	/**
	 * Load plugin language files automatically
	 *
	 * @var    boolean
	 * @since  3.6.3
	 */
	protected $autoloadLanguage = true;

	protected $attachments = [];

	// Contains also [body]
	protected $emails = [];

	// To be set in ConvertForms builder.
	protected $sendnotifications = false;

	protected $attachUploaded = false;

	protected $sendCopy = false;

	protected $debug = false;

	protected $spamWords = [];

	protected $spamWordsReplacer = '[***]';

	/*
	$subission object:

	Siehe https://github.com/GHSVS-de/schraefl.j4.ghsvs.de/issues/3

	https://datenschutz-generator.de/kuendigung-button/

	Schaltfläche „Verträge hier kündigen“ führt auf Formular.

	“Ohne die Angaben der E-Mail-Adresse und der Vertragsnummer, können wir Ihre Kündigung keinem Vertrag rechtswirksam zuordnen“

	“zum frühest möglichen Zeitpunkt”

	Guten Tag!
	Soeben wurde mir ein Kündigungswunsch über die Internetseite ghsvs.de zugesendet.

	Bitte ignorieren und löschen Sie diese automatisierte Rückantwort hier, falls nicht Sie den Kündigungswunsch gesendet haben.

	Andernfalls heben Sie diese Email bitte als Sendebeleg auf. Ich werde mich umgehend an Sie wenden, nachdem ich den Vorgang geprüft habe.

	“Kündigungsbestätigung und Kopie der Kündigungserklärung


	*/

	// Dieses Event dient der Bearbeitung der Submission-Datas VOR onConvertFormsSubmissionAfterSave
	public function onConvertFormsSubmissionAfterSavePrepare(&$submission)
	{
		$this->debug = $this->params->get('debug', 0) === 1;
		$this->sendnotifications = (int) $submission->form->sendnotifications === 1;
		$this->attachUploaded = $this->sendnotifications === true
			&& (int) $this->params->get('attachUploaded', 0) === 1;
		$this->sendCopy = $this->sendnotifications === true
			&& (int) $this->params->get('sendCopy', 0) === 1;
		$this->spamWords = ConvertFormsGhsvsHelper::getSpamWords($this->params->get('spamWords', ''));
		$this->spamWordsReplacer = $this->params->get('spamWordsReplacer', '[***]');

		/*
		Get relevant Upload-Fields. Also vom Besucher im Formular hochgeladene Dateien,
		die im Email-Body verlinkt werden (Standard-Verhalten), aber (mindestens)
		zusätzlich als Attachments angehängt werden sollen.
		*/
		$fields = $submission->form->fields;

		foreach ($fields as $field)
		{
			if ($field['type'] === 'fileupload')
			{
				$name = $field['name'];

				if (!empty($submission->prepared_fields[$name]->value_raw)
					&& is_array($submission->prepared_fields[$name]->value_raw))
				{
					foreach ($submission->prepared_fields[$name]->value_raw as $path)
					{
						// Es wird ein relativer Pfad ohne einleitenden Slash erwartet.
						$path = ltrim($path, '/');

						if ($path && is_file(JPATH_SITE . '/' . $path))
						{
							$this->attachments[] = $path;
						}
					}
				}
			}
		}

		if (count($this->attachments) < 1)
		{
			$this->attachUploaded = false;
		}
		else
		{
			$this->attachments = array_unique($this->attachments);
		}

		// Add comma separated attachments string to eg. emails[emails0][attachments] array.
		if ($this->attachUploaded === true && !empty($submission->form->emails))
		{
			foreach ($submission->form->emails as $key => $email)
			{
				/*
				Im Formularbuilder eingetragene, statische Attachments? In der Pro sind mehrere
				Emails möglich. Je Email kann man 1 Attachment-Feld füllen. Pro Feld können
				kommasepariert mehrere Dateien eingetragen sein. Deshalb das Kommagedöns.
				*/
				if (isset($email['attachments'])
					&& ($attachments = rtrim(trim($email['attachments']), ', ')))
				{
					$submission->form->emails[$key]['attachments'] = $attachments . ','
						. implode(',', $this->attachments);
				}
				else
				{
					$submission->form->emails[$key]['attachments'] = implode(',', $this->attachments);
				}
			}
		}

		ConvertFormsGhsvsHelper::replaceSpamWords($submission, $this->spamWords,
			$this->spamWordsReplacer);

		// Bspw. {page.url} wird ggf. falsch aufgelöst.
		ConvertFormsGhsvsHelper::fixSmartTags($submission);

		$this->emails = $submission->form->emails;

		// Funktioniert. Wird so in Email ausgegeben.
		#$submission->prepared_fields['email']->value='arsch1@ghsvs.de';
		#$submission->prepared_fields['email']->value_html='<a target="_blank" href="mailto:illov@web.de">arsch2@ghsvs.de</a>';
		#$submission->prepared_fields['email']->value_raw='arsch3@ghsvs.de';

		if ($this->debug = true)
		{
			$this->debugOutput($submission);
		}
	}

	public function onConvertFormsSubmissionAfterSave($submission)
	{
		/*
			[emails0] => Array
			(
			[recipient] => {site.email}
			[subject] => Bewerbung. {site.name} von {field.name}
			[from_name] => {site.name}
			[from_email] => {site.email}
			[reply_to] => {field.email}
			[reply_to_name] => {field.name}
			[body] => Guten Tag!<br /><br />Soeben wurden Daten von {field.name} über das Formular "Jetzt bewerben" übermittelt ({url.path}).<br /><br />Übertragungs-ID: {submission.id}.<br />Übertragungs-Datum: {submission.date}.<br /><br />Wenn vom Besucher Dateien hochgeladen wurden, finden Sie diese zusätzlich im Anhang dieser Email.<br /><br /><strong>Eingegebene Daten:</strong><br /><br />{all_fields}
			[attachments] =>
			)
		*/
		if ($this->sendCopy === true && !empty($this->emails['emails0'])
			// Visitor-Email:
			&& !empty($submission->prepared_fields['email']->value_raw)
		){
			// By visitor entered data. Array.
			$data = $submission->params;
			$body = "<strong>Diese Nachricht bestätigt, dass Ihr Formular auf Webseite {url.path} übermittelt wurde. Es folgt eine Kopie der an " . $this->emails['emails0']['recipient'] . " gesendeten Email:</strong><br /><br />";

			$body .= $this->emails['emails0']['body'];
			$subject = 'Sendebestätigung: ' . $this->emails['emails0']['subject'];
			$recipient = $submission->prepared_fields['email']->value_raw;
			$from_name = $this->emails['emails0']['from_name'];
			$reply_to_name = $this->emails['emails0']['from_name'];
			// Email des Original-Formularempfängers.
			$from_email = $this->emails['emails0']['recipient'];
			$reply_to = $this->emails['emails0']['recipient'];
			$attachments = $this->attachUploaded === true ? implode(',', $this->attachments) : '';

			$email = [
				'recipient' => $recipient,
				'subject' => $subject,
				'from_name' => $from_name,
				'from_email' => $from_email,
				'reply_to' => $reply_to,
				'reply_to_name' => $reply_to_name,
				'body' => $body,
				'attachments' => $attachments,
			];

			// Protect against email cloaking.
			$email['body'] = str_replace('@', '[**at**]', $email['body']);
			$email['body'] = HTMLHelper::_('content.prepare', $email['body']);
			$email['body'] = str_replace('[**at**]', '@', $email['body']);
			$email['body'] = ConvertFormsGhsvsHelper::fixSmartTags($submission, $email['body']);
			$email = ConvertForms\SmartTags::replace($email, $submission);
			$mailer = new NRFramework\Email($email);

			if (!$mailer->send())
			{
				throw new \Exception($mailer->error);
			}
		}
	}

	/** NUTZLOS IM FE!!!!!!!!
	*  Prepare form.
	*
	*  @param   JForm  $form  The form to be altered.
	*  @param   mixed  $data  The associated data for the form.
	*
	*  @return  boolean
	*/
	public function onContentPrepareForm(Form $form, $data)
	{
	}
	public function onContentPrepareData($context, $data)
	{
	}

	public function onBeforeCompileHead()
	{
	}

	/** NUTZLOS IM FE!!!!!!!!
	*  Add plugin fields to the form
	*
	*  @param   JForm   $form
	*  @param   object  $data
	*
	*  @return  boolean
	*/
	public function onConvertFormsFormPrepareForm($form, $data)
	{
	}

	/*
	Weil Convert form als Modul falsches {url.path} auflöst
	Siehe https://github.com/GHSVS-de/plg_system_convertformsghsvs/discussions/5.
	*/
	public function onConvertFormsFieldBeforeRender($field, $fieldForm)
	{
		if ($field->name === 'url_path')
		{
			$field->value = base64_encode(Uri::getInstance()->toString());
		}
	}

	/*
	Load CSS.
	*/
	public function onConvertFormsFormBeforeRender($data)
	{
		if ($this->app->isClient('administrator') || empty($data)) {
			return;
		}

		ConvertFormsGhsvsHelper::loadCss($data);
		return true;
	}

	/*
	Ist hier besser aufgehoen als im Helper. Wegen protected Variablen.
	*/
	protected function debugOutput($submission)
	{
		$debugPath = rtrim($this->app->get('cache_path', JPATH_CACHE), '/');

		if (is_writable($debugPath))
		{
			$debugPath .= '/plg_system_convertformsghsvs';

			if (!is_dir($debugPath ))
			{
				Folder::create($debugPath);
			}

			$access = "<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n"
			. "</IfModule>\n<IfModule mod_authz_core.c>\n<RequireAll>\n"
			. "Require all denied\n</RequireAll>\n</IfModule>";
			file_put_contents($debugPath . '/.htaccess', $access);

			// Add file prefix.
			$debugPath .= '/form_id-' . $submission->form_id .'-';

			$debugFile = $debugPath . 'onConvertFormsSubmissionAfterSavePrepare.txt';
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
						$debugFile2 = $debugPath . "prepared_fields_$key.txt";
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

			$toDo = ['spamWords', 'attachments', 'emails'];

			foreach ($toDo as $key)
			{
				$debugFile = $debugPath . $key . '.txt';
				file_put_contents($debugFile, $key . ':' . "\n" . print_r($this->$key, true));
			}
		}
	}
}
