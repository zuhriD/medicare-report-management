# Panduan & Rencana Integrasi Evolution WhatsApp API (Bot Absensi Terpusat)

Dokumen ini berisi panduan teknis, alur arsitektur, format pesan, dan rencana implementasi untuk integrasi notifikasi kehadiran Medicare HR Attendance System dengan **Evolution WhatsApp API**.

---

## 1. Arsitektur & Cara Kerja

Sistem menggunakan **1 nomor WhatsApp Bot terpusat (Dedicated Bot)**:
- **Otomatis dari Server**: Saat staff melakukan absensi di web (*Check-In, Izin Keluar, Kembali, Check-Out, Lembur*), server secara otomatis memformat pesan laporan dan mengirimkannya ke WhatsApp Group.
- **Asynchronous Queue**: Pengiriman diproses melalui background job (*Laravel Queue Job*) sehingga respon tombol absen di browser staff tetap instan dalam milidetik tanpa menunggu respons jaringan dari WhatsApp.
- **Multi-Office Ready**: Setiap kantor/cabang dapat memiliki WhatsApp Group masing-masing, atau menggunakan grup utama secara default.

---

## 2. Variabel Konfigurasi (`.env`)

Tambahkan variabel berikut ke file `.env` Laravel:

```env
# Evolution WhatsApp API Configuration
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=your-evolution-global-api-key
EVOLUTION_INSTANCE_NAME=medicare-attendance
EVOLUTION_ATTENDANCE_GROUP_ID=1203630xxxxxxxxxx@g.us
EVOLUTION_NOTIFICATIONS_ENABLED=true
```

---

## 3. Langkah Setup di Evolution API

1. **Jalankan Evolution API**:
   - Pastikan Evolution API aktif dan catat `SERVER_URL` serta `AUTHENTICATION_API_KEY`.
2. **Buat Instance Bot Absensi**:
   - Panggil `POST {{EVOLUTION_URL}}/instance/create`:
     ```json
     {
       "instanceName": "medicare-attendance",
       "qrcode": true,
       "integration": "WHATSAPP-BAILEYS"
     }
     ```
3. **Scan QR Code**:
   - Akses `GET {{EVOLUTION_URL}}/instance/connect/medicare-attendance` di browser.
   - Scan QR code menggunakan aplikasi WhatsApp di ponsel yang dijadikan Bot Kantor (*Linked Devices / Perangkat Tertaut*).
4. **Masukkan Nomor Bot ke Grup WhatsApp Absensi**:
   - Masukkan nomor bot tersebut ke dalam WhatsApp Group yang dituju.
5. **Dapatkan ID Grup WhatsApp (`@g.us`)**:
   - Panggil `GET {{EVOLUTION_URL}}/group/fetchAllGroups/medicare-attendance?getParticipants=false`
   - Ambil nilai `id` grup absensi (contoh: `120363012345678901@g.us`) dan masukkan ke `.env`.

---

## 4. Contoh Format Pesan Notifikasi di WhatsApp Group

### 🟢 1. Check-In Reguler
```
━━━━━━━━━━━━━━━━━━━━━
📍 *NOTIFIKASI ABSENSI MASUK*
━━━━━━━━━━━━━━━━━━━━━
👤 *Nama:* John Doe (@johndoe)
🏢 *Kantor:* Kuala Lumpur HQ
🕒 *Waktu Masuk:* 08:05:12 (Asia/Kuala_Lumpur)
📌 *Lokasi GPS:* Dalam Radius Kantor (±10m)
💬 *Catatan:* Siap bertugas hari ini
━━━━━━━━━━━━━━━━━━━━━
_Medicare HR Attendance System_
```

### ⏸️ 2. Izin Keluar (Pause)
```
━━━━━━━━━━━━━━━━━━━━━
⏸️ *IZIN KELUAR KANTOR (JEDA)*
━━━━━━━━━━━━━━━━━━━━━
👤 *Nama:* John Doe
🏢 *Kantor:* Kuala Lumpur HQ
🕒 *Waktu Keluar:* 12:00:10
📝 *Alasan Izin:* Istirahat Makan Siang
💬 *Catatan:* Makan siang di luar kantor
━━━━━━━━━━━━━━━━━━━━━
_Status absensi dijeda otomatis hingga kembali ke kantor_
```

### ▶️ 3. Kembali ke Kantor (Resume)
```
━━━━━━━━━━━━━━━━━━━━━
▶️ *KEMBALI KE KANTOR (LANJUT KERJA)*
━━━━━━━━━━━━━━━━━━━━━
👤 *Nama:* John Doe
🏢 *Kantor:* Kuala Lumpur HQ
🕒 *Waktu Kembali:* 12:50:20
⏱️ *Durasi Jeda:* 50 menit
📌 *Status Lokasi:* Terverifikasi di Kantor (GPS OK)
━━━━━━━━━━━━━━━━━━━━━
_Absensi dilanjutkan kembali_
```

### 🔴 4. Check-Out Reguler
```
━━━━━━━━━━━━━━━━━━━━━
🏁 *NOTIFIKASI ABSENSI PULANG*
━━━━━━━━━━━━━━━━━━━━━
👤 *Nama:* John Doe
🏢 *Kantor:* Kuala Lumpur HQ
🕒 *Waktu Pulang:* 17:05:30
⏳ *Jam Masuk:* 08:05:12
⏸️ *Total Izin/Jeda:* 50 menit
⏱️ *Durasi Kerja Efektif:* 8 jam 10 menit
💰 *Tunjangan Harian:* ✅ Memenuhi Syarat
━━━━━━━━━━━━━━━━━━━━━
_Medicare HR Attendance System_
```

### 🔥 5. Lembur (Overtime Check-In & Check-Out)
```
━━━━━━━━━━━━━━━━━━━━━
🔥 *NOTIFIKASI SELESAI LEMBUR*
━━━━━━━━━━━━━━━━━━━━━
👤 *Nama:* John Doe
🏢 *Kantor:* Kuala Lumpur HQ
🕒 *Mulai Lembur:* 18:00:00
🏁 *Selesai Lembur:* 21:00:00
⏱️ *Durasi Lembur:* 3 jam
💰 *Uang Lembur:* ✅ Memenuhi Syarat (Rp 50,000.00)
━━━━━━━━━━━━━━━━━━━━━
_Medicare HR Attendance System_
```

---

## 5. Rencana File & Komponen di Laravel

1. **Database Migration**:
   - `database/migrations/2026_09_22_000002_add_whatsapp_fields_to_offices_table.php` (menambahkan `whatsapp_group_id` dan `whatsapp_notifications_enabled` per kantor).
2. **Config & Model**:
   - `config/services.php`: Section konfigurasi `evolution`.
   - `app/Models/Office.php`: Helper `getEffectiveWhatsAppGroupId()`.
   - `app/Filament/Resources/OfficeResource.php`: Input grup WA per kantor di panel admin Filament.
3. **Service Layer**:
   - `app/Services/EvolutionWhatsAppService.php`: Client HTTP untuk endpoint Evolution API (`/message/sendText` & `/message/sendMedia`).
   - `app/Services/AttendanceWhatsAppNotificationService.php`: Formatter pesan notifikasi untuk seluruh jenis event absensi.
4. **Queue Job**:
   - `app/Jobs/SendAttendanceWhatsAppNotificationJob.php`: Background worker untuk pengiriman notifikasi tanpa blocking.
5. **Staff Page Trigger**:
   - `app/Filament/Pages/MyAttendance.php`: Trigger dispatch queue job pada aksi check-in, pause, resume, check-out, dan lembur.
