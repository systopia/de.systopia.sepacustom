{*-------------------------------------------------------+
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
+-------------------------------------------------------*}

<h3>{ts domain="de.systopia.sepacustom"}Bank Holidays{/ts}</h3>

<div class="crm-section">
  <div class="label">{$form.bank_holidays.label}</div>
  <div class="content">{$form.bank_holidays.html}</div>
  <div class="clear"></div>
</div>

<h3>{ts domain="de.systopia.sepacustom"}BIC Restrictions{/ts}</h3>
<table>
{foreach from=$bic_restrictions item=i}
<tr class="sepacustom-bic-restriction sepacustom-bic-restriction-{$i}">
  <td>{ts domain="de.systopia.sepacustom"}For&nbsp;Creditor{/ts}</td>
  {capture assign="field_name"}bic_restriction_creditor_{$i}{/capture}
  <td class="sepacustom-bic-restriction-creditor">{$form.$field_name.html}</td>
  <td>{ts domain="de.systopia.sepacustom"}BICs&nbsp;must{/ts}</td>
  {capture assign="field_name"}bic_restriction_condition_{$i}{/capture}
  <td>{$form.$field_name.html}</td>
  {capture assign="field_name"}bic_restriction_regex_{$i}{/capture}
  <td>{$form.$field_name.html}</td>
  <td>{ts domain="de.systopia.sepacustom"}or&nbsp;error {/ts}</td>
  {capture assign="field_name"}bic_restriction_message_{$i}{/capture}
  <td>{$form.$field_name.html}</td>
  <td>{ts domain="de.systopia.sepacustom"}is&nbsp;displayed.{/ts}</td>
</tr>
{/foreach}
</table>

{* FOOTER *}
<div class="crm-submit-buttons">
{include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
