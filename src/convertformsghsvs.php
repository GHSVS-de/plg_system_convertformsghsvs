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
Um die events zu finden, suche nach "onConvertForms" in den Dateien der gesamten seite.

Siehe https://www.tassos.gr/joomla-extensions/convert-forms/docs/developers-php-events

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
use Joomla\CMS\Language\Text;

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

	// Want a protected upload folder.
	protected $uploadPathRel = 'media/com_convertforms/uploads/ghsvs';

	// Man kann z.B. die Submission-Id ändern. Für Uploads wenig hilfreich, außer dass der tmp/-Pfad bekannt gemacht wird.
	public function onConvertFormsSubmissionBeforeSave(&$data)
	{
	}

	/*
	Fires by the File Upload field after the uploaded file has been moved into its
	destination folder. With this event, you can rename the file, move it to
	another folder, resize an image or even upload it to a cloud storage service.

	&$filepath: (string) The absolute file path where the file is stored. Passed by reference.
	$data: (array) The form submitted data.

	*/
	// Man erhält den ABSOLUTEN Pfad unter dem die Datei bereits gespeichert ist!
	public function onConvertFormsFileUpload(&$filepath, $data)
	{
		if ($this->params->get('protectUploaded', 1) === 1) {
			$uploadPath = JPATH_SITE . '/' . $this->uploadPathRel;
			$htpasswd = $uploadPath . '/.htpasswd';
			$newFilepath = $uploadPath . '/form-' . $data['form_id'] . '_' . basename($filepath);

			if (!is_dir($uploadPath)) {
				Folder::create($uploadPath);
			}

			if (!is_file($htpasswd)) {
				file_put_contents($htpasswd, '');
			}

			if (!is_file($uploadPath . '/.htaccess')) {
				$htaccess = <<<HTACCESS
AuthUserFile $htpasswd
AuthGroupFile /dev/null
AuthName 'please enter access data'
AuthType Basic
require valid-user
HTACCESS;
				file_put_contents($uploadPath . '/.htaccess', $htaccess);
			}

			$filepath = NRFramework\File::move($filepath, $newFilepath);
			$cleanoutIntervall = ((int) $this->params->get('cleanoutIntervall', 30))
				* 24 * 60 * 60;

			// -1:never, 0:always, others:x days
			if ($cleanoutIntervall > -1) {
				$now = time();
				$cleanoutLog = $uploadPath . '/cleanoutLog.txt';

				if ($cleanoutIntervall === 0) {
					$nextCleanoutTime = $now;
				}
				elseif (is_file($cleanoutLog)) {
					$nextCleanoutTime = filemtime($cleanoutLog) + $cleanoutIntervall;
				}
				else {
					$nextCleanoutTime = $now + $cleanoutIntervall;
					file_put_contents($cleanoutLog, '');
				}

				if ($nextCleanoutTime <= $now) {
					file_put_contents($cleanoutLog, '');

					foreach(Folder::files(
						$uploadPath,
						$filter = '.',
						$recurse = true,
						$full = true,
						$exclude = ['.htaccess', '.htpasswd', 'cleanoutLog.txt']) as $File
					) {
						$fileAge = filemtime($File);

						if (($fileAge + $cleanoutIntervall) < $now) {
							unlink($File);
						}
					}
				}
			}
		}
	}

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

		foreach ($fields as $fieldKey => $field)
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

		if (!empty($submission->form->emails)) {

		// Add comma separated attachments string to eg. emails[emails0][attachments] array.
			if ($this->attachUploaded === true)
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

			if (!empty($submission->prepared_fields['email_bcc']->value_raw))
			{
				$submission->form->emails['emails0']['bcc'] = base64_decode(
					$submission->prepared_fields['email_bcc']->value_raw);
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

	public function onConvertFormsFieldBeforeRender($field, $fieldForm)
	{
	/*
		Eigentlich war die Idee, ähnlich ECC+ Zahlen gelegentlich als Worte auszugeben.
		Zu diesem Zeitpunkt kann man die Werte in 'question' bedenkenlos überschreiben.
		Das ist mir aber derzeit zu nervig wegen ini-Sprachdateien.
		Außerdem kann ECC+ mittlerweile ConvertForms.
		*/
		if ($field->type === 'captcha' && $this->params->get('numbersAsWords', 0) === 1)
		{
			if ($field->question['comparator'] === '+' && random_int(1, 3) === 1)
			{
				$field->question['comparator'] = Text::_('PLG_SYSTEM_EASYCALCCHECKPLUS_PLUS');
			}

			if ($field->question['comparator'] === '-' && random_int(1, 3) === 1)
			{
				$field->question['comparator'] = Text::_('PLG_SYSTEM_EASYCALCCHECKPLUS_MINUS');
			}
		}

		if ($field->type === 'hidden') {
		/*
	Weil Convert form als Modul falsches {url.path} auflöst
	Siehe https://github.com/GHSVS-de/plg_system_convertformsghsvs/discussions/5.
	*/
		if ($field->name === 'url_path')
		{
			$field->value = base64_encode(Uri::getInstance()->toString());
		}

			/*
			Eine BCC-Email über hidden field email_bcc
			*/
			if ($field->name === 'email_bcc' && ($email_bcc = trim($field->value)))
			{
				$field->value = base64_encode($field->value);
			}
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

		if (is_writable($debugPath)) {
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
