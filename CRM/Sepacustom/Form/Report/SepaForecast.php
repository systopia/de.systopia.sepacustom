<?php
/*-------------------------------------------------------+
| Common CiviSEPA Customisations                         |
| Copyright (C) 2020 SYSTOPIA                            |
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


class CRM_Sepacustom_Form_Report_SepaForecast extends CRM_Report_Form
{

    protected $_addressField = false;

    protected $_emailField = false;

    protected $_summary = null;

    protected $_customGroupExtends = [];
    protected $_customGroupGroupBy = false;

    function __construct()
    {
        $this->_columns     = array(
            'civicrm_contact'           => array(
                'dao'      => 'CRM_Contact_DAO_Contact',
                'fields'   => array(
                    'sort_name'  => array(
                        'title'     => E::ts('Contact Name'),
                        'required'  => true,
                        'default'   => true,
                        'no_repeat' => true,
                    ),
                    'id'         => array(
                        'no_display' => true,
                        'required'   => true,
                    ),
                    'first_name' => array(
                        'title'     => E::ts('First Name'),
                        'no_repeat' => true,
                    ),
                    'last_name'  => array(
                        'title'     => E::ts('Last Name'),
                        'no_repeat' => true,
                    ),
                ),
                'filters'  => array(
                    'sort_name' => array(
                        'title'    => E::ts('Contact Name'),
                        'operator' => 'like',
                    ),
                    'id'        => array(
                        'no_display' => true,
                    ),
                ),
                'grouping' => 'contact-fields',
            ),
        );
        $this->_groupFilter = true;
        $this->_tagFilter   = true;
        parent::__construct();
    }

    function preProcess()
    {
        $this->assign('reportTitle', E::ts("CiviSEPA Collection Forecast"));
        parent::preProcess();
    }

    function from()
    {
        $this->_from = null;

        // get pre-calculated collection table
        $collections = $this->getCollectionsTable();

        $this->_from = "
         FROM  {$collections} sepa_collections
         INNER JOIN civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
                          ON {$this->_aliases['civicrm_contact']}.id = sepa_collections.contact_id
        ";
    }

    /**
     * Add field specific select alterations.
     *
     * @param string $tableName
     * @param string $tableKey
     * @param string $fieldName
     * @param array $field
     *
     * @return string
     */
    function selectClause(&$tableName, $tableKey, &$fieldName, &$field)
    {
        return parent::selectClause($tableName, $tableKey, $fieldName, $field);
    }

    /**
     * Add field specific where alterations.
     *
     * This can be overridden in reports for special treatment of a field
     *
     * @param array $field Field specifications
     * @param string $op Query operator (not an exact match to sql)
     * @param mixed $value
     * @param float $min
     * @param float $max
     *
     * @return null|string
     */
    public function whereClause(&$field, $op, $value, $min, $max)
    {
        return parent::whereClause($field, $op, $value, $min, $max);
    }

    function alterDisplay(&$rows)
    {
        // custom code to alter rows
        $entryFound = false;
        $checkList  = array();
        foreach ($rows as $rowNum => $row) {
            if (!empty($this->_noRepeats) && $this->_outputMode != 'csv') {
                // not repeat contact display names if it matches with the one
                // in previous row
                $repeatFound = false;
                foreach ($row as $colName => $colVal) {
                    if (CRM_Utils_Array::value($colName, $checkList) &&
                        is_array($checkList[$colName]) &&
                        in_array($colVal, $checkList[$colName])
                    ) {
                        $rows[$rowNum][$colName] = '';
                        $repeatFound             = true;
                    }
                    if (in_array($colName, $this->_noRepeats)) {
                        $checkList[$colName][] = $colVal;
                    }
                }
            }

            if (array_key_exists('civicrm_membership_membership_type_id', $row)) {
                if ($value = $row['civicrm_membership_membership_type_id']) {
                    $rows[$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType(
                        $value,
                        false
                    );
                }
                $entryFound = true;
            }

            if (array_key_exists('civicrm_address_state_province_id', $row)) {
                if ($value = $row['civicrm_address_state_province_id']) {
                    $rows[$rowNum]['civicrm_address_state_province_id'] = CRM_Core_PseudoConstant::stateProvince(
                        $value,
                        false
                    );
                }
                $entryFound = true;
            }

            if (array_key_exists('civicrm_address_country_id', $row)) {
                if ($value = $row['civicrm_address_country_id']) {
                    $rows[$rowNum]['civicrm_address_country_id'] = CRM_Core_PseudoConstant::country($value, false);
                }
                $entryFound = true;
            }

            if (array_key_exists('civicrm_contact_sort_name', $row) &&
                $rows[$rowNum]['civicrm_contact_sort_name'] &&
                array_key_exists('civicrm_contact_id', $row)
            ) {
                $url                                              = CRM_Utils_System::url(
                    "civicrm/contact/view",
                    'reset=1&cid=' . $row['civicrm_contact_id'],
                    $this->_absoluteUrl
                );
                $rows[$rowNum]['civicrm_contact_sort_name_link']  = $url;
                $rows[$rowNum]['civicrm_contact_sort_name_hover'] = E::ts("View Contact Summary for this Contact.");
                $entryFound                                       = true;
            }

            if (!$entryFound) {
                break;
            }
        }
    }

    /**
     * Make sure the table of all predicted collections is there and return the table name
     *
     * Table name pattern is sdd_forecast_<timeout>_<horizon>
     *
     * @return string
     *      name of the table to use
     */
    protected function getCollectionsTable()
    {
        $now         = 'now'
        $horizon     = (int) 100;  // TODO: option
        $ttl_seconds = (int) 3600; // TODO: option

        // step one: find existing collection tables
        $candidates = [];
        $candidate_query = CRM_Core_DAO::executeQuery("SHOW TABLES LIKE 'sdd_forecast_%'");
        while ($candidate_query->fetch()) {
            $table_name = $candidate_query->table_name;
            if (preg_match('/sdd_forecast_[0-9]{14}_[0-9]+/', $table_name)) {
                $candidates[] = $table_name;
            }
        }

        // step one: delete all outdated tables
        $timestamp_now = date("YmdHis");
        foreach (array_keys($candidates) as $index) {
            $table_name = $candidates[$index];
            if (preg_match('/sdd_forecast_[0-9]{14}_[0-9]+/', $table_name, $match)) {
                if ($match[0] < $timestamp_now) {
                    // expired: delete that (old) table
                    CRM_Core_DAO::executeQuery("DROP TABLE %1;", [1 => [$table_name, 'String']]);
                    unset($candidates[$index]);
                }
            }
        }

        // step three: from the ones with the right horizon...
        $valid_candidates = [];
        foreach ($candidates as $table_name) {
            if (preg_match('/sdd_forecast_[0-9]{14}_[0-9]+/', $table_name, $match)) {
                if ($match[1] >= $horizon) {
                    // that one has a big enough horizon
                    $valid_candidates[] = $table_name;
                }
            }
        }
        if (!empty($valid_candidates)) {
            // ...pick the most recent one and return
            sort($valid_candidates);
            array_reverse($valid_candidates);
            return reset($valid_candidates);
        }

        // step four: there is no such table => create one
        $timeout = date('YmdHis', strtotime("now + {$ttl_seconds} seconds"));
        $table_name = "sdd_forecast_{$timeout}_{$horizon}";
        CRM_Core_DAO::executeQuery("
        CREATE TABLE `{$table_name}` (
         `mandate_id`          int unsigned        COMMENT 'ID of the mandate that created this',
         `contact_id`          int unsigned        COMMENT 'ID of the contact owning the mandate',
         `financial_type_id`   int(10) unsigned    COMMENT 'financial type of the mandate',
         `amount`              decimal(20,2)       COMMENT 'amount to be collected',
         `collection_date`     datetime            COMMENT 'date when the amount is collected',
         INDEX `mandate_id` (mandate_id),
         INDEX `contact_id` (contact_id),
         INDEX `financial_type_id` (financial_type_id),
         INDEX `amount` (amount),
         INDEX `collection_date` (collection_date)
        );");

        // fill table
        $active_mandates = CRM_Core_DAO::executeQuery("
          SELECT
            -- needed for data:
            mandate.id                       AS mandate_id,
            mandate.contact_id               AS mandate_contact_id,
            rcontribution.financial_type_id  AS rc_financial_type_id,
            rcontribution.amount             AS rc_amount,
            mandate.creditor_id              AS mandate_creditor_id,

            -- needed for scheduling:
            first_contribution.receive_date  AS mandate_first_executed,
            rcontribution.start_date         AS start_date,
            rcontribution.cycle_day          AS cycle_day,
            rcontribution.frequency_interval AS frequency_interval,
            rcontribution.frequency_unit     AS frequency_unit,
            rcontribution.cancel_date        AS cancel_date,
            rcontribution.end_date           AS end_date,
            mandate.status                   AS status
          FROM civicrm_sdd_mandate           AS mandate
          INNER JOIN civicrm_contribution_recur AS rcontribution       ON mandate.entity_id = rcontribution.id AND mandate.entity_table = 'civicrm_contribution_recur'
          LEFT  JOIN civicrm_contribution       AS first_contribution  ON mandate.first_contribution_id = first_contribution.id
          LEFT  JOIN civicrm_sdd_creditor       AS creditor            ON mandate.creditor_id = creditor.id
          WHERE mandate.type = 'RCUR'
            AND creditor.mandate_prefix <> 'TEST'
            AND (rcontribution.is_test IS NULL OR rcontribution.is_test = 0) 
            AND mandate.status IN ('FRST', 'RCUR');
          ");
        while ($active_mandates->fetch()) {
            $mandate         = [
                'cycle_day'              => $active_mandates->cycle_day,
                'frequency_interval'     => $active_mandates->frequency_interval,
                'frequency_unit'         => $active_mandates->frequency_unit,
                'start_date'             => $active_mandates->start_date,
                'mandate_first_executed' => $active_mandates->mandate_first_executed,
                'end_date'               => $active_mandates->end_date,
                'cancel_date'            => $active_mandates->cancel_date,
            ];
            $next_collection = $next_date = CRM_Sepa_Logic_Batching::getNextExecutionDate(
                $mandate,
                $now,
                $mandate->status
            );

            // TODO: while nextcollection < horizon:
            // ->defer(via cache)
            // insert into BLORP
        }

        // we're done:
        return $table_name;
    }
}
