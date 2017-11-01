<?php

require_once 'softcreditnotify.civix.php';

function softcreditnotify_civicrm_tokens(&$tokens) {

    $tokens['to'] = array(
        'to.display_name' => ts("To: Display Name"),
        'to.email' => ts("To: Email"),
        'to.last_name' => ts("To: Last Name"),
        'to.first_name' => ts("To: First Name"),
        'to.id' => ts("To: ID"),
    );
    $tokens['donor'] = array(
        'donor.display_name' => ts("Donor: Display Name"),
        'donor.email' => ts("Donor: Email"),
        'donor.last_name' => ts("Donor: Last Name"),
        'donor.first_name' => ts("Donor: First Name"),
        'donor.id' => ts("Donor: ID"),
        'donor.tribute_name' => ts("Donor: Tribute Name"),
    );
    $tokens['honoree'] = array(
        'honoree.display_name' => ts("Honoree: Display Name"),
        'honoree.email' => ts("Honoree: Email"),
        'honoree.last_name' => ts("Honoree: Last Name"),
        'honoree.first_name' => ts("Honoree: First Name"),
        'honoree.id' => ts("Honoree: ID"),
    );
    $tokens['acknowledged'] = array(
        'acknowledged.display_name' => ts("Acknowledged: Display Name"),
        'acknowledged.email' => ts("Acknowledged: Email"),
        'acknowledged.last_name' => ts("Acknowledged: Last Name"),
        'acknowledged.first_name' => ts("Acknowledged: First Name"),
        'acknowledged.id' => ts("Acknowledged: ID"),
    );
    $tokens['contribution'] = array(
        'contribution.amount' => ts("Contribution: Amount"),
        'contribution.id' => ts("Contribution: ID"),
        'contribution.type' => ts("Contribution: Type"),
    );
}

function softcreditnotify_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {

    if (array_key_exists('donor', $tokens) || array_key_exists('to', $tokens) || array_key_exists('ackMail', $tokens)) {
        $session =& CRM_Core_Session::singleton();
        $tplParams = $session->get('tplParams');
        $donor = array();
        $honoree = array();
        $notified = array();
        $to = array();
        $contribution = array();
        foreach ($tplParams['donor'] as $key => $value) {
            $newkey = "donor." . $key;
            $donor[$newkey] = $value;
        }
        if (!empty($tplParams['honoree'])) {
            foreach ($tplParams['honoree'] as $key => $value) {
                $newkey = "honoree." . $key;
                $honoree[$newkey] = $value;
            }
        }
        if (!empty($tplParams['acknowledged'])) {
            foreach ($tplParams['acknowledged'] as $key => $value) {
                $newkey = "acknowledged." . $key;
                $notified[$newkey] = $value;
            }
        }
        if (!empty($tplParams['contribution'])) {
            foreach ($tplParams['contribution'] as $key => $value) {
                $newkey = "contribution." . $key;
                $contribution[$newkey] = $value;
            }
        }
        if (!empty($tplParams['to'])) {
            foreach ($tplParams['to'] as $key => $value) {
                $newkey = "to." . $key;
                $to[$newkey] = $value;
            }
        }

        foreach ($cids as $cid) {
            $values[$cid] = empty($values[$cid]) ? $donor : $values[$cid] + $donor;
            $values[$cid] = empty($values[$cid]) ? $donor : $values[$cid] + $honoree;
            $values[$cid] = empty($values[$cid]) ? $donor : $values[$cid] + $notified;
            $values[$cid] = empty($values[$cid]) ? $donor : $values[$cid] + $contribution;
            $values[$cid] = empty($values[$cid]) ? $donor : $values[$cid] + $to;
        }
    }
}

