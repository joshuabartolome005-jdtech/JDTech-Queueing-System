<?php
/*
 * includes/slips.php
 * Renders the four bank slips exactly as the paper forms:
 *   cash_deposit  = BOF BBG Form No. 1 (Rev 08/2022)
 *   withdrawal    = BOF BBG Form No. 3 (Rev 08/2022)
 *   check_deposit = BOF BBG Form No. 2 (Rev 08/2022)
 *   ecpay         = BBG Form No. 53 (White) (New 04/2018)
 *

 */

const SLIP_TYPES = [
    'cash_deposit'  => 'Cash Deposit Slip',
    'withdrawal'    => 'Withdrawal Slip',
    'check_deposit' => 'Check Deposit Slip',
    'ecpay'         => 'ECPay Payment Slip',
];

function sl_h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function sl_cut($s, $n) {
    return preg_match('/^.{0,' . (int)$n . '}/us', $s, $m) ? $m[0] : substr($s, 0, $n);
}

function sl_v($d, $k) {
    return (isset($d[$k]) && is_string($d[$k])) ? $d[$k] : '';
}

function sl_in($d, $ro, $name, $cls = '', $type = 'text', $extra = '') {
    if ($ro && $type === 'date' && sl_v($d, $name) === '') $type = 'text'; 
    return '<input type="' . $type . '" class="sl-in ' . $cls . '" data-f="' . $name . '" value="'
        . sl_h(sl_v($d, $name)) . '" autocomplete="off"' . ($ro ? ' readonly tabindex="-1"' : '') . ' ' . $extra . '>';
}

function sl_ta($d, $ro, $name, $cls = '') {
    return '<textarea class="sl-in sl-ta ' . $cls . '" data-f="' . $name . '" autocomplete="off"'
        . ($ro ? ' readonly tabindex="-1"' : '') . '>' . sl_h(sl_v($d, $name)) . '</textarea>';
}

function sl_cb($d, $ro, $group, $value, $label) {
    $chk = (sl_v($d, $group) === $value) ? ' checked' : '';
    return '<label class="sl-cb"><input type="checkbox" data-f="' . $group . '" data-group="' . $group
        . '" value="' . $value . '"' . $chk . ($ro ? ' disabled' : '') . '><i></i><span>' . sl_h($label) . '</span></label>';
}

function sl_sig($d, $ro, $name) {
    $v = sl_v($d, $name);
    if ($ro) {
        if (preg_match('~^data:image/png;base64,[A-Za-z0-9+/=]+$~', $v)) {
            return '<img class="sl-sigimg" src="' . sl_h($v) . '" alt="signature">';
        }
        return '';
    }
    return '<div class="sl-sig"><canvas data-sig="' . $name . '" width="640" height="200"></canvas>'
        . '<button type="button" class="sl-sig-clear" data-clear="' . $name . '">Clear</button></div>';
}

function sl_money($d, $ro, $name, $computed = false) {
    $lock = ($ro || $computed) ? ' readonly tabindex="-1"' : '';
    $p = '<input type="text" class="sl-in sl-p" data-f="' . $name . '_p" value="' . sl_h(sl_v($d, $name . '_p'))
        . '" inputmode="decimal" autocomplete="off"' . $lock . '>';
    $c = '<input type="text" class="sl-in sl-c" data-f="' . $name . '_c" value="' . sl_h(sl_v($d, $name . '_c'))
        . '" inputmode="numeric" maxlength="2" autocomplete="off"' . $lock . '>';
    return '<td class="m-p">' . $p . '</td><td class="m-c">' . $c . '</td>';
}

function sl_acct($d, $ro) {
    $val = str_pad(substr(sl_v($d, 'acct'), 0, 12), 12, ' ');
    $map = [0, 1, 2, 'x', 3, 4, 'x', 5, 6, 7, 8, 9, 10, 'x', 11];
    $o = '';
    foreach ($map as $m) {
        if ($m === 'x') {
            $o .= '<td class="ac dk"></td>';
        } else {
            $ch = trim($val[$m]);
            $o .= '<td class="ac"><input type="text" class="ac-in" data-ac="' . $m . '" maxlength="1" inputmode="numeric" value="'
                . sl_h($ch) . '" autocomplete="off"' . ($ro ? ' readonly tabindex="-1"' : '') . '></td>';
        }
    }
    return $o;
}

