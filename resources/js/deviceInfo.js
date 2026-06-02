/**
 * DeviceInfo.js
 * Helper untuk tracking device info di browser/mobile app
 * Menggunakan LocalStorage untuk menyimpan device_token (UUID)
 * dan Jenssegers/Agent (server-side) untuk parse User-Agent
 */

const DeviceInfo = {
    KEY_TOKEN: 'sikat_device_token',
    KEY_INFO: 'sikat_device_info',

    /**
     * Generate/get UUID dari LocalStorage
     */
    getToken() {
        let token = localStorage.getItem(this.KEY_TOKEN);
        if (!token) {
            token = this._generateUUID();
            localStorage.setItem(this.KEY_TOKEN, token);
        }
        return token;
    },

    /**
     * Generate UUID v4
     */
    _generateUUID() {
        if (crypto.randomUUID) {
            return crypto.randomUUID();
        }
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8).toString(16));
        });
    },

    /**
     * Set device info dari response server
     */
    setInfo(info) {
        const data = {
            device_model: info.device_model || 'Unknown',
            os_version: info.os_version || 'Unknown',
            browser: info.browser || 'Unknown',
            cached_at: new Date().toISOString()
        };
        localStorage.setItem(this.KEY_INFO, JSON.stringify(data));
        return data;
    },

    /**
     * Get device info dari LocalStorage
     */
    getInfo() {
        try {
            const info = localStorage.getItem(this.KEY_INFO);
            return info ? JSON.parse(info) : null;
        } catch {
            return null;
        }
    },

    /**
     * Get client IP (dari API external)
     */
    async getClientIP() {
        try {
            const response = await fetch('https://api.ipify.org?format=json');
            const data = await response.json();
            return data.ip;
        } catch {
            return null;
        }
    },

    /**
     * Initialize device - fetch info dari server dan cache
     */
    async init() {
        try {
            const response = await fetch('/api/absensi-agenda/device-info', {
                headers: {
                    'Authorization': 'Bearer ' + this._getToken(),
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    this.setInfo(result.data);
                }
            }
        } catch (error) {
            console.warn('Failed to fetch device info:', error);
        }
        return this.getInfo();
    },

    /**
     * Get semua data untuk kirim ke server
     */
    async getAll() {
        const token = this.getToken();
        const info = this.getInfo();
        let ip = null;

        try {
            ip = await this.getClientIP();
        } catch {
            ip = null;
        }

        return {
            device_token: token,
            ip_address: ip,
            device_model: info ? info.device_model : null,
            os_version: info ? info.os_version : null,
            browser: info ? info.browser : null,
        };
    },

    /**
     * Helper untuk get Bearer token (dari localStorage app)
     */
    _getToken() {
        return localStorage.getItem('sikat_token') || '';
    },

    /**
     * Clear all stored data
     */
    clear() {
        localStorage.removeItem(this.KEY_TOKEN);
        localStorage.removeItem(this.KEY_INFO);
    }
};

// Export untuk module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = DeviceInfo;
}