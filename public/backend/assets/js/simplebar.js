(function () {
    "use strict";
    
    // Inisialisasi SimpleBar untuk sidebar dengan validasi ketat
    function initSidebarSimpleBar() {
        if (typeof SimpleBar === 'undefined') {
            return; // Skip jika library tidak tersedia
        }
        
        var myElement = document.getElementById('sidebar-scroll');
        
        // Validasi ketat sebelum initialize
        if (!myElement || !(myElement instanceof HTMLElement) || myElement.nodeType !== 1) {
            return; // Skip jika elemen tidak valid
        }
        
        if (!document.body || !document.body.contains(myElement)) {
            return; // Skip jika tidak ada di DOM
        }
        
        // Cek apakah sudah diinisialisasi
        if (myElement.SimpleBar || myElement.hasAttribute('data-simplebar')) {
            return; // Skip jika sudah diinisialisasi
        }
        
        try {
            new SimpleBar(myElement, { autoHide: true });
        } catch (e) {
            // Silent fail untuk sidebar
            console.warn('SimpleBar sidebar initialization skipped:', e.message);
        }
    }
    
    // Initialize setelah DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initSidebarSimpleBar, 300);
        });
    } else {
        setTimeout(initSidebarSimpleBar, 300);
    }
})();