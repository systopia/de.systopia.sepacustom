<?php
/*-------------------------------------------------------+
| Common CiviSEPA Customisations                         |
| Copyright (C) 2019 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)                 |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/

require_once 'sepacustom.civix.php';
use CRM_Sepacustom_ExtensionUtil as E;

/**
 * Add extra validation for forms
 */
function sepacustom_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  // apply BIC restrictions to new mandates
  if ($formName == 'CRM_Sepa_Form_CreateMandate') {
    $bic = CRM_Utils_Array::value('bic', $fields);
    if ($bic) {
      $creditor_id = CRM_Utils_Array::value('creditor_id', $fields);
      $bic_error = CRM_Sepacustom_Configuration::getBICRestrictionError($creditor_id, $bic);
      if ($bic_error) {
        $errors['bic'] = $bic_error;
      }
    }
  }
}

/**
 * Implements CiviSEPA hook to adjust collection date
 */
function sepacustom_civicrm_defer_collection_date(&$collection_date, $creditor_id) {
  $bank_holidays = CRM_Sepacustom_Configuration::getBankHolidays();
  while (in_array($collection_date, $bank_holidays)                      // this is a bank holiday
      || date('N', strtotime($collection_date)) > 5) {   // or this is a weekend
    // while this is not a valid collection day, move on to the next day
    $collection_date = date('Y-m-d', strtotime("+1 day", strtotime($collection_date)));
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/ 
 */
function sepacustom_civicrm_config(&$config) {
  _sepacustom_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function sepacustom_civicrm_install() {
  _sepacustom_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function sepacustom_civicrm_enable() {
  _sepacustom_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *

 // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
function sepacustom_civicrm_navigationMenu(&$menu) {
  _sepacustom_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _sepacustom_civix_navigationMenu($menu);
} // */
