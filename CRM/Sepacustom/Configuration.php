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

use CRM_Sepacustom_ExtensionUtil as E;

/**
 * General Configuration
 */
class CRM_Sepacustom_Configuration {

  /**
   * Get the list of bank holidays to exclude from batching
   *
   * @return array of date strings
   */
  public static function getBankHolidays() {
    $holidays = Civi::settings()->get('customsepa_bank_holidays');
    if (is_array($holidays)) {
      return $holidays;
    } else {
      return [];
    }
  }

  /**
   * Get the list of BIC restrictions records. Each contains
   * 'creditor_id' - ID or '*'
   * 'match'       - '+' or '-' (positive or negative match)
   * 'pattern'     - regex string
   * 'error'       - error message to report in case of a match
   *
   * @return array with the restriction records
   *
   */
  public static function getBICRestrictions() {
    $restrictions = Civi::settings()->get('customsepa_bic_restrictions');
    if (is_array($restrictions)) {
      return $restrictions;
    } else {
      return [];
    }
  }

  /**
   * Check if the given creditor/bic combination is restricted
   *
   * @param $creditor_id  integer SepaCreditor ID
   * @param $bic          string  BIC
   * @return NULL|string error message in case there is a restriction
   */
  public static function getBICRestrictionError($creditor_id, $bic) {
    $restrictions = self::getBICRestrictions();
    foreach ($restrictions as $r) {
      if ($r['creditor_id'] == $creditor_id || $r['creditor_id'] == '*') {
        // it applies to this creditor
        if (!empty($r['match']) && !empty($r['pattern'])) {
          $match = preg_match("#{$r['pattern']}#", $bic);
          if (($match && $r['match'] == '-') || (!$match && $r['match'] == '+')) {
            // this is a match
            return empty($r['error']) ? E::ts("Invalid BIC for this creditor") : $r['error'];
          }
        }
      }
    }
    // all good
    return NULL;
  }

  /**
   * Get the default form values with the current BIC restrictions
   */
  public static function getBICRestrictionsFormValues() {
    $values = [];
    $restrictions = self::getBICRestrictions();
    foreach ($restrictions as $i => $r) {
      $values["bic_restriction_creditor_{$i}"]  = $r['creditor_id'];
      $values["bic_restriction_condition_{$i}"] = $r['match'];
      $values["bic_restriction_regex_{$i}"]     = $r['pattern'];
      $values["bic_restriction_message_{$i}"]   = $r['error'];
    }
    return $values;
  }


  /**
   * Get the list of BIC restrictions records. Each contains
   * 'creditor_id' - ID or '*'
   * 'match'       - '+' or '-' (positive or negative match)
   * 'pattern'     - regex string
   * 'error'       - error message to report in case of a match
   *
   * @return array with the restriction records
   *
   */
  public static function getTXReferenceChanges() {
    $restrictions = Civi::settings()->get('customsepa_txref_changes');
    if (is_array($restrictions)) {
      return $restrictions;
    } else {
      return [];
    }
  }

  /**
   * Apply any search/replace changes to the TXG reference
   *
   * @param $txg_reference  string current TXG reference
   */
  public static function applyTxgReferenceChanges(&$txg_reference) {
    $changes = self::getTXReferenceChanges();
    foreach ($changes as $change) {
      // add delimiters if needed
      if ($change['search'][0] != $change['search'][-1]) {
       $change['search'] = '!' . preg_replace('/!/', '\\!', $change['search']) . '!';
      }

      // apply
      $txg_reference = preg_replace($change['search'], $change['replace'], $txg_reference);
    }
  }


  /**
   * Get the default form values with the current BIC restrictions
   */
  public static function getTXReferenceChangesFormValues() {
    $values  = [];
    $changes = self::getTXReferenceChanges();
    foreach ($changes as $i => $r) {
      $values["txref_search_{$i}"]  = $r['search'];
      $values["txref_replace_{$i}"] = $r['replace'];
    }
    return $values;
  }


}
