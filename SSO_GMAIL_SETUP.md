# Tutorial SSO Gmail untuk Mahakarya HRIS

Dokumen ini mengikuti implementasi yang ada di project ini:

- Backend auth: `backend/Auth.gs`
- Login page: `views/Login.html`
- Session handling dashboard: `js/auth.html`
- Router: `Kode.gs`

Implementasi saat ini memakai:

- `Google Identity Services` di frontend
- `google.accounts.oauth2.initTokenClient()` untuk mengambil access token Google
- Verifikasi profil Google di Apps Script melalui endpoint `https://www.googleapis.com/oauth2/v3/userinfo`
- Session aplikasi internal menggunakan `CacheService`
- Sheet `Users` sebagai sumber role dan status akses

## 1. Cara kerja login di project ini

Flow login project ini bukan OAuth redirect flow tradisional.

Yang terjadi:

1. User membuka `?page=login`
2. Tombol **Login with Google** memanggil Google Identity Services
3. Browser menerima `access_token`
4. Frontend mengirim token itu ke `signInWithGoogle(accessToken)`
5. Apps Script memanggil Google UserInfo API
6. Email user dicek ke sheet `Users`
7. Jika user aktif:
   - sistem membuat `sessionToken` internal
   - user diarahkan ke `?page=dashboard`
8. Jika user belum terdaftar:
   - sistem otomatis membuat row baru di sheet `Users`
   - role default: `Viewer`
   - status default: `Inactive`

Karena flow ini berbasis popup/token client, konfigurasi terpenting di Google Cloud adalah:

- `Authorized JavaScript origins`

Bukan:

- redirect URI ke `.../exec?page=dashboard`

Redirect URI hanya dipakai kalau kamu membangun flow redirect/callback. Project ini tidak memakai callback OAuth server-side.

## 2. Google Cloud Console setup yang benar

### A. Pilih project Cloud

Pakai project Cloud yang terhubung ke Apps Script web app ini.

Kalau sebelumnya sudah bikin project terpisah, pastikan `Client ID` yang dipakai di login page memang berasal dari project yang sama dan masih aktif.

### B. OAuth consent screen

Di `Google Cloud Console > APIs & Services > OAuth consent screen`:

1. Isi `App name`
2. Isi `User support email`
3. Isi `Developer contact information`
4. Pilih audience:
   - `Internal` jika hanya akun Google Workspace perusahaan
   - `External` jika ingin bisa diuji akun Gmail umum
5. Jika masih mode testing:
   - tambahkan akun yang akan dipakai login ke daftar `Test users`

Catatan:

- Jika pakai `External` tapi user belum masuk `Test users`, login bisa gagal di consent screen.
- Jika pakai `Internal`, akun di luar domain Workspace tidak akan bisa login.

### C. API yang perlu di-enable

Untuk flow login project ini, yang wajib praktis hanya komponen OAuth client dan scope user profile/email.

Yang relevan:

- OAuth consent screen
- OAuth Client ID

Tambahan yang saat ini dipakai project:

- `Admin SDK API` hanya bila fitur pembacaan directory Google Workspace memang digunakan

Yang tidak wajib khusus untuk login popup ini:

- menambahkan redirect URI ke `exec?page=dashboard`
- membuat OAuth callback page
- menambahkan library OAuth2 Apps Script

### D. Buat OAuth Client ID

Masuk ke `Google Cloud Console > APIs & Services > Credentials`:

1. Klik `Create Credentials`
2. Pilih `OAuth client ID`
3. Application type: `Web application`
4. Isi nama client, misalnya `Mahakarya HRIS Web Login`

### E. Isi Authorized JavaScript origins

Ini bagian paling penting.

Tambahkan origin web app yang benar. Untuk Apps Script, origin yang aman untuk diizinkan biasanya:

- `https://script.google.com`
- `https://script.googleusercontent.com`

Kalau kamu punya domain custom reverse proxy sendiri, tambahkan domain itu juga.

