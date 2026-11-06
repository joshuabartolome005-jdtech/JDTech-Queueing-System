
(function () {
    'use strict';

    var ONES = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve',
        'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    var TENS = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    var SCALES = ['', ' Thousand', ' Million', ' Billion'];

    function below1000(n) {
        var s = '';
        if (n >= 100) { s += ONES[Math.floor(n / 100)] + ' Hundred'; n = n % 100; if (n) s += ' '; }
        if (n >= 20) { s += TENS[Math.floor(n / 10)]; if (n % 10) s += '-' + ONES[n % 10]; }
        else if (n > 0) { s += ONES[n]; }
        return s;
    }

    function intWords(n) {
        if (n === 0) return 'Zero';
        var parts = [], i = 0;
        while (n > 0 && i < SCALES.length) {
            var chunk = n % 1000;
            if (chunk) parts.unshift(below1000(chunk) + SCALES[i]);
            n = Math.floor(n / 1000); i++;
        }
        return parts.join(' ');
    }

    function pesoWords(amount) {
        var cents = Math.round(amount * 100);
        var pesos = Math.floor(cents / 100), c = cents % 100;
        if (cents <= 0) return '';
        var w = pesos > 0 ? intWords(pesos) + (pesos === 1 ? ' Peso' : ' Pesos') : '';
        if (c > 0) w += (w ? ' and ' : '') + (c < 10 ? '0' : '') + c + '/100';
        if (!pesos && c) w += ' Centavos';
        return w + ' Only';
    }

    function fmt(n) { return Math.floor(n).toLocaleString('en-US'); }
    function num(v) {
        v = String(v == null ? '' : v).replace(/[^0-9.]/g, '');
        var f = parseFloat(v);
        return isNaN(f) ? 0 : f;
    }
    function q(root, sel) { return Array.prototype.slice.call(root.querySelectorAll(sel)); }
    function f(root, name) { return root.querySelector('[data-f="' + name + '"]'); }

    function todayManila() {
        var p = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Manila', year: 'numeric', month: '2-digit', day: '2-digit' }).format(new Date());
        return p; 
    }

    function pairValue(root, base) {
        var p = f(root, base + '_p'), c = f(root, base + '_c');
        var pesos = p ? num(p.value) : 0;
        var cents = c ? parseInt(String(c.value).replace(/\D/g, '').padEnd(2, '0').slice(0, 2) || '0', 10) : 0;
        return pesos + cents / 100;
    }

    function setPair(root, base, value, show) {
        var p = f(root, base + '_p'), c = f(root, base + '_c');
        if (!p || !c) return;
        if (!show) { p.value = ''; c.value = ''; return; }
        var cents = Math.round(value * 100);
        p.value = fmt(cents / 100);
        c.value = ('0' + (cents % 100)).slice(-2);
    }

    function recompute(root) {
        var total = 0;
        q(root, '[data-den]').forEach(function (inp) {
            var key = inp.getAttribute('data-f').replace('pcs_', '');
            var pcs = parseInt(String(inp.value).replace(/\D/g, ''), 10);
            var amt = (isNaN(pcs) ? 0 : pcs) * parseInt(inp.getAttribute('data-den'), 10);
            setPair(root, 'amt_' + key, amt, !isNaN(pcs) && inp.value !== '');
            total += amt;
        });

        if (f(root, 'oth_amt_p')) total += pairValue(root, 'oth_amt');

        for (var i = 1; i <= 10; i++) { if (f(root, 'chk' + i + '_amt_p')) total += pairValue(root, 'chk' + i + '_amt'); }
        total = Math.round(total * 100) / 100;
        setPair(root, 'total', total, total > 0);

        var type = root.getAttribute('data-slip');
        var words = (type === 'withdrawal' || type === 'cash_deposit' || type === 'check_deposit');
        var source = total;
        if (type === 'withdrawal') {
            var wp = f(root, 'wd_php');
            if (!root._phpManual) {
                wp.value = total > 0 ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '';
            }
            source = wp.value ? num(wp.value) : total;
        }
        if (words && !root._wordsManual) {
            var w = pesoWords(source);
            if (type === 'withdrawal') {
                var w1 = f(root, 'words1'), w2 = f(root, 'words2');
                var cut = w.length > 62 ? w.lastIndexOf(' ', 62) : -1;
                if (cut > 0) { w1.value = w.slice(0, cut); w2.value = w.slice(cut + 1); } else { w1.value = w; w2.value = ''; }
            } else {
                f(root, 'words').value = w;
            }
        }
    }

    function initAccount(root) {
        var cells = q(root, '.ac-in');
        cells.forEach(function (c, i) {
            c.addEventListener('input', function () {
                c.value = c.value.replace(/\D/g, '').slice(-1);
                if (c.value && cells[i + 1]) cells[i + 1].focus();
            });
            c.addEventListener('keydown', function (e) {
                if (e.key === 'Backspace' && !c.value && cells[i - 1]) { cells[i - 1].focus(); }
                if (e.key === 'ArrowLeft' && cells[i - 1]) cells[i - 1].focus();
                if (e.key === 'ArrowRight' && cells[i + 1]) cells[i + 1].focus();
            });
            c.addEventListener('focus', function () { c.select(); });
            c.addEventListener('paste', function (e) {
                var t = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (!t) return;
                e.preventDefault();
                for (var k = 0; k < t.length && cells[i + k]; k++) cells[i + k].value = t[k];
            });
        });
    }

    function initGroups(root) {
        q(root, 'input[type=checkbox][data-group]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (!cb.checked) return;
                q(root, 'input[type=checkbox][data-group="' + cb.getAttribute('data-group') + '"]').forEach(function (o) {
                    if (o !== cb) o.checked = false;
                });
            });
        });
    }

    function initSignatures(root) {
        q(root, 'canvas[data-sig]').forEach(function (cv) {
            var ctx = cv.getContext('2d'), drawing = false, last = null;
            cv._dirty = false;
            function pos(e) {
                var r = cv.getBoundingClientRect();
                return { x: (e.clientX - r.left) * (cv.width / r.width), y: (e.clientY - r.top) * (cv.height / r.height) };
            }
            cv.addEventListener('pointerdown', function (e) {
                drawing = true; last = pos(e); cv.setPointerCapture(e.pointerId); e.preventDefault();
            });
            cv.addEventListener('pointermove', function (e) {
                if (!drawing) return;
                var p = pos(e);
                ctx.strokeStyle = '#0a1a3d'; ctx.lineWidth = 3; ctx.lineCap = 'round'; ctx.lineJoin = 'round';
                ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
                last = p; cv._dirty = true;
            });
            function end() { drawing = false; }
            cv.addEventListener('pointerup', end);
            cv.addEventListener('pointercancel', end);
            var clear = root.querySelector('[data-clear="' + cv.getAttribute('data-sig') + '"]');
            if (clear) clear.addEventListener('click', function () {
                ctx.clearRect(0, 0, cv.width, cv.height); cv._dirty = false;
            });
        });
    }

    function sizeCanvases(root) {
        q(root, 'canvas[data-sig]').forEach(function (cv) {
            if (cv._dirty) return;
            var w = cv.offsetWidth, h = cv.offsetHeight;
            if (w > 0 && h > 0) { cv.width = w * 2; cv.height = h * 2; }
        });
    }

    function initSlip(root) {
        if (root._ready) return;
        root._ready = true;
        initAccount(root);
        initGroups(root);
        initSignatures(root);

        q(root, '[data-den],[data-f="oth_pcs"],.sl-c').forEach(function (inp) {
            inp.addEventListener('input', function () { inp.value = inp.value.replace(/\D/g, ''); recompute(root); });
        });
        q(root, '.sl-p:not([readonly])').forEach(function (inp) {
            inp.addEventListener('input', function () { inp.value = inp.value.replace(/[^\d]/g, ''); recompute(root); });
            inp.addEventListener('blur', function () { if (inp.value) inp.value = fmt(num(inp.value)); recompute(root); });
            inp.addEventListener('focus', function () { inp.value = inp.value.replace(/,/g, ''); });
        });
        var words = f(root, 'words'), w1 = f(root, 'words1'), w2 = f(root, 'words2'), php = f(root, 'wd_php');
        [words, w1, w2].forEach(function (w) {
            if (!w) return;
            w.addEventListener('input', function () {
                root._wordsManual = !!((words && words.value) || (w1 && w1.value) || (w2 && w2.value));
            });
        });
        if (php) php.addEventListener('input', function () {
            php.value = php.value.replace(/[^0-9.,]/g, '');
            root._phpManual = php.value !== '';
            recompute(root);
        });

        var date = f(root, 'date');
        if (date && !date.value) date.value = todayManila();
        recompute(root);
    }

    function collect(root) {
        var o = {};
        q(root, '[data-f]').forEach(function (el) {
            var name = el.getAttribute('data-f');
            if (el.type === 'checkbox') { if (el.checked) o[el.getAttribute('data-group') || name] = el.value; return; }
            var v = String(el.value || '').trim();
            if (v !== '') o[name] = v;
        });
        var cells = q(root, '.ac-in'), s = '';
        cells.forEach(function (c) { s += c.value ? c.value : ' '; });
        if (s.trim() !== '') o.acct = s;
        q(root, 'canvas[data-sig]').forEach(function (cv) {
            if (cv._dirty) o[cv.getAttribute('data-sig')] = cv.toDataURL('image/png');
        });
        return o;
    }

    function validate(root, data) {
        var type = root.getAttribute('data-slip');
        var total = pairValue(root, 'total');
        if (type === 'ecpay') {
            if (!data.biller) return 'Please enter the Company/Biller Name.';
            if (!data.sub_name) return "Please enter the Subscriber's Name.";
            if (!(total > 0)) return 'Please enter the number of pieces in the Cash Payment Breakdown.';
            return '';
        }
        if (!data.acct_name) return 'Please enter the Account Name.';
        if (type === 'withdrawal') {
            if (!(total > 0) && !data.wd_php) return 'Please enter the amount to withdraw.';
        } else if (!(total > 0)) {
            return type === 'check_deposit' ? 'Please enter the amount of the check/s.' : 'Please enter the number of pieces in the cash breakdown.';
        }
        return '';
    }

    function fit(container, slip, maxZoom) {
        slip.style.zoom = 1;
        var avail = container.clientWidth - 16;
        var z = Math.min(maxZoom || 1.4, avail / slip.offsetWidth);
        slip.style.zoom = z > 0.2 ? z : 0.2;
    }

    window.BankSlips = { init: initSlip, collect: collect, validate: validate, fit: fit, sizeCanvases: sizeCanvases, pesoWords: pesoWords, recompute: recompute };
})();
