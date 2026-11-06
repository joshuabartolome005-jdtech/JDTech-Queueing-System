<?php /* BOF BBG Form No. 2 - Revised 08/2022 - CHECK DEPOSIT SLIP. Vars: $d (data), $ro (read-only) */ ?>
<div class="sl sl-check" data-slip="check_deposit">
  <?= sl_head('BOF BBG Form No. 2', 'Revised 08/2022') ?>
  <div class="sl-bar">CHECK DEPOSIT SLIP</div>

  <table class="sl-t t-name"><tr>
    <td class="c-name"><div class="lb">Account Name</div><?= sl_in($d, $ro, 'acct_name', 'nm') ?></td>
    <td class="c-date"><div class="lb">Date</div><?= sl_in($d, $ro, 'date', 'dt', 'date') ?></td>
  </tr></table>

  <table class="sl-t t-acct"><tr>
    <td class="ac-lb">Account<br>Number</td><?= sl_acct($d, $ro) ?>
  </tr></table>

  <div class="sl-valid"><div class="lb">Teller's Validation</div></div>

  <table class="sl-t t-types"><tr>
    <td class="ty1"><?= sl_cb($d, $ro, 'acct_type', 'kiddie_savings', 'Kiddie Savings') ?><?= sl_cb($d, $ro, 'acct_type', 'easy_savings', 'Easy Savings') ?></td>
    <td class="ty2"><?= sl_cb($d, $ro, 'acct_type', 'smartsavings', 'SmartSavings') ?></td>
    <td class="ty3"><?= sl_cb($d, $ro, 'acct_type', 'personal_checking', 'Personal Checking') ?><?= sl_cb($d, $ro, 'acct_type', 'business_checking', 'Business Checking') ?></td>
    <td class="ty4"><?= sl_cb($d, $ro, 'acct_type', 'time_deposit', 'Time Deposit') ?></td>
  </tr></table>

  <div class="sl-note">Please use separate deposit slips for each type of check deposit and put a check mark on the box corresponding to the type of check deposit.
    <div class="ck-types">
      <div><?= sl_cb($d, $ro, 'check_type', 'in_house', 'In-house Check') ?><?= sl_cb($d, $ro, 'check_type', 'local', 'Local Checks') ?></div>
      <div><?= sl_cb($d, $ro, 'check_type', 'on_us', 'On-us Checks') ?><?= sl_cb($d, $ro, 'check_type', 'regional', 'Regional Checks') ?></div>
    </div>
  </div>

  <table class="sl-t t-chk">
    <colgroup><col style="width:21.8%"><col style="width:21%"><col style="width:21%"><col style="width:29.6%"><col style="width:6.6%"></colgroup>
    <tr class="dh"><td class="bk-h">Bank Initial</td><td class="br-h">Branch</td><td class="no-h">Check No.</td><td colspan="2" class="am-h">Amount in PhP</td></tr>
    <?php for ($i = 1; $i <= 10; $i++): ?>
    <tr class="cr">
      <td class="bk"><span class="rn"><?= $i ?></span><?= sl_in($d, $ro, 'chk' . $i . '_bank', 'ck') ?></td>
      <td class="br"><?= sl_in($d, $ro, 'chk' . $i . '_branch', 'ck') ?></td>
      <td class="no"><?= sl_in($d, $ro, 'chk' . $i . '_no', 'ck') ?></td>
      <?= sl_money($d, $ro, 'chk' . $i . '_amt', false) ?>
    </tr>
    <?php endfor; ?>
    <tr class="tot"><td colspan="3" class="tot-lb">TOTAL CHECK DEPOSIT</td><?= sl_money($d, $ro, 'total', true) ?></tr>
  </table>

  <div class="sl-words"><div class="lb">Amount in Words</div><?= sl_ta($d, $ro, 'words', 'wd') ?></div>

  <table class="sl-t t-sign"><tr>
    <td class="sg1"><div class="lb">Signature over Printed Full Name of<br>Depositor</div><?= sl_sig($d, $ro, 'sig_depositor') ?></td>
    <td class="sg2"><div class="lb">Posted by</div></td>
    <td class="sg3"><div class="lb">Checked by</div></td>
  </tr></table>

  <div class="sl-foot">This deposit is subject to the terms and conditions covering this account.</div>
</div>
