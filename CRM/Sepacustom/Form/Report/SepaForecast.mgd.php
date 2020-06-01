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

return [
    [
        'name'   => 'CRM_Sepacustom_Form_Report_SepaForecast',
        'entity' => 'ReportTemplate',
        'params' =>
            [
                'version'     => 3,
                'label'       => E::ts("Sepa Forecast"),
                'description' => E::ts("Forecast of CiviSEPA collections"),
                'class_name'  => 'CRM_Sepacustom_Form_Report_SepaForecast',
                'report_url'  => 'de.systopia.sepacustom/sepaforecast',
                'component'   => 'CiviContribute',
            ],
    ],
];
