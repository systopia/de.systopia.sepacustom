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
    protected $_summary = null;

    function __construct()
    {
        $stats_table = $this->getCollectionsTable();
        $this->_columns     = [
            'sdd_collection_forecast' => [
                'table' => $stats_table,
                'fields' => [
                    'sum_amount' => [
                        'title' => E::ts("Total Amount"),
                    ],
                    'avg_amount' => [
                        'title' => E::ts("Average Amount"),
                    ],
                    'contribution_count' => [
                        'title' => E::ts("Contribution Count"),
                    ],
                    'contact_count' => [
                        'title' => E::ts("Contact Count"),
                    ],
                    'financial_type_id' => [
                        'name' => 'financial_type_id',
                        'title' => E::ts("Financial Type"),
                    ],
                    'creditor_id' => [
                        'title' => E::ts("Creditor"),
                    ],
                ],
                'filters' => [
                    'horizon' => [
                        'name' => 'horizon',
                        'title' => E::ts("Horizon"),
                        'operatorType' => CRM_Report_Form::OP_SELECT,
                        'default' => '+1 year',
                        'options' => [
                            '+1 month' => E::ts("1 month"),
                            '+6 month' => E::ts("6 months"),
                            '+1 year'  => E::ts("1 year"),
                            '+2 year'  => E::ts("2 years"),
                            '+5 year'  => E::ts("5 years"),
                        ],
                    ],
//                    'financial_type_id' => [
//                        'name' => 'financial_type_id',
//                        'title' => E::ts("Financial Type"),
//                        'type' => CRM_Utils_Type::T_INT,
//                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
//                        'options' => CRM_Financial_BAO_FinancialType::getAllAvailableFinancialTypes(),
//                    ],
//                    'creditor_id' => [
//                        'title' => E::ts("Creditor"),
//                        'type' => CRM_Utils_Type::T_INT,
//                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
//                        'options' => $this->getAllCreditors(),
//                    ],
                ],
                'group_bys' => [
                    'collection_date' => [
                        'name' => 'collection_date',
                        'title' => E::ts("Collection Date"),
                        'default' => true,
                        'frequency' => true,
                        'chart' => true,
//                        'type' => 12,
                    ],
                    'financial_type_id' => [
                        'name' => 'financial_type_id',
                        'title' => E::ts("Financial Type"),
                    ],
                    'creditor_id' => [
                        'name' => 'creditor_id',
                        'title' => E::ts("Creditor"),
                    ],
                ],
            ]
        ];

        $this->_groupFilter = true;
        $this->_tagFilter   = true;
        $this->_aliases['civicrm_contact'] = 'sdd_collection_contact';
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
         FROM  {$collections} sdd_collection_forecast
         LEFT JOIN civicrm_contact {$this->_aliases['civicrm_contact']} ON {$this->_aliases['civicrm_contact']}.id = sdd_collection_forecast.contact_id
        ";
    }

    /**
     * Generate the SELECT clause and set class variable $_select.
     */
    public function select()
    {
        // start with collection date frame
        if (!empty($this->_params['group_bys']['collection_date'])) {
            switch ($this->_params['group_bys_freq']['collection_date']) {
                case 'YEARWEEK':
                    $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), 'W', WEEK(sdd_collection_forecast.collection_date)) AS date_frame"];
                    break;

                default:
                case 'MONTH':
                $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), '-', MONTH(sdd_collection_forecast.collection_date)) AS date_frame"];
                    break;

                case 'QUARTER':
                    $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), 'Q', QUARTER(sdd_collection_forecast.collection_date)) AS date_frame"];
                    break;

                case 'YEAR':
                    $this->_selectClauses = ["YEAR(sdd_collection_forecast.collection_date) AS date_frame"];
                    break;
            }
        } else { // default is month
            $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), '-', MONTH(sdd_collection_forecast.collection_date)) AS date_frame"];
        }
        $this->_columnHeaders['date_frame'] = [
            'title' => E::ts("Time Frame"),
            'type' => CRM_Utils_Type::T_STRING,
        ];

        if (!empty($this->_params['fields']['sum_amount'])) {
            $this->_selectClauses[] = "SUM(sdd_collection_forecast.amount) AS sum_amount";
            $this->_columnHeaders['sum_amount'] = [
                'title' => E::ts("Total Amount"),
                'type' => CRM_Utils_Type::T_MONEY,
            ];
        }
        if (!empty($this->_params['fields']['avg_amount'])) {
            $this->_selectClauses[] = "AVG(sdd_collection_forecast.amount) AS avg_amount";
            $this->_columnHeaders['avg_amount'] = [
                'title' => E::ts("Average Amount"),
                'type' => CRM_Utils_Type::T_MONEY,
            ];
        }
        if (!empty($this->_params['fields']['contribution_count'])) {
            $this->_selectClauses[] = "COUNT(*) AS contribution_count";
            $this->_columnHeaders['contribution_count'] = [
                'title' => E::ts("Individual Contributions"),
                'type' => CRM_Utils_Type::T_INT,
            ];
        }
        if (!empty($this->_params['fields']['contact_count'])) {
            $this->_selectClauses[] = "COUNT(DISTINCT(sdd_collection_forecast.contact_id)) AS contact_count";
            $this->_columnHeaders['contact_count'] = [
                'title' => E::ts("Individual Contacts"),
                'type' => CRM_Utils_Type::T_INT,
            ];
        }

        $this->_select = "SELECT " . implode(', ', $this->_selectClauses) . " ";
    }

    public function groupBy()
    {
        $this->_groupBy = null;
        $group_bys = [];

        // add collection date grouping
        if (!empty($this->_params['group_bys']['collection_date'])) {
            switch ($this->_params['group_bys_freq']['collection_date']) {
                case 'YEARWEEK':
                    $group_bys[] = "YEAR(sdd_collection_forecast.collection_date), WEEK(sdd_collection_forecast.collection_date)";
                    break;

                default:
                case 'MONTH':
                    $group_bys[] = "YEAR(sdd_collection_forecast.collection_date), MONTH(sdd_collection_forecast.collection_date)";
                    break;

                case 'QUARTER':
                    $group_bys[] = "YEAR(sdd_collection_forecast.collection_date), QUARTER(sdd_collection_forecast.collection_date)";
                    break;

                case 'YEAR':
                    $group_bys[] = "YEAR(sdd_collection_forecast.collection_date)";
                    break;
            }
        } else { // default is month
            $group_bys[] = "YEAR(sdd_collection_forecast.collection_date), MONTH(sdd_collection_forecast.collection_date)";
        }

        // add financial type grouping
        if (!empty($this->_params['group_bys']['financial_type_id'])) {
            $group_bys[] = "sdd_collection_forecast.financial_type_id";
        }

        // add creditor ID grouping
        if (!empty($this->_params['group_bys']['creditor_id'])) {
            $group_bys[] = "sdd_collection_forecast.creditor_id";
        }

        // finally: compile the group by clause
        if (!empty($group_bys)) {
            $this->_groupBy = 'GROUP BY ' . implode(', ', $group_bys);
        }
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
        $this->_where = ' TRUE ';
    }

    /**
     * Modify column headers.
     */
    public function modifyColumnHeaders() {
        // use this method to modify $this->_columnHeaders
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
     * Return a list of all creditors
     *
     * @return array
     *      creditor id -> label
     */
    protected function getAllCreditors() {
        $creditor_list = [];
        $query = civicrm_api3(
            'SepaCreditor',
            'get',
            [
                'option.limit' => 0,
                'return'       => 'id,name,label'
            ]
        );
        foreach ($query['values'] as $creditor) {
            if (empty($creditor['label'])) {
                $creditor_list[$creditor['id']] = $creditor['name'];
            } else {
                $creditor_list[$creditor['id']] = $creditor['label'];
            }
        }

        return $creditor_list;
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
        $now         = strtotime('now');
        $today       = date('Y-m-d');
        $horizon     = (int) 100;  // TODO: option
        $ttl_seconds = (int) 3600; // TODO: option

        // step one: find existing collection tables
        $candidates = [];
        $DSN = DB::parseDSN(CRM_Core_Config::singleton()->dsn);
        $candidate_query = CRM_Core_DAO::executeQuery("SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$DSN['database']}' AND TABLE_NAME LIKE 'sdd_forecast_%';");
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
            if (preg_match('/sdd_forecast_(?P<timestamp>[0-9]{14})_[0-9]+/', $table_name, $match)) {
                if ($match['timestamp'] < $timestamp_now) {
                    // expired: delete that (old) table
                    CRM_Core_DAO::executeQuery("DROP TABLE `{$table_name}`;");
                    unset($candidates[$index]);
                }
            }
        }

        // step three: from the ones with the right horizon...
        $valid_candidates = [];
        foreach ($candidates as $table_name) {
            if (preg_match('/sdd_forecast_[0-9]{14}_(?P<horizon>[0-9]+)/', $table_name, $match)) {
                if ($match['horizon'] >= $horizon) {
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
        $horizon_date = date('YmdHis', strtotime("now + {$horizon} days"));
        $table_name = "sdd_forecast_{$timeout}_{$horizon}";
        $creation_timestamp = microtime(true);
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

            // calculate next collection
            $next_collection = CRM_Sepa_Logic_Batching::getNextExecutionDate(
                $mandate,
                $now,
                $mandate->status
            );

            // calculate abortion date
            $abortion_date = $horizon_date;
            if (!empty($mandate['end_date'])) {
                $abortion_date = min($abortion_date, $mandate['end_date']);
            }
            if (!empty($mandate['cancel_date'])) {
                $abortion_date = min($abortion_date, $mandate['cancel_date']);
            }

            $collection_dates = [];
            while ($next_collection < $abortion_date) {
                // collect this one for writing out
                if ($next_collection >= $today) {
                    $collection_dates[] = $next_collection;
                }

                // move on to the next one
                $next_collection = date('Y-m-d', strtotime("{$next_collection} + {$mandate['frequency_interval']} {$mandate['frequency_unit']}"));
            }

            // write out
            if (!empty($collection_dates)) {
                $values = [];
                $template = "({$active_mandates->mandate_id},{$active_mandates->mandate_contact_id},{$active_mandates->rc_financial_type_id},{$active_mandates->rc_amount},DATE('%s'))";
                foreach ($collection_dates as $collection_date) {
                    // TODO: defer?
                    $values[] = sprintf($template, $collection_date);
                }
                // write out
                CRM_Core_DAO::executeQuery("INSERT INTO `{$table_name}`(mandate_id,contact_id,financial_type_id,amount,collection_date) VALUES " . implode(',',$values));
            }
        } // on to the next mandate

        // we're done:
        Civi::log()->debug(sprintf("Created SEPA Forecast data table '%s' in %.1fs", $table_name, microtime(true) - $creation_timestamp));
        return $table_name;
    }
}
