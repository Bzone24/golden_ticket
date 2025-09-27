(function () {
    "use strict";

    function escapeHtml(s) {
        if (s === undefined || s === null) return "";
        return String(s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function openPrintWindowAndPrint(html) {
        var w = window.open("", "", "width=300,height=600");
        if (!w) {
            alert(
                "Popup blocked — allow popups or use a printing bridge for silent printing."
            );
            return;
        }
        w.document.open();
        w.document.write(html);
        w.document.close();
        try {
            w.focus();
        } catch (e) {}
        setTimeout(function () {
            try {
                w.print();
            } catch (e) {
                console.error("print failed", e);
            }
            setTimeout(function () {
                try {
                    w.close();
                } catch (e) {}
            }, 500);
        }, 300);
    }

    function buildHtmlFromPayload(p) {
        p = p || {};
        var ticketNo = escapeHtml(p.ticket_number || "");
        var labels =
            p.labels && Array.isArray(p.labels)
                ? p.labels
                : p.labels
                ? [p.labels]
                : [];
        var times =
            p.times && Array.isArray(p.times)
                ? p.times
                : p.times
                ? [p.times]
                : [];
        var rows = Array.isArray(p.stored_options) ? p.stored_options : [];

        var rowsHtml = "";
        if (rows.length === 0) {
            rowsHtml =
                '<tr><td colspan="5" style="text-align:center;padding:6px;">No records found.</td></tr>';
        } else {
            for (var i = 0; i < rows.length; i++) {
                var r = rows[i];
                var opt = escapeHtml(r.option || r.opt || "");
                var num = escapeHtml(r.number || r.num || "");
                var qty = escapeHtml(r.qty || r.quantity || r.q || "");
                var total = escapeHtml(r.total || r.amt || r.amount || "");
                rowsHtml +=
                    "<tr>" +
                    '<td style="padding:3px 6px;">' +
                    (i + 1) +
                    "</td>" +
                    '<td style="padding:3px 6px;">' +
                    opt +
                    "</td>" +
                    '<td style="padding:3px 6px;">' +
                    num +
                    "</td>" +
                    '<td style="padding:3px 6px;">' +
                    qty +
                    "</td>" +
                    '<td style="padding:3px 6px; text-align:right;">' +
                    total +
                    "</td>" +
                    "</tr>";
            }
        }

        var tq = p.tq || 0;
        var total = p.total || 0;
        var finalTotal = p.finalTotal || 0;
        var drawCount = p.draw_count || 1;

        var html = "";
        html +=
            '<html><head><meta charset="utf-8"/><title>Ticket Print</title>';
        html +=
            "<style>@page{size:72mm auto;margin:0}html,body{width:72mm;margin:0;padding:6px;font-family:monospace;font-size:12px}.center{text-align:center}table{width:100%;border-collapse:collapse;margin-top:6px}thead th{padding:4px 0;font-weight:700;border-bottom:1px dashed #000}td{padding:3px 0}.dashed{border-top:1px dashed #000;margin:6px 0}.totals{font-weight:700;text-align:center;margin-top:6px}</style>";
        html += "</head><body>";
        html +=
            '<div class="center"><div><strong>Ticket No:</strong> ' +
            ticketNo +
            "</div>";
        html +=
            '<div style="margin-top:4px;">Game: ' +
            escapeHtml(labels.join(", ")) +
            " &nbsp;|&nbsp; Draw: " +
            escapeHtml(times.join(", ")) +
            "</div></div>";
        html += '<div class="dashed"></div>';
        html +=
            '<table><thead><tr><th style="width:8%;">#</th><th style="width:30%;">Option</th><th style="width:28%;">Number</th><th style="width:14%;">Qty</th><th style="width:20%;text-align:right;">Total</th></tr></thead>';
        html += "<tbody>" + rowsHtml + "</tbody></table>";
        html += '<div class="dashed"></div>';
        html +=
            '<div class="totals">TQ: ' +
            escapeHtml(tq) +
            " &nbsp;|&nbsp; Total: " +
            escapeHtml(total) +
            " &nbsp;|&nbsp; Final (× " +
            escapeHtml(drawCount) +
            " draws): " +
            escapeHtml(finalTotal) +
            "</div>";
        html += "</body></html>";

        return html;
    }

    function handlePayloadAndPrint(payload) {
        try {
            var html = buildHtmlFromPayload(payload || {});
            openPrintWindowAndPrint(html);
        } catch (e) {
            console.error("Print handler error", e);
            alert("Print failed: see console for details.");
        }
    }

    // Listen for Livewire event
    if (window.Livewire && typeof window.Livewire.on === "function") {
        Livewire.on("ticketSubmitted", function (payload) {
            handlePayloadAndPrint(payload || {});
        });
    }

    // Listen for browser dispatched event
    window.addEventListener("ticketSubmitted", function (e) {
        var payload = e && e.detail ? e.detail : {};
        handlePayloadAndPrint(payload);
    });

    // Keyboard shortcut: Ctrl+F12 triggers submit and print
    document.addEventListener("keydown", function (e) {
        var isTrigger =
            (e.ctrlKey && e.key === "F12") || (e.key === "F12" && e.ctrlKey);
        if (!isTrigger) return;
        try {
            e.preventDefault();
        } catch (err) {}
        // Trigger submit: try to click the submit button first
        var submitBtn = document.querySelector('[wire\\:click="submitTicket"]');
        if (submitBtn) {
            try {
                submitBtn.click();
                return;
            } catch (err) {
                /* ignore */
            }
        }
        // Fallback to Livewire.emit if available
        if (window.Livewire && typeof Livewire.emit === "function") {
            try {
                Livewire.emit("submitTicket");
            } catch (err) {
                console.warn(err);
            }
        }
    });
})();
