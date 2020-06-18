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
        $this->_columns     = [
            'sdd_collection_forecast' => [
                'fields' => [
                    'sum_amount' => [
                        'title' => E::ts("Total Amount"),
                        'default' => 1
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
//                    'financial_type_id' => [
//                        'name' => 'financial_type_id',
//                        'title' => E::ts("Financial Type"),
//                    ],
//                    'creditor_id' => [
//                        'title' => E::ts("Creditor"),
//                    ],
                ],
                'filters' => [
                    'horizon' => [
                        'name' => 'horizon',
                        'title' => E::ts("Horizon"),
                        'operatorType' => CRM_Report_Form::OP_SELECT,
                        'default' => '1 year',
                        'options' => [
                            '1 month' => E::ts("1 month"),
                            '6 month' => E::ts("6 months"),
                            '1 year'  => E::ts("1 year"),
                            '2 year'  => E::ts("2 years"),
                            '5 year'  => E::ts("5 years"),
                        ],
                    ],
                    'caching' => [
                        'name' => 'caching',
                        'title' => E::ts("Caching"),
                        'operatorType' => CRM_Report_Form::OP_SELECT,
                        'default' => '604800', # one week
                        'options' => [
                            '0'       => E::ts("no caching"),
                            '86400'   => E::ts("1 day"),
                            '604800'  => E::ts("1 week"),
                            '2678400' => E::ts("1 month"),
                        ],
                    ],
                    'financial_type_id' => [
                        'name' => 'financial_type_id',
                        'title' => E::ts("Financial Type"),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Financial_BAO_FinancialType::getAllAvailableFinancialTypes(),
                    ],
                    'creditor_id' => [
                        'title' => E::ts("Creditor"),
                        'type' => CRM_Utils_Type::T_INT,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => $this->getAllCreditors(),
                    ],
                ],
                'group_bys' => [
                    'collection_date' => [
                        'name' => 'collection_date',
                        'title' => E::ts("Collection Date"),
                        'default' => true,
                        'frequency' => true,
                        'chart' => true,
                    ],
//                    'financial_type_id' => [
//                        'name' => 'financial_type_id',
//                        'title' => E::ts("Financial Type"),
//                    ],
//                    'creditor_id' => [
//                        'name' => 'creditor_id',
//                        'title' => E::ts("Creditor"),
//                    ],
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


        // get (hopefully) pre-calculated collection table
        $horizon = CRM_Utils_Array::value('horizon_value', $this->_params, '1 year');
        $caching = CRM_Utils_Array::value('caching_value', $this->_params, '604800');
        $from_date = $this->getAlignedStartDate();
        $to_date   = date('Y-m-d', strtotime("{$from_date} + {$horizon} - 1 day"));
        $min_creation_time = date('YmdHis', strtotime("now - {$caching} seconds"));
        $collections = $this->getCollectionsTable($from_date, $to_date, $min_creation_time);

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
        // if no columns selected, add the amount
        if (empty($this->_params['fields'])) {
            $this->_params['fields'] = ['sum_amount' => 1];
        }

        // start with collection date frame
        if (!empty($this->_params['group_bys']['collection_date'])) {
            switch ($this->_params['group_bys_freq']['collection_date']) {
                case 'YEARWEEK':
                    $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), 'W', LPAD(WEEK(sdd_collection_forecast.collection_date), 2, 0)) AS date_frame"];
                    break;

                default:
                case 'MONTH':
                $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), '-', LPAD(MONTH(sdd_collection_forecast.collection_date)), 2, 0) AS date_frame"];
                    break;

                case 'QUARTER':
                    $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), 'Q', QUARTER(sdd_collection_forecast.collection_date)) AS date_frame"];
                    break;

                case 'YEAR':
                    $this->_selectClauses = ["YEAR(sdd_collection_forecast.collection_date) AS date_frame"];
                    break;
            }
        } else { // default is month
            $this->_selectClauses = ["CONCAT(YEAR(sdd_collection_forecast.collection_date), '-', LPAD(MONTH(sdd_collection_forecast.collection_date), 2, 0)) AS date_frame"];
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
    public function where()
    {
        $where_clauses = [];

        // add time restrictions
        $from_date = $this->getAlignedStartDate();
        $horizon = CRM_Utils_Array::value('horizon_value', $this->_params, '1 year');
        $to_date   = date('Y-m-d', strtotime("{$from_date} + {$horizon} - 1 day"));
        $where_clauses[] = "DATE(sdd_collection_forecast.collection_date) >= DATE('{$from_date}')";
        $where_clauses[] = "DATE(sdd_collection_forecast.collection_date) <= DATE('{$to_date}')";

        // add financial_type restriction
        if (!empty($this->_params['financial_type_id_value'])) {
            $values = implode(',', $this->_params['financial_type_id_value']);
            if ($this->_params['financial_type_id_op'] == 'in') {
                $where_clauses[] = "sdd_collection_forecast.financial_type_id IN ($values)";
            } else {
                $where_clauses[] = "sdd_collection_forecast.financial_type_id NOT IN ($values)";
            }
        }

        // add creditor restriction
        if (!empty($this->_params['creditor_id_value'])) {
            $values = implode(',', $this->_params['creditor_id_value']);
            if ($this->_params['creditor_id_op'] == 'in') {
                $where_clauses[] = "sdd_collection_forecast.creditor_id IN ($values)";
            } else {
                $where_clauses[] = "sdd_collection_forecast.creditor_id NOT IN ($values)";
            }
        }

        $this->_where = 'WHERE (' . implode(') AND (', $where_clauses) . ')';
        $this->_having = "";
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
     * Get the start date of the given period, e.g.
     *   first of next week/month/quarter/year
     *
     * @return string start date of the next period
     */
    protected function getAlignedStartDate()
    {
        // start with now:
        $start_date = strtotime('now');

        // add collection date grouping
        if (!empty($this->_params['group_bys']['collection_date'])) {
            switch ($this->_params['group_bys_freq']['collection_date']) {
                case 'YEARWEEK':
                    while (date('w', $start_date) <> 0) {
                        $start_date = strtotime("+1 day", $start_date);
                    }
                    break;

                default:
                case 'MONTH':
                    while (date('d', $start_date) <> 1) {
                        $start_date = strtotime("+1 day", $start_date);
                    }
                    break;

                case 'QUARTER':
                    while (date('d', $start_date) <> 1 && !in_array(date('m', $start_date), [1,4,7,10])) {
                        $start_date = strtotime("+1 day", $start_date);
                    }
                    break;

                case 'YEAR':
                    $start_date = strtotime(date('Y', $start_date) . '-01-01 + 1 year');
                    break;
            }
        } else { // default is month
            while (date('d', $start_date) <> 1) {
                $start_date = strtotime("+1 day", $start_date);
            }
        }
        return date('Y-m-d', $start_date);
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
     * Table name pattern is sdd_forecast_<creation>_<from>_<to>
     *
     * @param integer $horizon
     *     horizon / timeframe in days
     *
     * @param integer $max_age
     *     maximum age of table in seconds
     *
     * @return string
     *      name of the table to use
     */
    protected function getCollectionsTable($from_date, $to_date, $min_creation_time)
    {
        // some basic data
        $now           = strtotime('now');
        $today         = date('Y-m-d');
        $buffer        = '1 month';
        $table_pattern = '/^sdd_forecast_(?P<timestamp>[0-9]{14})_(?P<from>[0-9]{8})_(?P<to>[0-9]{8})$/';

        // normalise input
        $min_creation_time  = date('YmdHis', strtotime($min_creation_time));
        $from_date          = date('Ymd', strtotime($from_date));
        $min_date           = date('Y-m-d', strtotime($from_date));
        $to_date            = date('Ymd', strtotime($to_date));
        $max_date           = date('Y-m-d', strtotime($to_date));

        // step one: find existing collection tables
        $candidates = [];
        $DSN = DB::parseDSN(CRM_Core_Config::singleton()->dsn);
        $candidate_query = CRM_Core_DAO::executeQuery("SELECT table_name FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$DSN['database']}' AND TABLE_NAME LIKE 'sdd_forecast_%';");
        while ($candidate_query->fetch()) {
            $table_name = $candidate_query->table_name;
            if (preg_match($table_pattern, $table_name)) {
                $candidates[] = $table_name;
            }
        }

        // step one: delete all outdated tables
        $purge_date = date('YmdHis', strtotime('now -1 month'));
        foreach (array_keys($candidates) as $index) {
            $table_name = $candidates[$index];
            if (preg_match($table_pattern, $table_name, $match)) {
                if ($match['timestamp'] < $purge_date) {
                    // expired: delete that (old) table
                    CRM_Core_DAO::executeQuery("DROP TABLE `{$table_name}`;");
                    unset($candidates[$index]);
                }
            }
        }

        // step three: from the ones with the right horizon...
        $valid_candidates = [];
        foreach ($candidates as $table_name) {
            if (preg_match($table_pattern, $table_name, $match)) {
                if (   $match['timestamp'] >= $min_creation_time
                    && $match['from']      <= $from_date
                    && $match['to']        >= $to_date) {
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
        $creation_timestamp = date('YmdHis');
        $table_name         = "sdd_forecast_{$creation_timestamp}_{$from_date}_{$to_date}";

        $creation_timestamp = microtime(true);
        CRM_Core_DAO::executeQuery("
        CREATE TABLE `{$table_name}` (
         `mandate_id`          int unsigned        COMMENT 'ID of the mandate that created this',
         `contact_id`          int unsigned        COMMENT 'ID of the contact owning the mandate',
         `creditor_id`         int unsigned        COMMENT 'ID of the mandate creditor',
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
            mandate.creditor_id              AS mandate_creditor_id,
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
                'status'                 => $active_mandates->status,
            ];

            // calculate next collection
            $next_collection = CRM_Sepa_Logic_Batching::getNextExecutionDate(
                $mandate,
                $now,
                $mandate['status']
            );

            // calculate abortion date
            $abortion_date = $max_date;
            if (!empty($mandate['end_date'])) {
                $abortion_date = min($abortion_date, $mandate['end_date']);
            }
            if (!empty($mandate['cancel_date'])) {
                $abortion_date = min($abortion_date, $mandate['cancel_date']);
            }

            $collection_dates = [];
            while ($next_collection < $abortion_date) {
                // collect this one for writing out
                if ($next_collection >= $min_date) {
                    $collection_dates[] = $next_collection;
                }

                // move on to the next one
                $next_collection = date('Y-m-d', strtotime("{$next_collection} + {$mandate['frequency_interval']} {$mandate['frequency_unit']}"));
            }

            // write out
            if (!empty($collection_dates)) {
                $values = [];
                $template = "({$active_mandates->mandate_id},{$active_mandates->mandate_contact_id},{$active_mandates->mandate_creditor_id},{$active_mandates->rc_financial_type_id},{$active_mandates->rc_amount},DATE('%s'))";
                foreach ($collection_dates as $collection_date) {
                    // TODO: defer?
                    $values[] = sprintf($template, $collection_date);
                }
                // write out
                CRM_Core_DAO::executeQuery("INSERT INTO `{$table_name}`(mandate_id,contact_id,creditor_id,financial_type_id,amount,collection_date) VALUES " . implode(',',$values));
            }
        } // on to the next mandate

        // we're done:
        Civi::log()->debug(sprintf("Created SEPA Forecast data table '%s' in %.1fs", $table_name, microtime(true) - $creation_timestamp));
        return $table_name;
    }
}
