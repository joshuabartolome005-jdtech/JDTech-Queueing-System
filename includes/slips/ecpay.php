<?php /* ECPay PAYMENT SLIP - BBG Form No. 53 (White) (New 04/2018). Vars: $d (data), $ro (read-only) */
$bills = ['1000' => '1,000.00', '500' => '500.00', '200' => '200.00', '100' => '100.00', '50' => '50.00', '20' => '20.00'];
$coins = ['10' => '10.00', '5' => '5.00', '1' => '1.00'];
?>
<div class="sl sl-ec" data-slip="ecpay">
  <div class="ec-head">
    <div class="ec-title">ECPay PAYMENT SLIP</div>
    <div class="ec-form">BBG Form No. 53 (White)<br>(New 04/2018)</div>
    <div class="ec-logo"><img src="assets/images/bof_logo_white.png" alt="BOF"><small>BANK OF FLORIDA</small></div>
  </div>

  <div class="ec-blank"></div>
  <div class="ec-valid">Teller's Validation:</div>
  <div class="ec-terms">This payment is made and accepted subject to the terms and conditions covering the account.</div>

  <table class="sl-t ec-f">
    <tr><td class="l"><div class="lb">Company/Biller Name:</div><?= sl_in($d, $ro, 'biller', 'ef') ?></td><td class="r"><div class="lb">Date:</div><?= sl_in($d, $ro, 'date', 'ef', 'date') ?></td></tr>
    <tr><td class="l"><div class="lb">Subscriber's Account No.:</div><?= sl_in($d, $ro, 'sub_acct', 'ef') ?></td><td class="r"><div class="lb">Reference No: <i>(if applicable)</i></div><?= sl_in($d, $ro, 'ref_no', 'ef') ?></td></tr>
    <tr><td class="l"><div class="lb">Subscriber's Name:</div><?= sl_in($d, $ro, 'sub_name', 'ef') ?></td><td class="r"><div class="lb">Mobile/Telephone No: <i>(if applicable)</i></div><?= sl_in($d, $ro, 'mobile', 'ef', 'text', 'inputmode="tel"') ?></td></tr>
  </table>

  <table class="sl-t ec-b"><tr>
    <td class="bank">
      <div class="bk-t">(TO BE FILLED UP BY BANK)</div>
      <div class="bk-l"><span>Amount Due:</span><u></u></div>
      <div class="bk-l bk-s"><span>Convenience Fee/<br>Service Charge</span><u></u></div>
      <div class="bk-l"><span>Total Amount Due:</span><u class="dbl"></u></div>
      <div class="bk-l bk-sig"><span>Signature:</span><u></u></div>
      <div class="bk-note">Note:<br>In absence of Statement of Account<br>(SOA), fill out in duplicate copy.</div>
    </td>
    <td class="brk">
      <table class="ec-d">
        <colgroup><col style="width:9%"><col style="width:42%"><col style="width:16%"><col style="width:25%"><col style="width:8%"></colgroup>
        <tr class="ec-h1"><td colspan="5">CASH PAYMENT BREAKDOWN</td></tr>
        <tr class="ec-h2"><td colspan="2">Denomination</td><td>Pieces</td><td colspan="2">Amount</td></tr>
        <?php $i = 0; foreach ($bills as $den => $lab): ?>
        <tr class="dr">
          <?php if ($i === 0): ?><td class="grp" rowspan="6"><span>B<br>I<br>L<br>L<br>S</span></td><?php endif; ?>
          <td class="dn"><?= $i === 0 ? '<b class="php">PhP</b>' : '' ?><b class="dv"><?= $lab ?></b></td>
          <td class="pc"><input type="text" class="sl-in" data-f="pcs_b<?= $den ?>" data-den="<?= $den ?>" value="<?= sl_h(sl_v($d, 'pcs_b' . $den)) ?>" inputmode="numeric" maxlength="6" autocomplete="off"<?= $ro ? ' readonly tabindex="-1"' : '' ?>></td>
          <td class="am"><?= $i === 0 ? '<b class="php">PhP</b>' : '' ?><input type="text" class="sl-in" data-f="amt_b<?= $den ?>_p" value="<?= sl_h(sl_v($d, 'amt_b' . $den . '_p')) ?>" readonly tabindex="-1"></td>
          <td class="cc"><input type="text" class="sl-in" data-f="amt_b<?= $den ?>_c" value="<?= sl_h(sl_v($d, 'amt_b' . $den . '_c')) ?>" readonly tabindex="-1"></td>
        </tr>
        <?php $i++; endforeach; ?>
        <?php $i = 0; foreach ($coins as $den => $lab): ?>
        <tr class="dr">
          <?php if ($i === 0): ?><td class="grp" rowspan="4"><span>C<br>O<br>I<br>N<br>S</span></td><?php endif; ?>
          <td class="dn"><b class="dv"><?= $lab ?></b></td>
          <td class="pc"><input type="text" class="sl-in" data-f="pcs_c<?= $den ?>" data-den="<?= $den ?>" value="<?= sl_h(sl_v($d, 'pcs_c' . $den)) ?>" inputmode="numeric" maxlength="6" autocomplete="off"<?= $ro ? ' readonly tabindex="-1"' : '' ?>></td>
          <td class="am"><input type="text" class="sl-in" data-f="amt_c<?= $den ?>_p" value="<?= sl_h(sl_v($d, 'amt_c' . $den . '_p')) ?>" readonly tabindex="-1"></td>
          <td class="cc"><input type="text" class="sl-in" data-f="amt_c<?= $den ?>_c" value="<?= sl_h(sl_v($d, 'amt_c' . $den . '_c')) ?>" readonly tabindex="-1"></td>
        </tr>
        <?php $i++; endforeach; ?>
        <tr class="dr">
          <td class="dn oth"><b>OTHERS</b></td>
          <td class="pc"><input type="text" class="sl-in" data-f="oth_pcs" value="<?= sl_h(sl_v($d, 'oth_pcs')) ?>" inputmode="numeric" maxlength="6" autocomplete="off"<?= $ro ? ' readonly tabindex="-1"' : '' ?>></td>
          <td class="am"><input type="text" class="sl-in sl-p" data-f="oth_amt_p" value="<?= sl_h(sl_v($d, 'oth_amt_p')) ?>" inputmode="decimal" autocomplete="off"<?= $ro ? ' readonly tabindex="-1"' : '' ?>></td>
          <td class="cc"><input type="text" class="sl-in sl-c" data-f="oth_amt_c" value="<?= sl_h(sl_v($d, 'oth_amt_c')) ?>" inputmode="numeric" maxlength="2" autocomplete="off"<?= $ro ? ' readonly tabindex="-1"' : '' ?>></td>
        </tr>
        <tr class="tot">
          <td colspan="3" class="tot-lb">TOTAL CASH PAYMENT</td>
          <td class="am"><b class="php">PhP</b><input type="text" class="sl-in" data-f="total_p" value="<?= sl_h(sl_v($d, 'total_p')) ?>" readonly tabindex="-1"></td>
          <td class="cc"><input type="text" class="sl-in" data-f="total_c" value="<?= sl_h(sl_v($d, 'total_c')) ?>" readonly tabindex="-1"></td>
        </tr>
      </table>
    </td>
  </tr></table>
</div>