Catatan penting:

- Jangan isi origin dengan path lengkap seperti `https://script.google.com/macros/s/.../exec?page=dashboard`
- Field origin hanya menerima skema + host

Contoh benar:

- `https://script.google.com`
- `https://script.googleusercontent.com`

Contoh salah:

- `https://script.google.com/macros/s/AKfycb.../exec?page=dashboard`

### F. Authorized redirect URIs

Untuk implementasi project ini, field ini tidak dipakai oleh flow login popup yang sekarang.

Jadi:

- boleh dikosongkan
- atau dibiarkan ada, tetapi tidak menjadi acuan utama

Kalau kamu sebelumnya mengisi:

- `https://script.google.com/macros/s/{SCRIPT_ID}/exec?page=dashboard`

itu bukan penyebab user otomatis masuk dashboard, karena code login saat ini tidak memakai redirect OAuth callback ke URL itu.

## 3. Setup di Apps Script

### A. Script Properties

Masuk ke Apps Script:

`Project Settings > Script properties`

Tambahkan property:

- Key: `GOOGLE_CLIENT_ID`
- Value: `YOUR_CLIENT_ID.apps.googleusercontent.com`

Property ini dibaca oleh:

- `getAuthConfig()` di `backend/Auth.gs`

### B. Manifest scopes

Pastikan `appsscript.json` memiliki scope berikut:

- `https://www.googleapis.com/auth/script.external_request`
- `https://www.googleapis.com/auth/userinfo.email`
- `https://www.googleapis.com/auth/userinfo.profile`

Di project ini scope tersebut sudah ada.

### C. Deployment Web App

Deploy sebagai Web App:

1. `Deploy > Manage deployments`
2. Buat atau update deployment type `Web app`
3. `Execute as`: `Me`
4. `Who has access`:
   - `Anyone` jika portal publik dan login diatur di level aplikasi
   - atau sesuaikan kebutuhan organisasi

Setelah update code auth, selalu lakukan:

- `Edit deployment` lalu `Deploy`

Kalau tidak, browser masih memakai deployment lama.

## 4. Setup sheet Users

Sheet yang dipakai:

- `Users`

Header yang dipakai sistem:

1. `Email`
2. `Full Name`
3. `Role`
4. `Status`
5. `Last Login`
6. `Created At`
7. `Updated At`
8. `Created By`

Role valid:

- `Super Admin`
- `HR Admin`
- `Recruiter`
- `Manager`
- `Viewer`

Status valid:

- `Active`
- `Inactive`

## 5. First run setup

Saat sheet `Users` masih kosong:

1. Buka `?page=login`
2. Klik `Daftarkan Saya sebagai Super Admin`
3. Pilih akun Google yang akan jadi admin pertama
4. Sistem akan:
   - membuat row user pertama
   - role = `Super Admin`
   - status = `Active`
   - membuat session login
   - mengarahkan user ke dashboard

## 6. Flow user biasa setelah first run

Untuk user baru:

1. Buka `?page=login`
2. Klik `Login with Google`
3. Jika email belum ada di sheet `Users`, sistem akan:
   - membuat row baru otomatis
   - role = `Viewer`
   - status = `Inactive`
   - `Created By = google-sso`
4. User belum bisa masuk dashboard sebelum diaktifkan
5. Super Admin masuk ke halaman user management lalu ubah:
   - `Status` menjadi `Active`
   - `Role` sesuai kebutuhan

Setelah itu user login ulang dan akan bisa masuk dashboard.

## 7. Penyebab masalah yang kamu alami

### Masalah 1: user klik login Gmail tetapi tidak masuk ke record GSheets

Checklist:

1. Pastikan popup Google benar-benar selesai dan callback `signInWithGoogle(accessToken)` terpanggil
2. Pastikan OAuth consent screen tidak memblokir user
3. Pastikan client ID valid dan sama dengan project Cloud yang aktif
4. Pastikan origin yang dipakai page login sudah ada di `Authorized JavaScript origins`
5. Pastikan deployment web app yang aktif sudah memakai code terbaru