function softcreditnotify_civicrm_buildForm($formName, &$form) {

    if ($formName == "CRM_Contribute_Form_ContributionPage_Settings") {
        $form->add('checkbox', 'notify_active', ts('Notify Honoree/Acknowlegde of Contribution?'));
        $form->add('checkbox', 'notify_honoree_of_honor', ts('Notify Honoree of in Honor Soft Credit'));
        $form->add('checkbox', 'notify_honoree_of_memory', ts('Notify Honoree of in Memory Soft Credit'));
        $form->add('checkbox', 'notify_ack_of_honor', ts('Notify Acknowledged of in Honor Soft Credit'));
        $form->add('checkbox', 'notify_ack_of_memory', ts('Notify Acknowledged of in Memory Of Soft Credit'));
        $form->add('text', 'subject', ts('Message Subject'),
            CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'subject'), false
        );
        CRM_Mailing_BAO_Mailing::commonCompose($form);
        $templatePath = realpath(dirname(__FILE__) . "/templates");
        CRM_Core_Region::instance('form-body')->add(array(
            'template' => "{$templatePath}/settings.tpl"
        ));
        $pageId = $form->getVar('_id');
        if ($pageId) {
            $sql = "SELECT * FROM civicrm_soft_notify WHERE contribution_page = {$pageId}";
            $dao = CRM_Core_DAO::executeQuery($sql);
            if ($dao->fetch()) {
                $defaults['template'] = $dao->template_id;
                $defaults['notify_honoree_of_honor'] = $dao->notify_honoree_of_honor;
                $defaults['notify_honoree_of_memory'] = $dao->notify_honoree_of_memory;
                $defaults['notify_ack_of_honor'] = $dao->notify_ack_of_honor;
                $defaults['notify_ack_of_memory'] = $dao->notify_ack_of_memory;
                $defaults['notify_active'] = $dao->is_active;
                $messageTemplate = new CRM_Core_DAO_MessageTemplate();
                $messageTemplate->id = $defaults['template'];
                $messageTemplate->selectAdd();
                $messageTemplate->selectAdd('msg_text, msg_html, msg_subject');
                $messageTemplate->find(true);
                $defaults['subject'] = $messageTemplate->msg_subject;
                $defaults['text_message'] = $messageTemplate->msg_text;
                $html_message = $messageTemplate->msg_html;
                $text_message = $messageTemplate->msg_text;
                $html = $form->getElement('html_message');
                $html->setValue($html_message);
                $text = $form->getElement('text_message');
                $text->setValue($text_message);
                $form->setDefaults($defaults);
            }
        }
    }

}

function softcreditnotify_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors)
{
    if ($formName == "CRM_Contribute_Form_ContributionPage_Settings") {
        if (!array_key_exists('acknowledged_is_active', $fields)) {
            if (strpos($fields['subject'], '{acknowledged') !== false) {
                $errors['acknowledged_is_active'] = ts('You are using acknowledged tokens but the acknowledged profile section is not enabled.');
            }
            if (strpos($fields['html_message'], '{acknowledged') !== false) {
                $errors['acknowledged_is_active'] = ts('You are using acknowledged tokens but the acknowledged profile section is not enabled.');
            }
            if (strpos($fields['text_message'], '{acknowledged') !== false) {
                $errors['acknowledged_is_active'] = ts('You are using acknowledged tokens but the acknowledged profile section is not enabled.');
            }
        }
    }
}