function sl_denom_rows($d, $ro) {
    $bills = [1000 => '1,000', 500 => '500', 200 => '200', 100 => '100', 50 => '50', 20 => '20'];
    $coins = [20 => '20', 10 => '10', 5 => '5', 1 => '1'];
    $o = '';
    $i = 0;
    foreach ($bills as $den => $lab) {
        $key = 'b' . $den;
        $o .= '<tr class="dr">';
        if ($i === 0) $o .= '<td class="grp" rowspan="6"><span>B<br>I<br>L<br>L<br>S</span></td>';
        $o .= '<td class="dn">' . ($i === 0 ? '<b class="php">PhP</b>' : '') . '<b>' . $lab . '</b></td>';
        $o .= '<td class="pc"><input type="text" class="sl-in" data-f="pcs_' . $key . '" data-den="' . $den . '" value="'
            . sl_h(sl_v($d, 'pcs_' . $key)) . '" inputmode="numeric" maxlength="6" autocomplete="off"' . ($ro ? ' readonly tabindex="-1"' : '') . '></td>';
        $o .= sl_money($d, $ro, 'amt_' . $key, true);
        $o .= '</tr>';
        $i++;
    }
    $i = 0;
    foreach ($coins as $den => $lab) {
        $key = 'c' . $den;
        $o .= '<tr class="dr">';
        if ($i === 0) $o .= '<td class="grp" rowspan="4"><span>C<br>O<br>I<br>N<br>S</span></td>';
        $o .= '<td class="dn"><b>' . $lab . '</b></td>';
        $o .= '<td class="pc"><input type="text" class="sl-in" data-f="pcs_' . $key . '" data-den="' . $den . '" value="'
            . sl_h(sl_v($d, 'pcs_' . $key)) . '" inputmode="numeric" maxlength="6" autocomplete="off"' . ($ro ? ' readonly tabindex="-1"' : '') . '></td>';
        $o .= sl_money($d, $ro, 'amt_' . $key, true);
        $o .= '</tr>';
        $i++;
    }
 
    $o .= '<tr class="dr"><td class="dn oth" colspan="2"><b>Others:</b> '
        . sl_in($d, $ro, 'oth_label', 'oth-lb') . '</td>';
    $o .= '<td class="pc"><input type="text" class="sl-in" data-f="oth_pcs" value="' . sl_h(sl_v($d, 'oth_pcs'))
        . '" inputmode="numeric" maxlength="6" autocomplete="off"' . ($ro ? ' readonly tabindex="-1"' : '') . '></td>';
    $o .= sl_money($d, $ro, 'oth_amt', false) . '</tr>';
    return $o;
}

function sl_remarks($d, $ro) {
    return '<div class="rm-t">Remarks or Special<br>Instructions:</div>'
        . '<div class="rm-i"><span>1. Please issue<br>check payable to</span>' . sl_ta($d, $ro, 'wd_check_payable', 'rm-ta1') . '</div>'
        . '<div class="rm-i rm-2"><span>2. Other Instructions:</span>' . sl_ta($d, $ro, 'wd_instructions', 'rm-ta2') . '</div>';
}

function sl_head($formNo, $rev) {
    return '<div class="sl-head"><img class="sl-logo" src="assets/images/bof_logo.png" alt="BOF - A Rural Bank">'
        . '<div class="sl-fno">' . $formNo . '<br>' . $rev . '</div></div>';
}

function render_slip($type, $d = [], $ro = false) {
    if (!isset(SLIP_TYPES[$type])) return '';
    if (!is_array($d)) $d = [];
    ob_start();
    include __DIR__ . '/slips/' . $type . '.php';
    return ob_get_clean();
}

function sanitize_slip_json($raw) {
    $arr = json_decode((string)$raw, true);
    if (!is_array($arr)) return false;
    $clean = [];
    foreach ($arr as $k => $v) {
        if (!is_string($k) || !preg_match('/^[a-z0-9_]{1,40}$/', $k)) continue;
        if (!is_string($v)) continue;
        if (strpos($k, 'sig_') === 0) {
            if (preg_match('~^data:image/png;base64,[A-Za-z0-9+/=]+$~', $v) && strlen($v) <= 150000) $clean[$k] = $v;
            continue;
        }
        if ($k === 'acct') {
            $v = preg_replace('/[^0-9 ]/', ' ', substr($v, 0, 12));
            if (trim($v) === '') continue;
            $clean[$k] = $v;
            continue;
        }
        $v = trim($v);
        if ($v === '') continue;
        $clean[$k] = sl_cut($v, 300);
    }
    if (!$clean) return false;
    return json_encode($clean, JSON_UNESCAPED_UNICODE);
}
