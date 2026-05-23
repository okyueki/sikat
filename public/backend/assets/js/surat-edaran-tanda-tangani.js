(function () {
    "use strict";

    window.__suratEdaranUploadedImageUrl = window.__suratEdaranUploadedImageUrl || "";
    window.signatureCropper = window.signatureCropper || null;

    document.addEventListener("DOMContentLoaded", function () {
        var cfg = window.SuratEdaranTtdConfig || {};
        if (typeof pdfjsLib !== "undefined" && cfg.pdfWorkerUrl) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = cfg.pdfWorkerUrl;
        }

        var pdfUrl = cfg.pdfUrl || "";
        var canvas = document.getElementById("pdf-canvas");
        if (!canvas) return;
        var ctx = canvas.getContext("2d");
        var pageSelect = document.getElementById("page-select");
        var dropZone = document.getElementById("pdf-drop-zone");
        var placementBoxes = document.getElementById("placement-boxes");
        var currentPage = 1;
        var pdfDoc = null;
        var scale = 1.4;
        var pageHeight = 0,
            pageWidth = 0;
        var MM_TO_PX = 2.83465;
        var placements = Array.isArray(cfg.placements) ? cfg.placements : [];
        var stempelUrl = cfg.stempelUrl || null;
        var selectedBox = null;
        var resizing = null;
        var teksEditPlacementIndex = -1;
        var LABELS = { signature: "Tanda tangan", inisial: "Inisial", nama: "Nama", tanggal: "Tanggal", teks: "Teks", stempel: "Stempel" };
        var isDirty = false;
        var dragGhost = document.getElementById("drag-ghost");
        var currentDragging = null;
        var selectedPlacementIndex = -1;
        var snapEnabled = false;
        var GRID_SIZE = 10;

        function getDisplayValue(fieldType) {
            if (fieldType === "signature") return document.getElementById("nama_lengkap").value || "Tanda tangan";
            if (fieldType === "inisial") return document.getElementById("inisial").value || "Inisial";
            if (fieldType === "nama") return document.getElementById("nama_lengkap").value || "Nama penandatangan";
            if (fieldType === "tanggal") return new Date().toLocaleDateString("id-ID");
            return LABELS[fieldType] || fieldType;
        }

        document.querySelectorAll(".jenis-option").forEach(function (el) {
            el.addEventListener("click", function () {
                if (this.classList.contains("opacity-50")) return;
                document.querySelectorAll(".jenis-option").forEach(function (x) { x.classList.remove("active"); });
                this.classList.add("active");
            });
        });

        document.getElementById("nama_lengkap").addEventListener("input", function () {
            document.getElementById("preview-signature").textContent = this.value || "Tanda tangan";
        });
        document.getElementById("inisial").addEventListener("input", function () {
            document.getElementById("preview-inisial").textContent = this.value || "Inisial";
        });

        var modalEl = document.getElementById("modalSignatureDetail");
        var modalNama = document.getElementById("modal_nama_lengkap");
        var modalInisial = document.getElementById("modal_inisial");
        var modalApply = document.getElementById("modal-signature-apply");

        function updateModalPreviewNames() {
            var name = modalNama.value || "Okyanto Agung K";
            document.querySelectorAll("#modal-font-options .modal-preview-name").forEach(function (el) { el.textContent = name; });
        }

        document.querySelectorAll(".signature-type-option").forEach(function (opt) {
            opt.addEventListener("click", function () {
                document.querySelectorAll(".signature-type-option").forEach(function (o) { o.classList.remove("active"); });
                this.classList.add("active");
                var currentSignatureType = this.dataset.type;
                document.getElementById("signature_type").value = currentSignatureType;
                document.getElementById("signature-type-text-content").style.display = currentSignatureType === "text" ? "block" : "none";
                document.getElementById("signature-type-image-content").style.display = currentSignatureType === "image" ? "block" : "none";
            });
        });

        document.getElementById("modal_file_ttd").addEventListener("change", function (e) {
            var file = e.target.files[0];
            if (!file) return;
            if (!file.type.match("image/png")) {
                alert("Hanya file PNG yang diperbolehkan.");
                this.value = "";
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert("Ukuran file maksimal 2 MB.");
                this.value = "";
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) {
                document.getElementById("signature-image-preview").src = ev.target.result;
                document.getElementById("signature-image-preview-container").style.display = "block";
                window.__suratEdaranUploadedImageUrl = ev.target.result;
            };
            reader.readAsDataURL(file);
        });

        document.getElementById("btn-open-signature-modal").addEventListener("click", function () {
            modalNama.value = document.getElementById("nama_lengkap").value;
            modalInisial.value = document.getElementById("inisial").value;
            var font = document.getElementById("font_style").value || "1";
            document.querySelectorAll('input[name="modal_font"]').forEach(function (r) { r.checked = (r.value === font); });
            document.querySelectorAll("#modal-font-options .modal-signature-option").forEach(function (o) { o.classList.toggle("selected", o.dataset.font === font); });
            var color = document.getElementById("color").value || "#000000";
            document.querySelectorAll("#modalSignatureDetail .color-dot").forEach(function (d) { d.classList.toggle("selected", (d.dataset.color || "").toLowerCase() === color.toLowerCase()); });
            var sigType = document.getElementById("signature_type").value || "text";
            document.querySelectorAll(".signature-type-option").forEach(function (o) { o.classList.toggle("active", o.dataset.type === sigType); });
            document.getElementById("signature-type-text-content").style.display = sigType === "text" ? "block" : "none";
            document.getElementById("signature-type-image-content").style.display = sigType === "image" ? "block" : "none";
            var existingUrl = document.getElementById("signature_image_url").value;
            if (existingUrl) {
                document.getElementById("signature-image-preview").src = existingUrl;
                document.getElementById("signature-image-preview-container").style.display = "block";
            }
            updateModalPreviewNames();
            if (typeof bootstrap !== "undefined" && bootstrap.Modal) new bootstrap.Modal(modalEl).show();
            else { modalEl.classList.add("show"); modalEl.style.display = "block"; }
        });

        modalNama.addEventListener("input", updateModalPreviewNames);
        document.querySelectorAll("#modal-font-options .modal-signature-option").forEach(function (opt) {
            opt.addEventListener("click", function () {
                var font = this.dataset.font;
                document.getElementById("font_style").value = font;
                document.querySelector('input[name="modal_font"][value="' + font + '"]').checked = true;
                document.querySelectorAll("#modal-font-options .modal-signature-option").forEach(function (o) { o.classList.remove("selected"); });
                this.classList.add("selected");
            });
        });
        document.querySelectorAll('input[name="modal_font"]').forEach(function (r) {
            r.addEventListener("change", function () {
                document.getElementById("font_style").value = this.value;
                document.querySelectorAll("#modal-font-options .modal-signature-option").forEach(function (o) { o.classList.remove("selected"); });
                var opt = document.querySelector('.modal-signature-option[data-font="' + this.value + '"]');
                if (opt) opt.classList.add("selected");
            });
        });
        document.querySelectorAll("#modalSignatureDetail .color-dot").forEach(function (dot) {
            dot.addEventListener("click", function () {
                document.querySelectorAll("#modalSignatureDetail .color-dot").forEach(function (d) { d.classList.remove("selected"); });
                this.classList.add("selected");
                document.getElementById("color").value = this.dataset.color;
            });
        });

        modalApply.addEventListener("click", function () {
            document.getElementById("nama_lengkap").value = modalNama.value;
            document.getElementById("inisial").value = modalInisial.value;
            var sigType = document.getElementById("signature_type").value || "text";
            var prevSig = document.getElementById("preview-signature");
            var prevIni = document.getElementById("preview-inisial");

            if (sigType === "image") {
                var croppedImage = document.getElementById("cropped_signature_image").value;
                var imgUrl = croppedImage || window.__suratEdaranUploadedImageUrl || document.getElementById("signature_image_url").value;
                if (!imgUrl) {
                    if (typeof Swal !== "undefined") Swal.fire({ icon: "warning", title: "Gambar Belum Dipilih", text: "Silakan upload gambar tanda tangan terlebih dahulu." });
                    else alert("Silakan upload gambar tanda tangan terlebih dahulu.");
                    return;
                }
                document.getElementById("signature_image_url").value = imgUrl;
                prevSig.innerHTML = '<img src="' + imgUrl + '" alt="TTD" style="max-height:24px;object-fit:contain;">';
                prevSig.className = "flex-grow-1";
                prevSig.style.color = "";
            } else {
                var font = document.querySelector('input[name="modal_font"]:checked');
                if (font) document.getElementById("font_style").value = font.value;
                var selDot = document.querySelector("#modalSignatureDetail .color-dot.selected");
                if (selDot) document.getElementById("color").value = selDot.dataset.color || "#000000";
                var fontClass = "signature-font-" + (document.getElementById("font_style").value || "1");
                var colorVal = document.getElementById("color").value || "#000000";
                prevSig.textContent = modalNama.value || "Tanda tangan";
                prevSig.className = "signature-cursive flex-grow-1 " + fontClass;
                prevSig.style.color = colorVal;
            }

            var fontClass2 = "signature-font-" + (document.getElementById("font_style").value || "1");
            var colorVal2 = document.getElementById("color").value || "#000000";
            prevIni.textContent = modalInisial.value || "Inisial";
            prevIni.className = "signature-cursive flex-grow-1 " + fontClass2;
            prevIni.style.color = colorVal2;
            renderPlacementBoxes();
            if (typeof bootstrap !== "undefined" && bootstrap.Modal) bootstrap.Modal.getInstance(modalEl).hide();
            else { modalEl.classList.remove("show"); modalEl.style.display = "none"; }
        });

        var pdfLoadingOverlay = document.getElementById("pdf-loading-overlay");
        pdfjsLib.getDocument(pdfUrl).promise.then(function (pdf) {
            pdfDoc = pdf;
            var n = pdf.numPages;
            document.getElementById("page-info").textContent = "Page 1 of " + n;
            for (var i = 1; i <= n; i++) {
                var o = document.createElement("option");
                o.value = i;
                o.textContent = "Halaman " + i;
                pageSelect.appendChild(o);
            }
            pageSelect.addEventListener("change", function () {
                currentPage = parseInt(this.value, 10);
                document.getElementById("page-info").textContent = "Page " + currentPage + " of " + n;
                renderPage(currentPage);
                renderPlacementBoxes();
            });
            renderPage(1);
            renderPlacementBoxes();
            if (pdfLoadingOverlay) pdfLoadingOverlay.style.display = "none";
        }).catch(function (err) {
            console.error("PDF Load Error:", err);
            if (pdfLoadingOverlay) pdfLoadingOverlay.innerHTML = '<div class="text-danger"><i class="fe fe-alert-circle" style="font-size:2rem;"></i><div class="loading-text">Gagal memuat PDF</div><small class="text-muted">Pastikan file PDF tersedia</small></div>';
        });

        window.addEventListener("beforeunload", function (e) {
            if (!isDirty) return;
            e.preventDefault();
            e.returnValue = "Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?";
            return e.returnValue;
        });

        function renderPage(num) {
            if (!pdfDoc) return;
            pdfDoc.getPage(num).then(function (page) {
                var viewport = page.getViewport({ scale: scale });
                pageWidth = viewport.width;
                pageHeight = viewport.height;
                canvas.width = pageWidth;
                canvas.height = pageHeight;
                dropZone.style.width = pageWidth + "px";
                dropZone.style.height = pageHeight + "px";
                placementBoxes.style.width = pageWidth + "px";
                placementBoxes.style.height = pageHeight + "px";
                page.render({ canvasContext: ctx, viewport: viewport });
                renderSnapGrid();
            });
        }

        var zoomLevels = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0];
        var currentZoomIndex = 2;
        var baseScale = 1.4;
        function updateZoom(newIndex) {
            if (newIndex < 0 || newIndex >= zoomLevels.length) return;
            currentZoomIndex = newIndex;
            var zoomFactor = zoomLevels[currentZoomIndex];
            scale = baseScale * zoomFactor;
            document.getElementById("zoom-level").textContent = Math.round(zoomFactor * 100) + "%";
            renderPage(currentPage);
            renderPlacementBoxes();
        }
        document.getElementById("zoom-in").addEventListener("click", function () { updateZoom(currentZoomIndex + 1); });
        document.getElementById("zoom-out").addEventListener("click", function () { updateZoom(currentZoomIndex - 1); });
        document.getElementById("zoom-fit").addEventListener("click", function () { updateZoom(2); });
        document.getElementById("snap-grid-toggle").addEventListener("change", function () {
            snapEnabled = this.checked;
            var grid = document.getElementById("snap-grid");
            if (grid) grid.classList.toggle("visible", snapEnabled);
        });

        function renderSnapGrid() {
            var grid = document.getElementById("snap-grid");
            if (!grid || !pageWidth || !pageHeight) return;
            grid.setAttribute("width", pageWidth);
            grid.setAttribute("height", pageHeight);
            grid.style.width = pageWidth + "px";
            grid.style.height = pageHeight + "px";
            var lines = "";
            var gridPx = mmToPx(GRID_SIZE);
            for (var x = gridPx; x < pageWidth; x += gridPx) lines += '<line class="grid-line-v" x1="' + x + '" y1="0" x2="' + x + '" y2="' + pageHeight + '" stroke-dasharray="2,4"/>';
            for (var y = gridPx; y < pageHeight; y += gridPx) lines += '<line class="grid-line-h" x1="0" y1="' + y + '" x2="' + pageWidth + '" y2="' + y + '" stroke-dasharray="2,4"/>';
            grid.innerHTML = lines;
        }

        function snapToGrid(valueMm) { return snapEnabled ? Math.round(valueMm / GRID_SIZE) * GRID_SIZE : valueMm; }
        function pxToMm(px) { return px / (MM_TO_PX * scale); }
        function mmToPx(mm) { return mm * MM_TO_PX * scale; }
        function showAlignGuide(type, positionMm) {
            var guide = document.getElementById("align-guide-" + type);
            if (!guide) return;
            guide.classList.add("visible");
            if (type === "h") guide.style.top = mmToPx(positionMm) + "px";
            else guide.style.left = mmToPx(positionMm) + "px";
        }
        function hideAlignGuides() {
            var h = document.getElementById("align-guide-h");
            var v = document.getElementById("align-guide-v");
            if (h) h.classList.remove("visible");
            if (v) v.classList.remove("visible");
        }
        function getDropCoordinates(e, element) {
            var rect = element.getBoundingClientRect();
            var xMm = Math.round(pxToMm(e.clientX - rect.left) * 100) / 100;
            var yMm = Math.round(pxToMm(e.clientY - rect.top) * 100) / 100;
            return { x: xMm, y: yMm };
        }
        function getSignatureStyle() {
            return {
                font: document.getElementById("font_style").value || "1",
                color: document.getElementById("color").value || "#000000",
                type: document.getElementById("signature_type").value || "text",
                imageUrl: document.getElementById("signature_image_url").value || "",
            };
        }

        function renderPlacementBoxes() {
            placementBoxes.innerHTML = "";
            placementBoxes.style.position = "absolute";
            placementBoxes.style.left = "0";
            placementBoxes.style.top = "0";
            placementBoxes.style.pointerEvents = "none";
            var style = getSignatureStyle();
            var onPage = placements.filter(function (p) { return p.page === currentPage; });
            onPage.forEach(function (p) {
                var globalIdx = placements.indexOf(p);
                var box = document.createElement("div");
                box.className = "placement-box" + (globalIdx === selectedPlacementIndex ? " selected" : "");
                box.style.left = mmToPx(p.x) + "px";
                box.style.top = mmToPx(p.y) + "px";
                box.style.width = (p.width ? mmToPx(p.width) : 80) + "px";
                box.style.height = (p.height ? mmToPx(p.height) : 20) + "px";
                box.style.pointerEvents = "auto";
                box.dataset.placementIndex = globalIdx;
                var text = p.value !== null && p.value !== "" ? p.value : getDisplayValue(p.field_type);
                var fontClass = (p.field_type === "signature" || p.field_type === "inisial") ? " signature-font-" + (p.font_style || style.font) : "";
                var colorStyle = (p.field_type === "signature" || p.field_type === "inisial") ? ("color:" + (p.color || style.color)) : "";
                var content = "";
                if (p.field_type === "stempel" && stempelUrl) {
                    box.classList.add("image-placement");
                    content = '<img src="' + stempelUrl + '" alt="Stempel" class="placement-stempel-img">';
                } else if (p.field_type === "stempel" && !stempelUrl) {
                    content = '<span class="placement-text" style="font-size:11px;color:#6c757d;">Stempel (belum diatur)</span>';
                } else if (p.field_type === "signature" && style.type === "image" && style.imageUrl) {
                    box.classList.add("image-placement");
                    content = '<img src="' + style.imageUrl + '" alt="TTD" class="placement-signature-img">';
                } else {
                    content = '<span class="placement-text' + fontClass + '" style="' + colorStyle + '">' + (p.field_type === "stempel" ? "Stempel" : (text || LABELS[p.field_type])) + "</span>";
                }
                box.innerHTML = '<button type="button" class="placement-remove" title="Hapus">&times;</button>' + content +
                    '<span class="placement-resize-handle nw" data-corner="nw"></span><span class="placement-resize-handle ne" data-corner="ne"></span><span class="placement-resize-handle sw" data-corner="sw"></span><span class="placement-resize-handle se" data-corner="se"></span>';
                box.querySelector(".placement-remove").addEventListener("click", function (e) {
                    e.stopPropagation();
                    var i = parseInt(box.dataset.placementIndex, 10);
                    if (i >= 0) { placements.splice(i, 1); isDirty = true; renderPlacementBoxes(); updateBadgeCount(); }
                });
                box.querySelectorAll(".placement-resize-handle").forEach(function (handle) {
                    handle.addEventListener("mousedown", function (e) {
                        e.stopPropagation();
                        e.preventDefault();
                        var i = parseInt(box.dataset.placementIndex, 10);
                        if (i < 0) return;
                        var pl = placements[i];
                        var layerRect = placementBoxes.getBoundingClientRect();
                        resizing = {
                            index: i,
                            corner: handle.dataset.corner,
                            startMmX: pxToMm(e.clientX - layerRect.left),
                            startMmY: pxToMm(e.clientY - layerRect.top),
                            startW: pl.width || 40,
                            startH: pl.height || 20,
                            startLeft: pl.x,
                            startTop: pl.y,
                        };
                    });
                });
                if (p.field_type === "teks") {
                    box.addEventListener("dblclick", function (e) {
                        if (e.target.classList.contains("placement-remove") || e.target.classList.contains("placement-resize-handle")) return;
                        teksEditPlacementIndex = globalIdx;
                        pendingTeksDrop = null;
                        document.getElementById("modal_teks_value").value = p.value || "";
                        var modalTeks = document.getElementById("modalTeksKustom");
                        if (typeof bootstrap !== "undefined" && bootstrap.Modal) new bootstrap.Modal(modalTeks).show();
                        else { modalTeks.classList.add("show"); modalTeks.style.display = "block"; }
                    });
                }
                placementBoxes.appendChild(box);
            });
            updateBadgeCount();
        }

        function updateBadgeCount() {
            var sigCount = placements.filter(function (p) { return p.field_type === "signature"; }).length;
            var otherCount = placements.filter(function (p) { return p.field_type !== "signature"; }).length;
            var currentPageCount = placements.filter(function (p) { return p.page === currentPage; }).length;
            var el = document.getElementById("count-signature");
            if (el) { el.textContent = sigCount; el.style.visibility = sigCount ? "visible" : "hidden"; }
            var sumSig = document.getElementById("summary-signature");
            var sumOther = document.getElementById("summary-other");
            var sumCurrent = document.getElementById("summary-current-page");
            if (sumSig) sumSig.textContent = sigCount;
            if (sumOther) sumOther.textContent = otherCount;
            if (sumCurrent) sumCurrent.textContent = currentPageCount;
        }

        document.querySelectorAll(".draggable-field").forEach(function (el) {
            el.addEventListener("dragstart", function (e) {
                var fieldType = el.dataset.fieldType;
                var displayValue = getDisplayValue(fieldType);
                e.dataTransfer.setData("fieldType", fieldType);
                e.dataTransfer.setData("displayValue", displayValue);
                e.dataTransfer.effectAllowed = "copy";
                currentDragging = { type: fieldType, display: displayValue };
                if (dragGhost) {
                    dragGhost.innerHTML = '<i class="fe fe-' + (fieldType === "signature" ? "edit-3" : fieldType === "tanggal" ? "calendar" : fieldType === "stempel" ? "bookmark" : "type") + ' me-1"></i>' + LABELS[fieldType];
                    dragGhost.classList.add("visible");
                }
                var img = new Image();
                img.src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";
                e.dataTransfer.setDragImage(img, 0, 0);
            });
            el.addEventListener("dragend", function () {
                if (dragGhost) dragGhost.classList.remove("visible");
                currentDragging = null;
            });
        });
        document.addEventListener("drag", function (e) {
            if (currentDragging && dragGhost && e.clientX > 0 && e.clientY > 0) {
                dragGhost.style.left = e.clientX + "px";
                dragGhost.style.top = e.clientY + "px";
            }
        });

        dropZone.addEventListener("dragover", function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = "copy";
            dropZone.classList.add("drag-over");
        });
        dropZone.addEventListener("dragleave", function () { dropZone.classList.remove("drag-over"); });
        var pendingTeksDrop = null;
        dropZone.addEventListener("drop", function (e) {
            e.preventDefault();
            dropZone.classList.remove("drag-over");
            if (dragGhost) dragGhost.classList.remove("visible");
            var fieldType = e.dataTransfer.getData("fieldType");
            var displayValue = e.dataTransfer.getData("displayValue");
            if (!fieldType) return;
            var coords = getDropCoordinates(e, dropZone);
            var w = 40, h = (fieldType === "signature" || fieldType === "inisial") ? 10 : 8;
            if (fieldType === "teks") {
                pendingTeksDrop = { x: coords.x, y: coords.y, page: currentPage, w: w, h: h };
                document.getElementById("modal_teks_value").value = "";
                var modalTeks = document.getElementById("modalTeksKustom");
                if (typeof bootstrap !== "undefined" && bootstrap.Modal) new bootstrap.Modal(modalTeks).show();
                else { modalTeks.classList.add("show"); modalTeks.style.display = "block"; }
                return;
            }
            placements.push({ field_type: fieldType, page: currentPage, x: coords.x, y: coords.y, width: w, height: h, value: fieldType === "tanggal" ? new Date().toLocaleDateString("id-ID") : (displayValue || null) });
            isDirty = true;
            renderPlacementBoxes();
        });

        document.getElementById("modal-teks-apply").addEventListener("click", function () {
            var val = document.getElementById("modal_teks_value").value.trim();
            if (pendingTeksDrop) {
                placements.push({ field_type: "teks", page: pendingTeksDrop.page, x: pendingTeksDrop.x, y: pendingTeksDrop.y, width: pendingTeksDrop.w || 40, height: pendingTeksDrop.h || 8, value: val || null });
                pendingTeksDrop = null;
                isDirty = true;
                renderPlacementBoxes();
            } else if (teksEditPlacementIndex >= 0) {
                placements[teksEditPlacementIndex].value = val || null;
                teksEditPlacementIndex = -1;
                isDirty = true;
                renderPlacementBoxes();
            }
            var modalTeks = document.getElementById("modalTeksKustom");
            if (typeof bootstrap !== "undefined" && bootstrap.Modal) { var inst = bootstrap.Modal.getInstance(modalTeks); if (inst) inst.hide(); }
            else { modalTeks.classList.remove("show"); modalTeks.style.display = "none"; }
        });

        placementBoxes.addEventListener("mousedown", function (e) {
            if (e.target.classList.contains("placement-resize-handle")) return;
            var box = e.target.closest(".placement-box");
            if (!box || e.target.classList.contains("placement-remove")) return;
            var idx = parseInt(box.dataset.placementIndex, 10);
            if (idx < 0 || placements[idx].page !== currentPage) return;
            selectedPlacementIndex = idx;
            renderPlacementBoxes();
            var layerRect = placementBoxes.getBoundingClientRect();
            selectedBox = {
                index: idx,
                startMmX: pxToMm(e.clientX - layerRect.left),
                startMmY: pxToMm(e.clientY - layerRect.top),
                startLeft: placements[idx].x,
                startTop: placements[idx].y,
            };
            e.preventDefault();
        });

        dropZone.addEventListener("click", function (e) {
            if (e.target === dropZone) { selectedPlacementIndex = -1; renderPlacementBoxes(); }
        });

        function checkAlignment(excludeIndex, x, y, w, h) {
            hideAlignGuides();
            var threshold = 3;
            var result = { x: x, y: y };
            placements.forEach(function (other, i) {
                if (i === excludeIndex || other.page !== currentPage) return;
                var ox = other.x, oy = other.y, ow = other.width || 40, oh = other.height || 20;
                if (Math.abs(y - oy) < threshold) { result.y = oy; showAlignGuide("h", oy); }
                if (Math.abs(y - (oy + oh)) < threshold) { result.y = oy + oh; showAlignGuide("h", oy + oh); }
                if (Math.abs((y + h) - oy) < threshold) { result.y = oy - h; showAlignGuide("h", oy); }
                if (Math.abs((y + h) - (oy + oh)) < threshold) { result.y = oy + oh - h; showAlignGuide("h", oy + oh); }
                if (Math.abs(x - ox) < threshold) { result.x = ox; showAlignGuide("v", ox); }
                if (Math.abs(x - (ox + ow)) < threshold) { result.x = ox + ow; showAlignGuide("v", ox + ow); }
                if (Math.abs((x + w) - ox) < threshold) { result.x = ox - w; showAlignGuide("v", ox); }
                if (Math.abs((x + w) - (ox + ow)) < threshold) { result.x = ox + ow - w; showAlignGuide("v", ox + ow); }
            });
            return result;
        }

        document.addEventListener("mousemove", function (e) {
            if (!resizing && !selectedBox) return;
            var layerRect = placementBoxes.getBoundingClientRect();
            var mouseMmX = pxToMm(e.clientX - layerRect.left);
            var mouseMmY = pxToMm(e.clientY - layerRect.top);
            if (resizing) {
                var p = placements[resizing.index];
                var minMm = 5;
                var dxMm = mouseMmX - resizing.startMmX;
                var dyMm = mouseMmY - resizing.startMmY;
                var w = resizing.startW, h = resizing.startH, x = resizing.startLeft, y = resizing.startTop;
                switch (resizing.corner) {
                    case "se": w = Math.max(minMm, resizing.startW + dxMm); h = Math.max(minMm, resizing.startH + dyMm); break;
                    case "sw": w = Math.max(minMm, resizing.startW - dxMm); h = Math.max(minMm, resizing.startH + dyMm); x = resizing.startLeft + dxMm; break;
                    case "ne": w = Math.max(minMm, resizing.startW + dxMm); h = Math.max(minMm, resizing.startH - dyMm); y = resizing.startTop + dyMm; break;
                    case "nw": w = Math.max(minMm, resizing.startW - dxMm); h = Math.max(minMm, resizing.startH - dyMm); x = resizing.startLeft + dxMm; y = resizing.startTop + dyMm; break;
                }
                p.width = snapToGrid(Math.round(w * 100) / 100);
                p.height = snapToGrid(Math.round(h * 100) / 100);
                p.x = snapToGrid(Math.round(x * 100) / 100);
                p.y = snapToGrid(Math.round(y * 100) / 100);
                renderPlacementBoxes();
                return;
            }
            var p2 = placements[selectedBox.index];
            var dx = mouseMmX - selectedBox.startMmX;
            var dy = mouseMmY - selectedBox.startMmY;
            var aligned = checkAlignment(selectedBox.index, snapToGrid(Math.round((selectedBox.startLeft + dx) * 100) / 100), snapToGrid(Math.round((selectedBox.startTop + dy) * 100) / 100), p2.width || 40, p2.height || 20);
            p2.x = aligned.x;
            p2.y = aligned.y;
            renderPlacementBoxes();
        });

        document.addEventListener("mouseup", function () {
            if (selectedBox || resizing) isDirty = true;
            hideAlignGuides();
            selectedBox = null;
            resizing = null;
        });

        document.addEventListener("keydown", function (e) {
            if ((e.key === "Delete" || e.key === "Backspace") && selectedPlacementIndex >= 0) {
                if (document.activeElement.tagName === "INPUT" || document.activeElement.tagName === "TEXTAREA") return;
                e.preventDefault();
                placements.splice(selectedPlacementIndex, 1);
                selectedPlacementIndex = -1;
                isDirty = true;
                renderPlacementBoxes();
                updateBadgeCount();
            }
            if (e.key === "Escape" && selectedPlacementIndex >= 0) {
                selectedPlacementIndex = -1;
                renderPlacementBoxes();
            }
        });

        function validateBeforeSign() {
            var hasSignature = placements.some(function (p) { return p.field_type === "signature"; });
            var namaLengkap = document.getElementById("nama_lengkap").value.trim();
            if (!namaLengkap) {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "warning", title: "Nama Wajib Diisi", text: 'Silakan atur detail tanda tangan terlebih dahulu (klik tombol "Atur detail").', confirmButtonColor: "#dc3545" });
                else alert("Silakan atur detail tanda tangan terlebih dahulu.");
                return false;
            }
            if (!hasSignature) {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "warning", title: "Tanda Tangan Wajib", text: 'Drag dan letakkan minimal 1 kotak "Tanda tangan" ke dokumen PDF sebelum menandatangani.', confirmButtonColor: "#dc3545" });
                else alert("Letakkan minimal 1 tanda tangan di dokumen.");
                return false;
            }
            return true;
        }

        function submitSignature(finalizeDocument) {
            var btn = document.getElementById("btn-save");
            btn.disabled = true;
            document.getElementById("btn-tandatangani").disabled = true;
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    title: "Menyimpan...",
                    html: '<div class="d-flex flex-column align-items-center"><div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;"></div><p class="mb-0">Sedang memproses tanda tangan dan membuat PDF...</p></div>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                });
            }
            var payload = {
                _token: document.getElementById("csrf-token").value,
                nama_lengkap: document.getElementById("nama_lengkap").value,
                inisial: document.getElementById("inisial").value,
                font_style: document.getElementById("font_style").value || "1",
                color: document.getElementById("color").value,
                signature_type: document.getElementById("signature_type").value || "text",
                signature_image_url: document.getElementById("signature_image_url").value || "",
                cropped_signature_image: document.getElementById("cropped_signature_image").value || "",
                finalize: !!finalizeDocument,
                placements: placements.map(function (p) {
                    return { field_type: p.field_type, page: p.page, x: p.x, y: p.y, width: p.width || 40, height: p.height || 8, value: p.value, options: {} };
                }),
            };
            fetch(document.getElementById("save-url").value, {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": payload._token, "Accept": "application/json" },
                body: JSON.stringify(payload),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (!data.success) {
                    if (typeof Swal !== "undefined") Swal.fire({ icon: "error", title: "Gagal", text: data.message || "Terjadi kesalahan saat menyimpan." });
                    else alert(data.message || "Gagal menyimpan.");
                    btn.disabled = false;
                    document.getElementById("btn-tandatangani").disabled = false;
                    return;
                }
                isDirty = false;
                if (finalizeDocument && typeof Swal !== "undefined") {
                    Swal.fire({
                        icon: "success",
                        title: "Dokumen Berhasil Ditandatangani!",
                        html: "<p class=\"mb-0\">Dokumen telah sah dan PDF bertanda tangan tersimpan.</p><p class=\"small text-muted mb-0 mt-2\">QR verifikasi keabsahan otomatis tertempel di <strong>halaman terakhir</strong> PDF (pojok bawah). Di halaman detail juga ada QR untuk scan cepat.</p>",
                        confirmButtonText: "Lihat Dokumen",
                        confirmButtonColor: "#198754",
                        allowOutsideClick: false,
                    }).then(function () { window.location.href = document.getElementById("redirect-url").value; });
                    return;
                }
                if (finalizeDocument) {
                    alert("Dokumen berhasil ditandatangani!");
                    window.location.href = document.getElementById("redirect-url").value;
                    return;
                }
                if (typeof Swal !== "undefined") Swal.fire({ icon: "success", title: "Draft Tersimpan", text: data.message || "Posisi tanda tangan berhasil disimpan.", timer: 1500, showConfirmButton: false });
                btn.disabled = false;
                document.getElementById("btn-tandatangani").disabled = false;
            }).catch(function () {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "error", title: "Gagal", text: "Terjadi kesalahan koneksi. Silakan coba lagi." });
                else alert("Gagal menyimpan.");
                btn.disabled = false;
                document.getElementById("btn-tandatangani").disabled = false;
            });
        }

        document.getElementById("btn-tandatangani").addEventListener("click", function () {
            if (!validateBeforeSign()) return;
            var namaLengkap = document.getElementById("nama_lengkap").value;
            var signatureCount = placements.filter(function (p) { return p.field_type === "signature"; }).length;
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "question",
                    title: "Konfirmasi Tanda Tangan",
                    html: '<p>Dokumen akan ditandatangani atas nama:</p><p class="fw-bold fs-5 text-primary">' + namaLengkap + '</p><p class="small text-muted mt-2">Jumlah tanda tangan: ' + signatureCount + " posisi<br>Setelah ditandatangani, dokumen akan menjadi <strong>SAH</strong> dan file asli akan diganti dengan versi bertanda tangan.</p>",
                    showCancelButton: true,
                    confirmButtonText: '<i class="fe fe-check me-1"></i> Ya, Tanda Tangani',
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    reverseButtons: true,
                }).then(function (result) { if (result.isConfirmed) submitSignature(true); });
            } else if (confirm('Tanda tangani dokumen atas nama "' + namaLengkap + '"? Dokumen akan menjadi SAH.')) {
                submitSignature(true);
            }
        });

        document.getElementById("btn-save").addEventListener("click", function () { submitSignature(false); });

        document.getElementById("btn-delete-draft").addEventListener("click", function () {
            if (typeof Swal !== "undefined") {
                Swal.fire({ title: "Hapus Dokumen Draft?", text: "Dokumen draft ini akan dihapus secara permanen dan tidak dapat dikembalikan.", icon: "warning", showCancelButton: true, confirmButtonColor: "#dc3545", cancelButtonColor: "#6c757d", confirmButtonText: "Ya, Hapus", cancelButtonText: "Batal" })
                    .then(function (result) {
                        if (!result.isConfirmed) return;
                        var payload = new URLSearchParams();
                        payload.append("_token", document.getElementById("csrf-token").value);
                        payload.append("_method", "DELETE");
                        fetch(document.getElementById("delete-url").value, {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8", "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                            body: payload.toString(),
                        }).then(function () { window.location.href = document.getElementById("index-url").value; })
                            .catch(function () { Swal.fire({ icon: "error", title: "Gagal", text: "Tidak bisa menghapus draft. Coba lagi." }); });
                    });
                return;
            }
            if (confirm("Yakin hapus dokumen draft ini?")) {
                var form = document.createElement("form");
                form.method = "POST";
                form.action = document.getElementById("delete-url").value;
                var token = document.createElement("input");
                token.type = "hidden"; token.name = "_token"; token.value = document.getElementById("csrf-token").value;
                var method = document.createElement("input");
                method.type = "hidden"; method.name = "_method"; method.value = "DELETE";
                form.appendChild(token); form.appendChild(method); document.body.appendChild(form); form.submit();
            }
        });

        document.getElementById("btn-reset").addEventListener("click", function () {
            if (placements.length === 0) {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "info", title: "Tidak Ada Posisi", text: "Belum ada kotak isian yang diletakkan di dokumen." });
                else alert("Belum ada posisi yang perlu direset.");
                return;
            }
            if (typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "warning",
                    title: "Reset Semua Posisi?",
                    text: "Semua kotak isian (" + placements.length + " item) akan dihapus dari dokumen. Tindakan ini tidak dapat dibatalkan.",
                    showCancelButton: true,
                    confirmButtonText: "Ya, Reset Semua",
                    cancelButtonText: "Batal",
                    confirmButtonColor: "#dc3545",
                    cancelButtonColor: "#6c757d",
                    reverseButtons: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;
                    placements = [];
                    isDirty = true;
                    renderPlacementBoxes();
                    updateBadgeCount();
                    Swal.fire({ icon: "success", title: "Berhasil", text: "Semua posisi telah direset.", timer: 1500, showConfirmButton: false });
                });
                return;
            }
            if (confirm("Reset semua posisi (" + placements.length + " item)? Tindakan ini tidak dapat dibatalkan.")) {
                placements = [];
                isDirty = true;
                renderPlacementBoxes();
                updateBadgeCount();
            }
        });

        var masterSelect = document.getElementById("master-ttd-select");
        if (masterSelect) {
            masterSelect.addEventListener("change", function () {
                var opt = this.options[this.selectedIndex];
                if (!opt || !opt.value) return;
                var nama = opt.getAttribute("data-nama") || "";
                var inisial = opt.getAttribute("data-inisial") || "";
                var font = opt.getAttribute("data-font") || "1";
                var color = opt.getAttribute("data-color") || "#000000";
                document.getElementById("nama_lengkap").value = nama;
                document.getElementById("inisial").value = inisial;
                document.getElementById("font_style").value = font;
                document.getElementById("color").value = color;
                var fontClass = "signature-font-" + font;
                document.getElementById("preview-signature").textContent = nama || "Tanda tangan";
                document.getElementById("preview-signature").className = "signature-cursive flex-grow-1 " + fontClass;
                document.getElementById("preview-signature").style.color = color;
                document.getElementById("preview-inisial").textContent = inisial || "Inisial";
                document.getElementById("preview-inisial").className = "signature-cursive flex-grow-1 " + fontClass;
                document.getElementById("preview-inisial").style.color = color;
                renderPlacementBoxes();
            });
        }

        document.getElementById("btn-save-master-ttd").addEventListener("click", function () {
            var btn = this;
            var payload = {
                _token: document.getElementById("csrf-token").value,
                nama_lengkap: document.getElementById("nama_lengkap").value,
                inisial: document.getElementById("inisial").value,
                font_style: document.getElementById("font_style").value || "1",
                color: document.getElementById("color").value || "#000000",
                is_default: true,
            };
            if (!payload.nama_lengkap.trim()) {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "warning", title: "Nama wajib", text: "Isi nama lengkap terlebih dahulu (Atur detail)." });
                else alert("Isi nama lengkap terlebih dahulu.");
                return;
            }
            btn.disabled = true;
            fetch(cfg.masterStoreUrl, {
                method: "POST",
                headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": payload._token, "Accept": "application/json", "X-Requested-With": "XMLHttpRequest" },
                body: JSON.stringify(payload),
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (!(data.success && data.master)) return;
                var m = data.master;
                var fontLabels = { "1": "Dancing Script", "2": "Pacifico", "3": "Great Vibes", "4": "Sacramento" };
                var label = fontLabels[m.font_style] || "Dancing Script";
                var opt = document.createElement("option");
                opt.value = m.id;
                opt.setAttribute("data-nama", m.nama_lengkap);
                opt.setAttribute("data-inisial", m.inisial || "");
                opt.setAttribute("data-font", m.font_style || "1");
                opt.setAttribute("data-color", m.color || "#000000");
                opt.textContent = m.nama_lengkap + " (" + label + ")";
                masterSelect.appendChild(opt);
                masterSelect.value = m.id;
                masterSelect.dispatchEvent(new Event("change"));
                if (typeof Swal !== "undefined") Swal.fire({ icon: "success", title: "Tersimpan", text: data.message || "Master tanda tangan disimpan." });
                else alert("Master tanda tangan disimpan.");
            }).catch(function () {
                if (typeof Swal !== "undefined") Swal.fire({ icon: "error", title: "Gagal", text: "Gagal menyimpan master." });
                else alert("Gagal menyimpan.");
            }).finally(function () { btn.disabled = false; });
        });

        (function applyInitialPreviewStyle() {
            var fontClass = "signature-font-" + (document.getElementById("font_style").value || "1");
            var colorVal = document.getElementById("color").value || "#000000";
            var prevSig = document.getElementById("preview-signature");
            var prevIni = document.getElementById("preview-inisial");
            if (prevSig) { prevSig.className = "signature-cursive flex-grow-1 " + fontClass; prevSig.style.color = colorVal; }
            if (prevIni) { prevIni.className = "signature-cursive flex-grow-1 " + fontClass; prevIni.style.color = colorVal; }
        })();

        updateBadgeCount();
    });

    window.openSignatureCropModal = function () {
        var imgSrc = document.getElementById("signature-image-preview").src;
        if (!imgSrc || imgSrc === "") {
            alert("Silakan upload gambar terlebih dahulu");
            return;
        }
        document.getElementById("signature-crop-image").src = imgSrc;
        var modal = new bootstrap.Modal(document.getElementById("cropSignatureModal"));
        modal.show();
        setTimeout(function () {
            var image = document.getElementById("signature-crop-image");
            if (window.signatureCropper) window.signatureCropper.destroy();
            window.signatureCropper = new Cropper(image, {
                aspectRatio: NaN,
                viewMode: 1,
                autoCropArea: 0.8,
                responsive: true,
                background: false,
            });
        }, 200);
    };

    window.applySignatureCrop = function () {
        if (!window.signatureCropper) return;
        var canvas = window.signatureCropper.getCroppedCanvas({
            maxWidth: 1024,
            maxHeight: 1024,
            fillColor: "transparent",
        });
        var croppedDataUrl = canvas.toDataURL("image/png");
        document.getElementById("signature-image-preview").src = croppedDataUrl;
        document.getElementById("cropped_signature_image").value = croppedDataUrl;
        window.__suratEdaranUploadedImageUrl = croppedDataUrl;
        document.getElementById("modal_file_ttd").value = "";
        bootstrap.Modal.getInstance(document.getElementById("cropSignatureModal")).hide();
    };
})();
