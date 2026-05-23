(function () {
    "use strict";

    function formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        var k = 1024;
        var sizes = ["Bytes", "KB", "MB"];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + " " + sizes[i];
    }

    function hideWarning(warningId) {
        var warning = document.getElementById(warningId);
        if (!warning) return;
        warning.classList.add("d-none");
        warning.innerHTML = "";
    }

    window.initSuratEdaranCreateUpload = function initSuratEdaranCreateUpload() {
        var fileInput = document.getElementById("file_pdf");
        var uploadArea = document.getElementById("file-upload-area");
        var uploadTrigger = uploadArea ? uploadArea.querySelector(".upload-trigger") : null;
        var fileInfo = document.getElementById("file-info");
        var fileName = document.getElementById("file-name");
        var fileSize = document.getElementById("file-size");

        if (!fileInput || !uploadArea || !uploadTrigger || !fileInfo || !fileName || !fileSize) {
            return;
        }

        fileInput.addEventListener("change", function () {
            if (!(fileInput.files && fileInput.files[0])) return;
            var file = fileInput.files[0];
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            uploadTrigger.style.display = "none";
            fileInfo.style.display = "flex";
        });

        uploadArea.addEventListener("dragover", function (e) {
            e.preventDefault();
            uploadArea.classList.add("dragover");
        });

        uploadArea.addEventListener("dragleave", function () {
            uploadArea.classList.remove("dragover");
        });

        uploadArea.addEventListener("drop", function (e) {
            e.preventDefault();
            uploadArea.classList.remove("dragover");
            var files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === "application/pdf") {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event("change"));
            } else if (files.length > 0) {
                alert("Hanya file PDF yang diizinkan.");
            }
        });

        window.clearFile = function () {
            fileInput.value = "";
            uploadTrigger.style.display = "flex";
            fileInfo.style.display = "none";
            fileName.textContent = "";
            fileSize.textContent = "";
            hideWarning("pdf-compat-warning");
        };
    };

    window.initSuratEdaranEditUpload = function initSuratEdaranEditUpload(config) {
        var cfg = config || {};
        if (cfg.isSigned) return;

        var fileInput = document.getElementById("file_pdf");
        var uploadArea = document.getElementById("file-upload-area");
        var uploadTrigger = document.getElementById("upload-trigger");
        var existingFileInfo = document.getElementById("existing-file-info");
        var newFileInfo = document.getElementById("new-file-info");
        var fileName = document.getElementById("file-name");
        var fileSize = document.getElementById("file-size");

        if (!fileInput || !uploadArea || !newFileInfo || !fileName || !fileSize) {
            return;
        }

        fileInput.addEventListener("change", function () {
            if (!(fileInput.files && fileInput.files[0])) return;
            var file = fileInput.files[0];
            fileName.textContent = file.name;
            fileSize.textContent = formatFileSize(file.size);
            if (uploadTrigger) uploadTrigger.style.display = "none";
            if (existingFileInfo) existingFileInfo.style.display = "none";
            newFileInfo.style.display = "flex";
        });

        uploadArea.addEventListener("dragover", function (e) {
            e.preventDefault();
            uploadArea.classList.add("dragover");
        });

        uploadArea.addEventListener("dragleave", function () {
            uploadArea.classList.remove("dragover");
        });

        uploadArea.addEventListener("drop", function (e) {
            e.preventDefault();
            uploadArea.classList.remove("dragover");
            var files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === "application/pdf") {
                fileInput.files = files;
                fileInput.dispatchEvent(new Event("change"));
            } else if (files.length > 0) {
                alert("Hanya file PDF yang diizinkan.");
            }
        });

        window.changeFile = function () {
            fileInput.click();
        };

        window.clearFile = function () {
            fileInput.value = "";
            newFileInfo.style.display = "none";
            if (cfg.hasExistingFile) {
                if (existingFileInfo) existingFileInfo.style.display = "flex";
            } else if (uploadTrigger) {
                uploadTrigger.style.display = "flex";
            }
            hideWarning("pdf-compat-warning");
        };
    };
})();
