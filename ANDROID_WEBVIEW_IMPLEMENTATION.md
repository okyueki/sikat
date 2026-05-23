# Implementasi Android WebView dengan Deteksi Fake GPS

## 📱 Overview

Laravel bisa di-webview-kan di Android, dan kita bisa menggunakan **native Android API** untuk mendeteksi fake GPS yang tidak bisa dilakukan di browser web biasa.

---

## 🎯 Solusi yang Disediakan

### 1. **API Endpoint untuk Validasi GPS**
- Endpoint: `POST /api/gps/validate`
- Menerima lokasi dari Android native app
- Mendeteksi fake GPS melalui flag `is_mock_location`
- Validasi jarak dari titik presensi

### 2. **JavaScript Bridge untuk WebView**
- Interface JavaScript untuk komunikasi WebView ↔ Native Android
- Auto-detect apakah di WebView atau browser biasa
- Fallback ke browser GPS jika bukan WebView

---

## 📋 Implementasi Android Studio

### **Step 1: Setup WebView di Android**

```kotlin
// MainActivity.kt
import android.webkit.WebView
import android.webkit.WebViewClient
import android.webkit.JavascriptInterface
import android.webkit.WebChromeClient
import android.location.Location
import android.location.LocationManager
import androidx.appcompat.app.AppCompatActivity

class MainActivity : AppCompatActivity() {
    private lateinit var webView: WebView
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        webView = WebView(this)
        setContentView(webView)
        
        // Enable JavaScript
        val webSettings = webView.settings
        webSettings.javaScriptEnabled = true
        webSettings.domStorageEnabled = true
        webSettings.setGeolocationEnabled(true)
        
        // Add JavaScript Interface
        webView.addJavascriptInterface(AndroidGPSInterface(), "AndroidGPS")
        
        // Set WebViewClient
        webView.webViewClient = WebViewClient()
        webView.webChromeClient = object : WebChromeClient() {
            override fun onGeolocationPermissionsShowPrompt(
                origin: String?,
                callback: GeolocationPermissions.Callback?
            ) {
                callback?.invoke(origin, true, false)
            }
        }
        
        // Load Laravel URL
        webView.loadUrl("https://your-domain.com/absensi")
    }
}
```

### **Step 2: Buat JavaScript Interface untuk GPS**