function softcreditnotify_civicrm_postProcess($formName, &$form)
{
    if ($formName == "CRM_Contribute_Form_ContributionPage_Settings") {
        $params = $form->_submitValues;
        $text_message = $params['text_message'];
        $subject = $params['subject'];
        $html_message = str_replace('%7B', '{', str_replace('%7D', '}', $params['html_message']));
        $tplName = $params['saveTemplateName'];
        $tplParams = array(
            'msg_text' => $text_message,
            'msg_html' => $html_message,
            'msg_subject' => $subject,
            'is_active' => true,
        );
        if (!empty($params['saveTemplate'])) {
            //Create new template
            $tplParams['msg_title'] = $params['saveTemplateName'];
            $msgTemplate = CRM_Core_BAO_MessageTemplate::add($tplParams);
        } elseif (!empty($params['updateTemplate'])) {
            //Edit existing template
            $tplParams['id'] = $params['template'];
            $msgTemplate = CRM_Core_BAO_MessageTemplate::add($tplParams);
        }
        /*** Put entry into table ***/
        $tempId = $params['template'];
        if (strlen($params['template']) < 1) {
            $tempId = "0";
        }

        $pageId = $form->getVar('_id');
        if (isset($params['notify_active'])) {
            $active = $params['notify_active'];
        } else {
            $active = "0";
        }
        $defaultSettings = array(
            'notify_honoree_of_honor' => 0,
            'notify_honoree_of_memory' => 0,
            'notify_ack_of_honor' => 0,
            'notify_ack_of_memory' => 0,
        );
        $newArray = array_replace($defaultSettings, $params);
        $nhoh = $newArray['notify_honoree_of_honor'];
        $nhom = $newArray['notify_honoree_of_memory'];
        $nkoh = $newArray['notify_ack_of_honor'];
        $nkom = $newArray['notify_ack_of_memory'];
        $sql = "SELECT contribution_page FROM civicrm_soft_notify WHERE contribution_page = {$pageId}";
        $dao = CRM_Core_DAO::executeQuery($sql);
        if ($dao->fetch()) {
            $sql = "UPDATE civicrm_soft_notify SET is_active = {$active}, template_id = {$tempId}, notify_honoree_of_honor = {$nhoh}, notify_honoree_of_memory = {$nhom}, notify_ack_of_honor = {$nkoh}, notify_ack_of_memory = {$nkom} WHERE contribution_page = {$pageId}";
            CRM_Core_DAO::executeQuery($sql);
        } else {
            $sql = "INSERT INTO civicrm_soft_notify (`is_active`, `template_id`, `contribution_page`, `notify_honoree_of_honor`, `notify_honoree_of_memory`, `notify_ack_of_honor`, `notify_ack_of_memory`) VALUES ('1', {$tempId}, {$pageId}, {$nhoh}, {$nhom}, {$nkoh}, {$nkom})";
            CRM_Core_DAO::executeQuery($sql);
        }
    }

    if ($formName == "CRM_Contribute_Form_Contribution_Confirm") {
        if ($form->getVar('_honor_block_is_active') == 1) {

            // Get Settings //
            $params = $form->_params;

            //check if there is any duplicate contact
            $profileContactType = CRM_Core_BAO_UFGroup::getContactType($params['mail_profile_id']);
            $dedupeParams = CRM_Dedupe_Finder::formatParams($params['ack'], $profileContactType);
            $dedupeParams['check_permission'] = FALSE;
            $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, $profileContactType);
            if(count($ids)) {
                $mailProfileId = CRM_Utils_Array::value(0, $ids);
            }

            $acknowledgeMailId = CRM_Contact_BAO_Contact::createProfileContact(
                $params['ack'], CRM_Core_DAO::$_nullArray,
                $mailProfileId, NULL,
                $params['mail_profile_id']
            );

            $session = CRM_Core_Session::singleton();
            $user = $session->get('userID');

            $pageID = $params['contributionPageID'];
            $sql = "SELECT * FROM civicrm_soft_notify WHERE contribution_page = {$pageID}";
            $dao = CRM_Core_DAO::executeQuery($sql);
            if ($dao->fetch()) {
                $templateID = $dao->template_id;
                $nhoh = $dao->notify_honoree_of_honor;
                $nhom = $dao->notify_honoree_of_memory;
                $nnoh = $dao->notify_ack_of_honor;
                $nnom = $dao->notify_ack_of_memory;
                $donorID = $form->_contactID;
                /*** Make token params ***/
                // Get Donor Info
                if ($donorID > 0) {
                    $donor = civicrm_api3('Contact', 'getsingle', array('sequential' => 1, 'id' => $donorID));
                    foreach ($params as $key => $value) {
                        $keyParts = explode("-", $key);
                        if ($keyParts[0] == "email") {
                            $donor['email'] = $value;
                        }
                    }
                    if (array_key_exists('tribute', $params)) {
                        $donor['tribute_name'] = $params['tribute'];
                    } else {
                        $donor['tribute_name'] = $donor['display_name'];
                    }
                }

                // Get Honoored Info
                if (!empty($params['honor']) && !empty($params['honor']['first_name'])) {
                    $honorParams = array(
                        'sequential' => 1,
                        'first_name' => $params['honor']['first_name'],
                        'last_name' => $params['honor']['last_name']
                    );
                    $honorAPI = civicrm_api3('Contact', 'get', $honorParams);
                    $honoree = $honorAPI['values'][0];
                }
                // Get acknowledged Info
                if ($params['acknowledge_active'] == 1 && !empty($params['acknowledge'])) {
                    $dedupeParams = CRM_Dedupe_Finder::formatParams($params['acknowledge'], 'Individual');
                    $dedupeParams['check_permission'] = false;
                    $ids = CRM_Dedupe_Finder::dupesByParams($dedupeParams, 'Individual');
                    if (!empty($ids)) {
                        $notifyID = $ids[0];
                        $notified = civicrm_api3('Contact', 'getsingle', array('sequential' => 1, 'id' => $notifyID));
                        foreach ($params['acknowledge'] as $key => $value) {
                            $keyParts = explode("-", $key);
                            if ($keyParts[0] == "email") {
                                $notified['email'] = $value;
                            }
                        }
                    }
                }
                // Make contribution params
                $con_id = $form->_contributionID;
                $contribution = array(
                    'amount' => $params['amount'],
                    'id' => $con_id,
                );
                if ($params['soft_credit_type_id'] == 1) {
                    $contribution['type'] = "honor";
                }
                if ($params['soft_credit_type_id'] == 2) {
                    $contribution['type'] = "memory";
                }
                // Set Template Params
                $tplParams = array(
                    'donor' => $donor,
                    'honoree' => $honoree,
                    'acknowledged' => $notified,
                    'contribution' => $contribution
                );
                $tplParams['amount'] = $params['amount'];
                $sendTemplateParams = array(
                    'tplParams' => $tplParams,
                    'messageTemplateID' => $templateID,
                    'PDFFilename' => null,
                    'from' => "info@fightsma.org"
                );
                /*** Send Mail for each contact***/
                if ($honorID && ($nhoh || $nhom)) {
                    $honorTemplateParams = $sendTemplateParams;
                    $honorTemplateParams['contactId'] = $honorID;
                    $honorTemplateParams['toEmail'] = $honoree['email'];
                    $honorTemplateParams['toName'] = $honoree['display_name'];
                    $tplParams['to'] = $honoree;
                    if ($params['soft_credit_type_id'] == 1) {
                        $tplParams['honoree']['display_name'] = "you";
                    }
                    $session =& CRM_Core_Session::singleton();
                    $session->set('tplParams', $tplParams);
                    $sessoin->set('contrib_id', $con_id);
                    CRM_Core_BAO_MessageTemplate::sendTemplate($honorTemplateParams);
                }
                if ($notifyID && ($nnoh || $nnom)) {
                    $notifiedTemplateParams = $sendTemplateParams;
                    $notifiedTemplateParams['contactId'] = $notifyID;
                    $notifiedTemplateParams['toEmail'] = $notified['email'];
                    $notifiedTemplateParams['toName'] = $notified['display_name'];
                    $tplParams['to'] = $notified;
                    $session = CRM_Core_Session::singleton();
                    $session->set('tplParams', $tplParams);
                    $session->set('contrib_id', $con_id);
                    CRM_Core_BAO_MessageTemplate::sendTemplate($notifiedTemplateParams);
                }
                /*** Send Postal Card Notification ***/
                if ($params['acknowledge_mail']) {
                    $state = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_state_province WHERE id = {$params['ack']['state_province-Primary']}");
                    $country = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_country WHERE id = {$params['ack']['country-Primary']}");
                    $displayName = "{$params['ack']['first_name']} {$params['ack']['last_name']}";
                    $fields = array(
                        'street_address' => $params['ack']['street_address-Primary'],
                        'city' => $params['ack']['city-Primary'],
                        'state_province' => $state,
                        'postal_code' => $params['ack']['postal_code-Primary'],
                        'country' => $country,
                    );
                    $address = CRM_Utils_Address::format($fields, null, false, true, true);
                    $tplParams['ackMail'] = $displayName . "\n" . $address;
                    $session =& CRM_Core_Session::singleton();
                    $session->set('tplParams', $tplParams);
                    $sendTemplateParams = array(
                        'tplParams' => $tplParams,
                        'messageTemplateID' => 62,
                        'PDFFilename' => null,
                        'from' => "info@fightsma.org",
                        'toEmail' => "deannagriffin@comcast.net",
                        'toName' => "Deanna Griffin",
                    );
                    CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);

                    //Add note to contribution
                    try {
                      $contribID = $form->_contributionID;
                      $noteResult = civicrm_api3('Contribution', 'getsingle', array(
				  	            'id'=> $contribID,
				  	            'return' => 'note',
				              ));
				              $note = $noteResult['notes'];
                      $note .= "\n A card was sent to \n " . $tplParams['ackMail'];
                      $params = array(
                        'id' => $contribID,
                        'note' => $note,
                        'options' => array(
                          'match' => 'id',
                         ),
                      );
                      $result = civicrm_api3('Contribution', 'create', $params);
                    } catch (CiviCRM_API3_Exception $e) {
                        $error = $e->getMessage();
                    }
                }
            }
        }
    }

    if ($formName == "CRM_Contribute_Form_Contribution_Main") {
        $params = $form->_submitValues;
        /*** Send Postal Card Notification ***/
        if ($params['acknowledge_mail']) {
            $state = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_state_province WHERE id = {$params['ack']['state_province-Primary']}");
            $country = CRM_Core_DAO::singleValueQuery("SELECT name FROM civicrm_country WHERE id = {$params['ack']['country-Primary']}");
            $displayName = "{$params['ack']['first_name']} {$params['ack']['last_name']}";
            $fields = array(
                'street_address' => $params['ack']['street_address-Primary'],
                'city' => $params['ack']['city-Primary'],
                'state_province' => $state,
                'postal_code' => $params['ack']['postal_code-Primary'],
                'country' => $country,
            );
            $address = CRM_Utils_Address::format($fields, null, false, true, true);
            $ackMail = "A card will be sent to the following address:<br>" . $displayName . "<br>" . $address;
            $session = CRM_Core_Session::singleton();
            $session->set('ackMail', $ackMail);
        }
    }

}

