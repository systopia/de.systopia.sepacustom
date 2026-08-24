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

use CRM_Sepacustom_ExtensionUtil as E;

/**
 * General Configuration
 */
class CRM_Sepacustom_Configuration {

  /**
   * Get the list of bank holidays to exclude from batching
   *
   * @return array<int, string>
   *   list of date strings
   */
  public static function getBankHolidays(): array {
    $holidays = Civi::settings()->get('customsepa_bank_holidays');
    if (is_array($holidays)) {
      return $holidays;
    }
    else {
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
   * @return array<int, array{creditor_id: string, match: string, pattern: string, error: string}>
   *   the restriction records
   */
  public static function getBICRestrictions(): array {
    $restrictions = Civi::settings()->get('customsepa_bic_restrictions');
    if (is_array($restrictions)) {
      return $restrictions;
    }
    else {
      return [];
    }
  }

  /**
   * Check if the given creditor/bic combination is restricted
   *
   * @param int|string|null $creditor_id
   *   SepaCreditor ID
   * @param string $bic
   *   BIC
   *
   * @return string|null
   *   error message in case there is a restriction
   */
  public static function getBICRestrictionError($creditor_id, string $bic): ?string {
    $restrictions = self::getBICRestrictions();
    foreach ($restrictions as $r) {
      $restriction_creditor_id = (string) $r['creditor_id'];
      if ($restriction_creditor_id === (string) $creditor_id || $restriction_creditor_id === '*') {
        // it applies to this creditor
        if ($r['match'] !== '' && $r['pattern'] !== '') {
          $match = preg_match("#{$r['pattern']}#", $bic) === 1;
          if (($match && $r['match'] === '-') || (!$match && $r['match'] === '+')) {
            // this is a match
            return $r['error'] === '' ? E::ts('Invalid BIC for this creditor') : $r['error'];
          }
        }
      }
    }
    // all good
    return NULL;
  }

  /**
   * Get the default form values with the current BIC restrictions
   *
   * @return array<string, string>
   */
  public static function getBICRestrictionsFormValues(): array {
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
   * Parse a date/time string into a Unix timestamp.
   *
   * Wraps strtotime() and throws instead of silently returning FALSE for an
   * unparseable date/time string.
   *
   * @param string $datetime
   * @param int|null $baseTimestamp
   *
   * @return int
   *
   * @throws \CRM_Core_Exception
   *   If the date/time string could not be parsed.
   */
  public static function toTimestamp(string $datetime, ?int $baseTimestamp = NULL): int {
    $timestamp = strtotime($datetime, $baseTimestamp);
    if ($timestamp === FALSE) {
      throw new CRM_Core_Exception("Could not parse date/time string \"{$datetime}\".");
    }
    return $timestamp;
  }

}
