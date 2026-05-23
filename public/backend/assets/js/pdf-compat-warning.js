(function () {
    "use strict";

    function isPdfFile(file) {
        if (!file) return false;
        var mime = (file.type || "").toLowerCase();
        var name = (file.name || "").toLowerCase();
        return mime === "application/pdf" || name.endsWith(".pdf");
    }

    function parseIndicators(text) {
        var warnings = [];
        var versionMatch = text.match(/%PDF-(\d\.\d)/);
        if (versionMatch && versionMatch[1] && parseFloat(versionMatch[1]) >= 1.5) {
            warnings.push("Versi PDF " + versionMatch[1] + " terdeteksi (struktur modern dapat gagal diproses parser internal).");
        }
        if (text.indexOf("/ObjStm") !== -1) {
            warnings.push("Object Stream (/ObjStm) terdeteksi.");
        }
        if (text.indexOf("/Type/XRef") !== -1 || text.indexOf("/XRef") !== -1) {
            warnings.push("Cross-reference stream (XRef stream) terdeteksi.");
        }
        if (text.indexOf("/Encrypt") !== -1) {
            warnings.push("PDF terenkripsi/protected terdeteksi.");
        }
        return warnings;
    }

    function bytesToText(bytes) {
        var text = "";
        for (var i = 0; i < bytes.length; i++) {
            text += String.fromCharCode(bytes[i]);
        }
        return text;
    }

    function hideWarning(el) {
        if (!el) return;
        el.classList.add("d-none");
        el.innerHTML = "";
    }

    function showWarning(el, messages) {
        if (!el) return;
        if (!messages || !messages.length) {
            hideWarning(el);
            return;
        }
        var html = "<strong>Perhatian kompatibilitas PDF:</strong><br>" + messages.map(function (msg) {
            return "- " + msg;
        }).join("<br>");
        el.classList.remove("d-none");
        el.innerHTML = html;
    }

    function analyzePdf(file, warningEl) {
        if (!isPdfFile(file)) {
            hideWarning(warningEl);
            return;
        }

        var reader = new FileReader();
        var sample = file.slice(0, 1024 * 1024);
        reader.onload = function (event) {
            var bytes = new Uint8Array(event.target.result || []);
            if (!bytes.length) {
                hideWarning(warningEl);
                return;
            }

            var warnings = parseIndicators(bytesToText(bytes));
            if (warnings.length) {
                warnings.push("Jika finalisasi gagal, simpan ulang dengan Print to PDF lalu upload kembali.");
            }
            showWarning(warningEl, warnings);
        };
        reader.onerror = function () {
            hideWarning(warningEl);
        };
        reader.readAsArrayBuffer(sample);
    }

    window.initPdfCompatibilityWarning = function initPdfCompatibilityWarning(inputId, warningId) {
        var input = document.getElementById(inputId);
        var warningEl = document.getElementById(warningId);
        if (!input || !warningEl) return;

        input.addEventListener("change", function () {
            var file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                hideWarning(warningEl);
                return;
            }
            analyzePdf(file, warningEl);
        });
    };
})();