Perbaikan yang sudah diterapkan di project:

- penulisan user SSO baru ke sheet sekarang di-`flush()`
- source `Created By` dibedakan menjadi `google-sso`
- jika row gagal terbaca ulang setelah append, backend akan melempar error eksplisit

### Masalah 2: setelah login sukses tetap berhenti di `?page=login`

Penyebab paling umum di Apps Script:

- session token tersimpan di storage halaman login, tetapi hilang saat pindah route/origin ke dashboard

Perbaikan yang sudah diterapkan:

1. Login page sekarang mengarahkan user ke dashboard sambil membawa `sessionToken` di query string
2. `js/auth.html` akan menangkap `sessionToken` itu di halaman tujuan
3. Token langsung disimpan ke storage normal
4. URL lalu dibersihkan lagi dengan `history.replaceState()`

Hasilnya:

- dashboard bisa membaca session dengan konsisten
- redirect tidak mental kembali ke `?page=login`

## 8. File project yang relevan

### `backend/Auth.gs`

Fungsi penting:

- `getAuthConfig()`
- `signInWithGoogle(accessToken)`
- `autoCreateFirstAdmin(accessToken)`
- `getCurrentUser(sessionToken)`
- `createPendingUserIfNeeded_(profile)`

### `views/Login.html`

Tugas file ini:

- render tombol login
- meminta access token dari Google
- kirim token ke backend
- simpan session token aplikasi
- redirect ke target setelah login sukses

### `js/auth.html`

Tugas file ini:

- membaca session token dari storage
- bootstrap token dari query param saat pertama redirect
- validasi user session ke backend
- guard halaman dashboard/user management

## 9. Langkah test yang disarankan

### Skenario A: first admin

1. Kosongkan sheet `Users`
2. Deploy ulang web app
3. Buka `?page=login`
4. Klik `Daftarkan Saya sebagai Super Admin`
5. Pastikan:
   - row admin pertama masuk ke sheet
   - role `Super Admin`
   - status `Active`
   - halaman pindah ke `?page=dashboard`

### Skenario B: user baru

1. Login dengan akun Google lain
2. Klik `Login with Google`
3. Pastikan:
   - row baru masuk ke sheet `Users`
   - role `Viewer`
   - status `Inactive`
   - muncul pesan akun menunggu aktivasi

### Skenario C: aktivasi user

1. Login sebagai `Super Admin`
2. Buka `?page=user-management`
3. Aktifkan user baru
4. Login ulang memakai akun user tersebut
5. Pastikan user masuk ke dashboard

## 10. Checklist troubleshooting cepat

Kalau login masih gagal, cek ini berurutan:

1. `GOOGLE_CLIENT_ID` sudah diisi benar
2. OAuth client type = `Web application`
3. `Authorized JavaScript origins` sudah benar
4. User yang tes sudah masuk `Test users` jika consent screen masih testing
5. Deployment web app sudah di-update
6. Browser tidak memblok popup Google
7. Browser cache sudah dibersihkan atau test via incognito
8. Row `Users` untuk email tersebut statusnya `Active`

## 11. Catatan penting untuk project ini

- Flow login sekarang berbasis token popup, bukan callback redirect OAuth
- Redirect URI ke `exec?page=dashboard` bukan mekanisme utama login project ini
- Source of truth akses tetap sheet `Users`
- User yang belum aktif memang sengaja ditolak masuk dashboard
- Recruitment form publik tetap tidak berubah

## 12. Ringkasan implementasi sekarang

Status implementasi project setelah perbaikan:

- first-run Super Admin: aktif
- auto-create pending user saat login Google: aktif
- redirect login ke dashboard dengan bootstrap session token: aktif
- route protection dashboard/user management: aktif