```kotlin
// AndroidGPSInterface.kt
import android.content.Context
import android.location.Location
import android.location.LocationManager
import android.webkit.JavascriptInterface
import android.util.Log
import org.json.JSONObject

class AndroidGPSInterface(private val context: Context) {
    
    @JavascriptInterface
    fun getLocation(): String {
        return try {
            val locationManager = context.getSystemService(Context.LOCATION_SERVICE) as LocationManager
            
            // Cek apakah GPS enabled
            if (!locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                return JSONObject().apply {
                    put("error", "GPS tidak aktif")
                    put("success", false)
                }.toString()
            }
            
            // Request location update (synchronous - untuk demo)
            // NOTE: Di production, gunakan callback atau coroutine
            var location: Location? = null
            var isMockLocation = false
            
            try {
                location = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER)
                
                // Deteksi fake GPS (Android API)
                if (location != null) {
                    // Method 1: Cek isFromMockProvider (API 18+)
                    if (android.os.Build.VERSION.SDK_INT >= android.os.Build.VERSION_CODES.JELLY_BEAN_MR2) {
                        isMockLocation = location.isFromMockProvider
                    }
                    
                    // Method 2: Cek Settings.Secure.ALLOW_MOCK_LOCATION (deprecated tapi masih bisa dipakai)
                    if (!isMockLocation) {
                        val allowMock = android.provider.Settings.Secure.getInt(
                            context.contentResolver,
                            android.provider.Settings.Secure.ALLOW_MOCK_LOCATION,
                            0
                        )
                        if (allowMock == 1) {
                            // Ada kemungkinan mock location aktif
                            // Lakukan validasi tambahan
                            isMockLocation = checkMockLocation(location)
                        }
                    }
                }
                
            } catch (e: SecurityException) {
                Log.e("GPS", "Permission denied", e)
                return JSONObject().apply {
                    put("error", "Izin lokasi tidak diberikan")
                    put("success", false)
                }.toString()
            }
            
            if (location == null) {
                return JSONObject().apply {
                    put("error", "Lokasi tidak tersedia")
                    put("success", false)
                }.toString()
            }
            
            // Return location data
            JSONObject().apply {
                put("success", true)
                put("latitude", location.latitude)
                put("longitude", location.longitude)
                put("accuracy", location.accuracy)
                put("is_mock_location", isMockLocation)
                put("provider", location.provider)
            }.toString()
            
        } catch (e: Exception) {
            Log.e("GPS", "Error getting location", e)
            JSONObject().apply {
                put("error", e.message)
                put("success", false)
            }.toString()
        }
    }
    
    /**
     * Deteksi fake GPS dengan metode tambahan
     */
    private fun checkMockLocation(location: Location): Boolean {
        // Cek beberapa indikator fake GPS:
        // 1. Accuracy terlalu sempurna (0.0 atau sangat kecil)
        // 2. Speed tidak masuk akal
        // 3. Provider tidak sesuai
        
        if (location.accuracy == 0.0f) {
            return true // Fake GPS biasanya accuracy = 0
        }
        
        if (location.speed < 0 || location.speed > 100) {
            return true // Speed tidak masuk akal
        }
        
        // Cek apakah ada aplikasi mock location yang terinstall
        val pm = context.packageManager
        val mockLocationApps = listOf(
            "com.lexa.fakegps",
            "com.blogspot.newapphorizons.fakegps",
            "com.incorporateapps.fakegps.free",
            "com.eyefishinggames.fakegps"
        )
        
        for (packageName in mockLocationApps) {
            try {
                pm.getPackageInfo(packageName, 0)
                return true // Aplikasi fake GPS terdeteksi
            } catch (e: Exception) {
                // App tidak terinstall
            }
        }
        
        return false
    }
}
```

### **Step 3: Update JavaScript di Laravel untuk WebView**

Tambahkan kode ini di `form.blade.php`:

