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

declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'sepacustom.civix.php';
use CRM_Sepacustom_ExtensionUtil as E;

/**
 * Add extra validation for forms
 *
 * @param array<int|string, mixed> $fields
 * @param array<int|string, mixed> $files
 * @param array<string, string> $errors
 */
function sepacustom_civicrm_validateForm(
  string $formName,
  array &$fields,
  array &$files,
  CRM_Core_Form &$form,
  array &$errors
): void {
  // apply BIC restrictions to new mandates
  if ($formName === 'CRM_Sepa_Form_CreateMandate') {
    $bic = $fields['bic'] ?? NULL;
    if (is_string($bic) && $bic !== '') {
      $creditor_id = $fields['creditor_id'] ?? NULL;
      $bic_error = CRM_Sepacustom_Configuration::getBICRestrictionError($creditor_id, $bic);
      if ($bic_error !== NULL) {
        $errors['bic'] = $bic_error;
      }
    }
  }
}

/**
 * Implements CiviSEPA hook to adjust collection date
 */
function sepacustom_civicrm_defer_collection_date(string &$collection_date, int $creditor_id): void {
  $bank_holidays = CRM_Sepacustom_Configuration::getBankHolidays();
  // this is a bank holiday
  while (in_array($collection_date, $bank_holidays, TRUE)
  // or this is a weekend
      || (int) date('N', CRM_Sepacustom_Configuration::toTimestamp($collection_date)) > 5) {
    // while this is not a valid collection day, move on to the next day
    $collection_date = date('Y-m-d', CRM_Sepacustom_Configuration::toTimestamp(
      '+1 day',
      CRM_Sepacustom_Configuration::toTimestamp($collection_date)
    ));
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function sepacustom_civicrm_config(CRM_Core_Config &$config): void {
  _sepacustom_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function sepacustom_civicrm_install(): void {
  _sepacustom_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function sepacustom_civicrm_enable(): void {
  _sepacustom_civix_civicrm_enable();
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 *
 *
 * // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 *
 * function sepacustom_civicrm_navigationMenu(&$menu) {
 * _sepacustom_civix_insert_navigation_menu($menu, 'Mailings', array(
 * 'label' => E::ts('New subliminal message'),
 * 'name' => 'mailing_subliminal_message',
 * 'url' => 'civicrm/mailing/subliminal',
 * 'permission' => 'access CiviMail',
 * 'operator' => 'OR',
 * 'separator' => 0,
 * ));
 * _sepacustom_civix_navigationMenu($menu);
} // */