function softcreditnotify_civicrm_alterMailParams(&$params, $context) {
    if (strpos($params['html'], '[mailing.address]') !== false) {
        $session = CRM_Core_Session::singleton();
        $ackMail = $session->get('ackMail');
        if (empty($ackMail)) {
            $ackMail = "";
        }
        $params['html'] = str_replace('[mailing.address]', $ackMail, $params['html']);
        $params['text'] = str_replace('[mailing.address]', $ackMail, $params['text']);
    }

    //Add email details to contribution note
    $session = CRM_Core_Session::singleton();
		$conID = $session->get('contrib_id');
		if ($conID) {
			//Add note to contribution
			try {
				  $noteResult = civicrm_api3('Contribution', 'getsingle', array(
				  	'id'=> $conID,
				  	'return' => 'note',
				  ));

				  $note = $noteResult['notes'];
				  $note .= "\n An email was sent to {$params['toName']} at {$params['toEmail']} from {$params['from']} :\n  {$params['text']}";

					$conParams = array(
					 		'id' => $conID,
					 		'note' => $note,
					 		'options' => array(
					 				'match' => 'id',
					 		),
					 );

				  $result = civicrm_api3('Contribution', 'create', $conParams);
				  $session->set('contrib_id', '');
			} catch (CiviCRM_API3_Exception $e) {
					$error = $e->getMessage();
			}
		}


}

