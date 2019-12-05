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

/**
 * Hides all unused BIC restrictions (except for the first one
 */
function sepacustom_bic_restriction_resize() {
    let first_empty_shown = false;
    for (let i = 0; i <= CRM.vars.sepacustom.bic_restriction_count; i++) {
        let field = cj("[name=bic_restriction_creditor_" + i + "]");
        console.log(i);
        if (field.val() == '') {
            if (first_empty_shown) {
                console.log('hide');
                field.parent().parent().hide();
            } else {
                console.log('show');
                field.parent().parent().show();
                first_empty_shown = true;
            }
        }
    }
}

cj(document).ready(function() {
    cj(".sepacustom-bic-restriction-creditor").find("select").change(function() {
        sepacustom_bic_restriction_resize();
    });
    sepacustom_bic_restriction_resize();
   // console.log(CRM.vars.sepacustom.bic_restriction_count);
});