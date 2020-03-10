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
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Sepacustom_Form_Configuration extends CRM_Core_Form {

  const MAX_BIC_RESTRICTION_COUNT = 10;
  const MAX_GROUP_REFERENCE_COUNT = 10;

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts("CiviSEPA Customisations"));

    // bank holiday field
    $this->add(
      'textarea',
      'bank_holidays',
      E::ts("Bank Holidays"),
      ['class' => 'huge'],
      FALSE
    );

    // add BIC restrictions
    $creditors = $this->getCreditors();
    $this->assign("bic_restrictions", range(0, self::MAX_BIC_RESTRICTION_COUNT));
    foreach (range(0, self::MAX_BIC_RESTRICTION_COUNT) as $i) {
      $this->add(
          'select',
          "bic_restriction_creditor_{$i}",
          E::ts("Creditor"),
          $creditors,
          FALSE
      );
      $this->add(
          'select',
          "bic_restriction_condition_{$i}",
          E::ts("Condition"),
          ['+' => E::ts("match"), "-" => E::ts("not match")],
          FALSE
      );
      $this->add(
          'text',
          "bic_restriction_regex_{$i}",
          E::ts("Pattern"),
          [],
          FALSE
      );
      $this->add(
          'text',
          "bic_restriction_message_{$i}",
          E::ts("Error Message"),
          [],
          FALSE
      );
    }

    // add group reference
    $this->assign("txref_list", range(0, self::MAX_GROUP_REFERENCE_COUNT));
    foreach (range(0, self::MAX_GROUP_REFERENCE_COUNT) as $i) {
      $this->add(
          'text',
          "txref_search_{$i}",
          E::ts("Find String"),
          [],
          FALSE
      );
      $this->add(
          'text',
          "txref_replace_{$i}",
          E::ts("Replace With"),
          [],
          FALSE
      );
    }

    $this->addButtons([
        [
            'type'      => 'submit',
            'name'      => E::ts('Submit'),
            'isDefault' => TRUE,
        ],
    ]);

    // set defaults
    $this->setDefaults(['bank_holidays' => implode(', ', CRM_Sepacustom_Configuration::getBankHolidays())]);
    $this->setDefaults(CRM_Sepacustom_Configuration::getBICRestrictionsFormValues());
    $this->setDefaults(CRM_Sepacustom_Configuration::getTXReferenceChangesFormValues());

    // add resources
    Civi::resources()->addScriptFile(E::LONG_NAME, 'js/configuration_form.js');
    Civi::resources()->addVars('sepacustom', [
        'bic_restriction_count'      => self::MAX_BIC_RESTRICTION_COUNT,
        'txgreference_changes_count' => self::MAX_BIC_RESTRICTION_COUNT
    ]);
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    // extract bank holidays
    $bank_holidays = [];
    if (preg_match_all('/[^0-9-](?<date>[0-9]{4}-[0-9]{2}-[0-9]{2})[^0-9-]/', " {$values['bank_holidays']} ", $matches)) {
      $bank_holidays = $matches['date'];
    }
    Civi::settings()->set('customsepa_bank_holidays', $bank_holidays);

    // extract BIC restrictions
    $bic_restrictions = [];
    foreach (range(0, self::MAX_BIC_RESTRICTION_COUNT) as $i) {
      if (!empty($values["bic_restriction_creditor_{$i}"])
          && !empty($values["bic_restriction_regex_{$i}"])) {
        // creditor and pattern are set => all good
        $bic_restrictions[] = [
            'creditor_id' => $values["bic_restriction_creditor_{$i}"],
            'match'       => $values["bic_restriction_condition_{$i}"],
            'pattern'     => $values["bic_restriction_regex_{$i}"],
            'error'       => $values["bic_restriction_message_{$i}"],
        ];
      }
    }
    Civi::settings()->set('customsepa_bic_restrictions', $bic_restrictions);

    // extract TXReference manipulations
    $xg_refenrence_changes = [];
    foreach (range(0, self::MAX_GROUP_REFERENCE_COUNT) as $i) {
      if (!empty($values["txref_search_{$i}"])) {
        // creditor and pattern are set => all good
        $xg_refenrence_changes[] = [
            'search'  => $values["txref_search_{$i}"],
            'replace' => $values["txref_replace_{$i}"],
        ];
      }
    }
    Civi::settings()->set('customsepa_txref_changes', $xg_refenrence_changes);
    $nase = "naseTXGnase";
    CRM_Sepacustom_Configuration::applyTxgReferenceChanges($nase);

    // done
    parent::postProcess();

    // reset page to format values (e.g. bank holidays)
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/sepacustom', 'reset=1'));
  }


  /**
   * Get the List of creditors
   */
  protected function getCreditors() {
    $list = [
        ''  => E::ts('<i>disabled</i>'),
        '*' => E::ts("any")
    ];

    $query = civicrm_api3('SepaCreditor', 'get', [
        'option.limit' => 0,
        'return'       => 'id,name,label'
    ]);

    foreach ($query['values'] as $creditor) {
      $name = empty($creditor['label']) ? $creditor['name'] : $creditor['label'];
      $list[$creditor['id']] =  "{$name} [{$creditor['id']}]";
    }

    return $list;
  }

}