function softcreditnotify_civicrm_pre($op, $objectName, $id, &$params) {
	if ($op == 'create' && $objectName == 'Individual') {
		//remove acknoweldge by email fields
		if (array_key_exists('acknowledge', $params)) {
			unset($params['acknowledge']);
		}
		//remove acknowledge by mail fields
		if (array_key_exists('ack', $params)) {
			unset($params['ack']);
		}
		//remove honor fields
		if (array_key_exists('honor', $params)) {
			unset($params['honor']);
		}
	}
}


/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function softcreditnotify_civicrm_config(&$config)
{
    _softcreditnotify_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function softcreditnotify_civicrm_xmlMenu(&$files)
{
    _softcreditnotify_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function softcreditnotify_civicrm_install()
{
    $sql = "CREATE TABLE IF NOT EXISTS civicrm_soft_notify (
	  is_active tinyint(4)  NOT NULL DEFAULT '0' COMMENT 'Boolean indicating whether notify is active or not',
	  template_id int(11) DEFAULT NULL COMMENT 'Implicit FK to option_value row in volunteer_role option_group.',
	  contribution_page int(11) DEFAULT NULL COMMENT 'Implicit FK to option_value row in volunteer_role option_group.',
	  notify_honoree_of_honor tinyint(4)  NOT NULL DEFAULT '0' COMMENT 'IBoolean of whether to notify contact from honoree profile of honor',
	  notify_honoree_of_memory tinyint(4)  NOT NULL DEFAULT '0' COMMENT 'Boolean of whether to notify contact from honoree profile of memory.',
	  notify_ack_of_honor tinyint(4)  NOT NULL DEFAULT '0' COMMENT 'Boolean of whether to notify contact from acknowledged profile of honor.',
	  notify_ack_of_memory tinyint(4)  NOT NULL DEFAULT '0' COMMENT 'Boolean of whether to notify contact from acknowledged profile of memory.',
	  PRIMARY KEY (`contribution_page`),
	  UNIQUE KEY (`contribution_page`)
	)";
    CRM_Core_DAO::executeQuery($sql);
    return _softcreditnotify_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function softcreditnotify_civicrm_uninstall()
{
    return _softcreditnotify_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function softcreditnotify_civicrm_enable()
{
    return _softcreditnotify_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function softcreditnotify_civicrm_disable()
{
    return _softcreditnotify_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function softcreditnotify_civicrm_upgrade($op, CRM_Queue_Queue $queue = null)
{
    return _softcreditnotify_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function softcreditnotify_civicrm_managed(&$entities)
{
    return _softcreditnotify_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function softcreditnotify_civicrm_caseTypes(&$caseTypes)
{
    _softcreditnotify_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function softcreditnotify_civicrm_alterSettingsFolders(&$metaDataFolders = null)
{
    _softcreditnotify_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