```javascript
// Deteksi apakah di WebView Android
function isAndroidWebView() {
    return typeof AndroidGPS !== 'undefined';
}

// Fungsi untuk mendapatkan lokasi dari Android native
function getLocationFromAndroid() {
    return new Promise((resolve, reject) => {
        if (!isAndroidWebView()) {
            reject('Not in Android WebView');
            return;
        }
        
        try {
            const locationData = JSON.parse(AndroidGPS.getLocation());
            
            if (locationData.success) {
                resolve({
                    lat: locationData.latitude,
                    lng: locationData.longitude,
                    accuracy: locationData.accuracy,
                    isMockLocation: locationData.is_mock_location || false
                });
            } else {
                reject(locationData.error || 'Failed to get location');
            }
        } catch (e) {
            reject('Error parsing location data: ' + e.message);
        }
    });
}

// Update fungsi getCurrentLocation untuk support WebView
function getCurrentLocation() {
    const gpsStatus = document.getElementById('gpsStatus');
    const gpsStatusText = document.getElementById('gpsStatusText');
    
    if (!gpsStatus || !gpsStatusText) return;
    
    // Jika di Android WebView, gunakan native API
    if (isAndroidWebView()) {
        gpsStatus.className = 'alert alert-warning mb-3';
        gpsStatus.style.display = 'block';
        gpsStatusText.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Mendapatkan lokasi dari Android...';
        
        getLocationFromAndroid()
            .then(location => {
                currentLocation = { lat: location.lat, lng: location.lng };
                
                // Validasi fake GPS
                if (location.isMockLocation) {
                    gpsStatus.className = 'alert alert-danger mb-3';
                    gpsStatusText.innerHTML = '<i class="fa fa-exclamation-triangle me-2"></i>Fake GPS terdeteksi! Presensi tidak dapat dilakukan.';
                    isLocationValid = false;
                    updateSubmitButton();
                    return;
                }
                
                // Validasi jarak
                const distance = calculateDistance(location.lat, location.lng, TARGET_LAT, TARGET_LNG);
                
                if (distance <= ALLOWED_RADIUS) {
                    gpsStatus.className = 'alert alert-success mb-3';
                    gpsStatusText.innerHTML = `<i class="fa fa-check-circle me-2"></i>Lokasi valid. Jarak: ${Math.round(distance)} meter.`;
                    isLocationValid = true;
                } else {
                    gpsStatus.className = 'alert alert-danger mb-3';
                    gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>Di luar radius. Jarak: ${Math.round(distance)} meter (maks ${ALLOWED_RADIUS} meter).`;
                    isLocationValid = false;
                }
                
                updateSubmitButton();
            })
            .catch(error => {
                gpsStatus.className = 'alert alert-danger mb-3';
                gpsStatusText.innerHTML = `<i class="fa fa-exclamation-triangle me-2"></i>${error}`;
                isLocationValid = false;
                updateSubmitButton();
            });
        
        return;
    }
    
    // Fallback ke browser GPS (untuk browser biasa)
    // ... kode GPS browser yang sudah ada ...
}
```

---

## 🔒 Deteksi Fake GPS di Android

### **Metode Deteksi:**

1. **`Location.isFromMockProvider()`** (API 18+)
   - Method resmi Android untuk deteksi mock location
   - Paling akurat

2. **Cek `Settings.Secure.ALLOW_MOCK_LOCATION`**
   - Cek apakah mock location diizinkan di Developer Options
   - Deprecated tapi masih bisa dipakai

3. **Validasi Data Lokasi**
   - Accuracy = 0.0 → kemungkinan fake
   - Speed tidak masuk akal → kemungkinan fake
   - Provider tidak sesuai → kemungkinan fake

4. **Cek Aplikasi Fake GPS**
   - Scan aplikasi fake GPS yang umum terinstall
   - Jika ada, flag sebagai suspicious

---

## 📝 AndroidManifest.xml

```xml
<manifest>
    <!-- Permission untuk GPS -->
    <uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
    <uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
    
    <!-- Permission untuk deteksi mock location (optional) -->
    <uses-permission android:name="android.permission.ACCESS_MOCK_LOCATION" 
                     tools:ignore="ProtectedPermissions" />
    
    <application>
        <activity android:name=".MainActivity">
            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>
        </activity>
    </application>
</manifest>
```

---

## 🚀 Testing

### **Test di Android Studio:**

1. **Test dengan GPS Asli:**
   - Enable GPS di device/emulator
   - Buka aplikasi
   - Pastikan lokasi valid

2. **Test dengan Fake GPS:**
   - Install aplikasi fake GPS (misal: Fake GPS Location)
   - Set lokasi fake
   - Buka aplikasi
   - Pastikan terdeteksi sebagai fake GPS

3. **Test di Luar Radius:**
   - Set lokasi di luar radius 30 meter
   - Pastikan presensi ditolak

---

## ⚠️ Catatan Penting

1. **Rooted Device:**
   - Di device yang sudah di-root, fake GPS bisa lebih sulit dideteksi
   - Pertimbangkan validasi tambahan di server-side

2. **Privacy:**
   - Lokasi GPS tidak disimpan di database (sesuai permintaan)
   - Hanya validasi real-time

3. **Fallback:**
   - Jika bukan WebView, tetap gunakan browser GPS
   - Tapi tidak bisa deteksi fake GPS di browser

4. **Server-side Validation (Opsional):**
   - Bisa tambahkan validasi di server juga
   - Bandingkan dengan IP geolocation
   - Cek pattern lokasi yang mencurigakan

---

## 📞 Support

Jika ada pertanyaan atau masalah implementasi, silakan hubungi developer Android atau cek dokumentasi Android Location API.
