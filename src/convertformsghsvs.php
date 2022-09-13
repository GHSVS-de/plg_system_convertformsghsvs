<?php
defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Table\Table;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Form\Form;

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

/*
$subission object:

Joomla\CMS\Object\CMSObject Object
(
    [id] => 4
    [created] => 13.06.2022 20:43
    [modified] => 0000-00-00 00:00:00
    [campaign_id] => 1
    [form_id] => 1
    [visitor_id] => e011f42cf4da70de
    [user_id] => 0
    [params] => Array
        (
            [name] => Testerlinger ghsvs.de
            [email] => illov@web.de
            [url_3] => https://ghsvs-clon-j3.ghsvs.de
        )

    [state] => 1
    [form] => Joomla\CMS\Object\CMSObject Object
        (
            [_errors:protected] => Array
                (
                )

            [created] => 2022-06-13 14:01:53
            [id] => 1
            [name] => Kündigungsformular
            [state] => 1
            [ordering] => 0
            [fields] => Array
                (
                    [fields1] => Array
                        (
                            [key] => 1
                            [type] => text
                            [name] => name
                            [label] => Ihr Name
                            [description] =>
                            [required] => 0
                            [size] =>
                            [value] =>
                            [placeholder] =>
                            [cssclass] =>
                            [inputcssclass] =>
                            [hidelabel] => 0
                            [browserautocomplete] => 0
                            [filter] => html
                            [inputmask] =>
                            [readonly] => 0
                            [minchars] => 0
                            [maxchars] => 0
                            [minwords] => 0
                            [maxwords] => 0
                        )

                    [fields0] => Array
                        (
                            [key] => 0
                            [type] => email
                            [name] => email
                            [label] => Ihre Email-Adresse
                            [description] =>
                            [required] => 0
                            [dnscheck] => 0
                            [size] =>
                            [value] =>
                            [placeholder] =>
                            [cssclass] =>
                            [inputcssclass] =>
                            [hidelabel] => 0
                            [browserautocomplete] => 0
                            [readonly] => 0
                        )

                    [fields3] => Array
                        (
                            [key] => 3
                            [type] => url
                            [name] => url_3
                            [label] => Webseite / URL
                            [description] =>
                            [required] => 0
                            [size] =>
                            [value] =>
                            [placeholder] => z.B. https://www.example.org
                            [cssclass] =>
                            [inputcssclass] =>
                            [hidelabel] => 0
                            [browserautocomplete] => 0
                            [readonly] => 0
                        )

                    [fields2] => Array
                        (
                            [key] => 2
                            [type] => submit
                            [text] => Kündigungswunsch absenden
                            [align] => left
                            [btnstyle] => flat
                            [fontsize] =>
                            [shadow] => 0
                            [bg] =>
                            [textcolor] =>
                            [texthovercolor] => #ffffff
                            [borderradius] => 3
                            [vpadding] => 11
                            [hpadding] => 15
                            [size] =>
                            [cssclass] =>
                            [inputcssclass] =>
                        )

                )

            [autowidth] => auto
            [width] => 500
            [bgcolor] =>
            [bgimage] => 0
            [bgurl] =>
            [bgfile] =>
            [bgrepeat] => no-repeat
            [bgsize] => auto
            [bgposition] => left top
            [text] => Hier haben Sie die Möglichkeit, mich zu informieren, wenn Sie einen mit mir geschlossene Wartungsvertrag vorzeitig kündigen wollen. Selbstverständlich können Sie das auch per Email an mich tun.<br /><br />Bitte beachten Sie, dass mit Absenden des Kündigungsformulars noch keine Prüfung meinerseits stattgefunden hat. Der Vorgang hält lediglich den Absendetermin Ihres Wunsches an mich fest.<br /><br />Bitte machen Sie unten ausreichend Angaben um Ihr Anliegen einem Wartungsvertrag zuordnen zu können ("damit ich weiß, wer Soe sind"), damit ich Sie in der Folge kontaktieren kann.
            [font] =>
            [padding] => 0
            [borderradius] => 0
            [borderstyle] => none
            [bordercolor] =>
            [borderwidth] => 2
            [image] => 0
            [imageurl] =>
            [imagefile] =>
            [imgposition] => img-above
            [imageautowidth] => auto
            [imagewidth] => 200
            [imagesize] => 6
            [imagehposition] => 0
            [imagevposition] => 0
            [imagealt] =>
            [hideimageonmobile] => 0
            [formposition] => form-bottom
            [formsize] => 16
            [formbgcolor] => none
            [labelscolor] =>
            [labelsfontsize] =>
            [labelposition] => top
            [required_indication] => 1
            [inputfontsize] =>
            [inputcolor] =>
            [inputbg] =>
            [inputalign] => left
            [inputbordercolor] =>
            [inputborderradius] => 0
            [inputvpadding] => 10
            [inputhpadding] => 10
            [inputshadow] => 0
            [footer] =>
            [customcss] =>
            [customcode] =>
            [classsuffix] =>
            [honeypot] => 1
            [phpscripts] => Array
                (
                    [formprepare] =>
                    [formdisplay] =>
                    [formprocess] =>
                    [afterformsubmission] =>
                )

            [sendnotifications] => 1
            [emails] => Array
                (
                    [emails0] => Array
                        (
                            [recipient] => {site.email}
                            [subject] => New Submission #{submission.id}: Contact Form
                            [from_name] => {site.name}
                            [from_email] => {site.email}
                            [reply_to] =>
                            [reply_to_name] =>
                            [body] => {all_fields}
                            [attachments] =>
                        )

                )

            [submission_state] => 1
            [campaign] => 1
            [onsuccess] => msg
            [successmsg] => Thanks for contacting us! We will get in touch with you shortly.
            [resetform] => 1
            [hideform] => 1
            [hidetext] => 0
            [successurl] =>
            [passdata] => 0
        )

    [campaign] => Joomla\CMS\Object\CMSObject Object
        (
            [_errors:protected] => Array
                (
                )

            [created] => 0000-00-00 00:00:00
            [id] => 1
            [name] => Demo Campaign
            [state] => 1
            [ordering] => 0
            [service] => 0
            [params] => Array
                (
                )

        )

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
	public function onConvertFormsSubmissionAfterSave($submission)
	{
		# Sende eine Bestätigungs-Email an den Besucher.
		# Draft!!!!!!!!!!
		# Die Email geht aber schon mal raus.

		$collect = new stdClass();

		// Field name versus field label.
		$fieldCollector = [];
		$formFields = $submission->form->fields;

		// By visitor entered data. Array.
		$data = $submission->params;

		$collect->date = $submission->created;
		$collect->identifier = $submission->visitor_id . '-ID-'
			. $submission->id;

		foreach ($fields as $field)
		{
			$fieldcollector[$field['name']] = $field->label;
		}

		foreach ($data as $field => $value)
		{

		}

		$body = "Die an mich übermittelten Daten" . "\n\n" . '{all_fields}';

		$email = [
			'recipient' => $data['email'],
			'subject' => 'New Submission #{submission.id}: '
				. $submission->form->name,
			'from_name' => '{site.name}',
			'from_email' => '{site.email}',
			'reply_to' => '',
			'reply_to_name' => '',
			'body' => $body,
			'attachments' => '',
		];

		$email['body'] = HTMLHelper::_('content.prepare', $email['body']);
		$email = ConvertForms\SmartTags::replace($email, $submission);

		$mailer = new NRFramework\Email($email);

		if (!$mailer->send())
		{
			throw new \Exception($mailer->error);
		}
		file_put_contents(JPATH_SITE . '/cli/onConvertFormsSubmissionAfterSave.txt',
	print_r($collect, true));


	}

     /**
     *  Prepare form.
     *
     *  @param   JForm  $form  The form to be altered.
     *  @param   mixed  $data  The associated data for the form.
     *
     *  @return  boolean
     */
    public function onContentPrepareForm(Form $form, $data)
    {
        if ($this->app->isClient('administrator')) {
        	return;
        }

echo ' 4654sd48sa7d98sD81s8d71dsa <pre>' . print_r($form->getName(), true) . '</pre>';exit;

        // Check we have a valid form context
        $validForms = array(
            "com_convertforms.campaign",
            "com_convertforms.form"
        );

        if (!in_array($form->getName(), $validForms))
        {
            return true;
        }

        // Load ConvertForms plugins
        JPluginHelper::importPlugin('convertforms');
        JPluginHelper::importPlugin('convertformstools');

        // Campaign Forms
        if ($form->getName() == 'com_convertforms.campaign')
        {
            if (!isset($data->service) || !$service = $data->service)
            {
                return true;
            }

            $result = \JFactory::getApplication()->triggerEvent('onConvertFormsCampaignPrepareForm', [$form, $data, $service]);
        }

        // Form Editing Page
        if ($form->getName() == 'com_convertforms.form')
        {
            $result = \JFactory::getApplication()->triggerEvent('onConvertFormsFormPrepareForm', [$form, $data]);
        }

        return true;
    }

}
