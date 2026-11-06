<?php /* BOF BBG Form No. 3 - Revised 08/2022 - WITHDRAWAL SLIP. Vars: $d (data), $ro (read-only) */ ?>
<div class="sl sl-wd" data-slip="withdrawal">
  <?= sl_head('BOF BBG Form No. 3', 'Revised 08/2022') ?>
  <div class="sl-bar">WITHDRAWAL SLIP</div>

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

  <div class="wd-recv">Received from Bank of Florida the sum of pesos</div>
  <div class="wd-l1"><?= sl_in($d, $ro, 'words1', 'wl') ?></div>
  <div class="wd-l2"><?= sl_in($d, $ro, 'words2', 'wl wl2') ?><span class="php-o">(PhP</span><?= sl_in($d, $ro, 'wd_php', 'wl wphp', 'text', 'inputmode="decimal"') ?><span class="php-c">)</span></div>

  <div class="wd-txt">which amount is withdrawn from the above stated account.<br>For joint account, I/we declare under the penalties of perjury that my/co-depositor(s)<br>is/are still living.</div>

  <div class="sg-pair">
    <div class="sgl"><?= sl_sig($d, $ro, 'sig_wd_depositor') ?><div class="sg-line"></div><div class="sg-lb">Signature over Printed Full Name</div></div>
    <div class="sgl"><div class="dt-in"><?= sl_in($d, $ro, 'wd_sig_date', 'dt', 'date') ?></div><div class="sg-line"></div><div class="sg-lb">Date</div></div>
  </div>

  <div class="sl-bar">AUTHORITY TO WITHDRAW</div>

  <div class="au-1"><span>I/We hereby authorize</span><?= sl_in($d, $ro, 'wd_auth_name', 'wl au-nm') ?></div>
  <div class="wd-txt au-2">whose signature appears below to withdraw the amount indicated herein from&nbsp; my/our<br>account.</div>

  <div class="sg-pair">
    <div class="sgl"><?= sl_sig($d, $ro, 'sig_authorized') ?><div class="sg-line"></div><div class="sg-lb">Signature over Printed Full Name<br>of Authorized Representative</div></div>
    <div class="sgl"><?= sl_sig($d, $ro, 'sig_depositors') ?><div class="sg-line"></div><div class="sg-lb">Signature over Printed Full Name/s<br>of Depositor/s</div></div>
  </div>

  <div class="sl-bar">CASH BREAKDOWN</div>

  <table class="sl-t t-den t-wdden">
    <colgroup><col style="width:6%"><col style="width:20.3%"><col style="width:20.3%"><col style="width:19.5%"><col style="width:6.8%"><col style="width:27.1%"></colgroup>
    <tr class="dh"><td colspan="2" class="dn-h">Denomination</td><td class="pc-h">No. of pcs.</td><td colspan="2" class="am-h">Amount in PhP</td><td class="rm" rowspan="13"><?= sl_remarks($d, $ro) ?></td></tr>
    <?= sl_denom_rows($d, $ro) ?>
    <tr class="tot"><td colspan="3" class="tot-lb">TOTAL WITHDRAWAL</td><?= sl_money($d, $ro, 'total', true) ?></tr>
  </table>

  <table class="sl-t t-wdbank"><tr>
    <td class="b1"><div class="lb">SV/Posted/Paid</div></td>
    <td class="b2"><div class="lb">Approved by</div></td>
    <td class="b3"><div class="lb">Checked by</div></td>
  </tr></table>

  <div class="wd-recipient">
    <div class="wd-must">Must be signed in the presence of the Teller</div>
    <div class="sg-line rc-line"></div>
    <div class="sg-lb">Signature over Printed Full Name<br>of Recipient</div>
  </div>
</div>
