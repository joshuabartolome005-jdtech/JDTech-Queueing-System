<?php /* BOF BBG Form No. 1 - Revised 08/2022 - CASH DEPOSIT SLIP. Vars: $d (data), $ro (read-only) */ ?>
<div class="sl sl-cash" data-slip="cash_deposit">
  <?= sl_head('BOF BBG Form No. 1', 'Revised 08/2022') ?>
  <div class="sl-bar">CASH DEPOSIT SLIP</div>

  <table class="sl-t t-name"><tr>
    <td class="c-name"><div class="lb">Account Name</div><?= sl_in($d, $ro, 'acct_name', 'nm') ?></td>
    <td class="c-date"><div class="lb">Date</div><?= sl_in($d, $ro, 'date', 'dt', 'date') ?></td>
  </tr></table>

  <table class="sl-t t-acct"><tr>
    <td class="ac-lb">Account<br>Number</td><?= sl_acct($d, $ro) ?>
  </tr></table>

  <div class="sl-valid"><div class="lb">Teller's Validation</div></div>

  <table class="sl-t t-types"><tr>
    <td class="ty1"><?= sl_cb($d, $ro, 'acct_type', 'kiddie_savings', 'Kiddie Savings') ?><?= sl_cb($d, $ro, 'acct_type', 'basic_savings', 'Basic Savings') ?><?= sl_cb($d, $ro, 'acct_type', 'easy_savings', 'Easy Savings') ?></td>
    <td class="ty2"><?= sl_cb($d, $ro, 'acct_type', 'smartsavings', 'SmartSavings') ?></td>
    <td class="ty3"><?= sl_cb($d, $ro, 'acct_type', 'personal_checking', 'Personal Checking') ?><?= sl_cb($d, $ro, 'acct_type', 'business_checking', 'Business Checking') ?></td>
    <td class="ty4"><?= sl_cb($d, $ro, 'acct_type', 'time_deposit', 'Time Deposit') ?></td>
  </tr></table>

  <table class="sl-t t-den">
    <colgroup><col style="width:5.6%"><col style="width:36.5%"><col style="width:24.9%"><col style="width:25.1%"><col style="width:7.9%"></colgroup>
    <tr class="dh"><td colspan="2" class="dn-h">Denomination</td><td class="pc-h">No. of pcs.</td><td colspan="2" class="am-h">Amount in PhP</td></tr>
    <?= sl_denom_rows($d, $ro) ?>
    <tr class="tot"><td colspan="3" class="tot-lb">TOTAL CASH DEPOSIT</td><?= sl_money($d, $ro, 'total', true) ?></tr>
  </table>

  <div class="sl-words"><div class="lb">Amount in Words</div><?= sl_ta($d, $ro, 'words', 'wd') ?></div>

  <table class="sl-t t-sign"><tr>
    <td class="sg1"><div class="lb">Signature over Printed Full Name of<br>Depositor</div><?= sl_sig($d, $ro, 'sig_depositor') ?></td>
    <td class="sg2"><div class="lb">Posted by</div></td>
    <td class="sg3"><div class="lb">Checked by</div></td>
  </tr></table>

  <div class="sl-foot">This deposit is subject to the terms and conditions covering this account.</div>
</div>
