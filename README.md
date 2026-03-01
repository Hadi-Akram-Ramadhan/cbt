# 🎓 CBT Online — Computer Based Test

Website ujian berbasis web menggunakan **PHP Native** + **Tailwind CSS**. Mendukung dua role: **Admin/Guru** dan **Siswa**.

---

## ✨ Fitur

- **Manajemen Ujian** — Buat, edit, dan hapus ujian dengan pengaturan waktu (buka/tutup) dan durasi timer
- **3 Tipe Soal** — Pilihan Ganda (PG), Multiple Choice, dan Essay
- **Import Soal via Excel** — Upload file `.xlsx` untuk input soal massal
- **Auto-grading** — Soal PG dan Multiple Choice dinilai otomatis; essay dinilai manual oleh admin
- **Timer Countdown** — Ujian otomatis disubmit saat waktu habis
- **Auto-save** — Jawaban tersimpan otomatis setiap 30 detik
- **Anti-cheat** — Disable copy/paste, right-click, dan F12
- **Tab Switch Detection** — Perpindahan tab dicatat dan ditampilkan di laporan hasil ujian
- **Dashboard Statistik** — Admin melihat total siswa, ujian, dan rata-rata nilai

---

## 🛠 Tech Stack

| Layer    | Teknologi                        |
| -------- | -------------------------------- |
| Backend  | PHP 8.x Native                   |
| Frontend | Tailwind CSS (CDN), Font Awesome |
| Database | MySQL (PDO)                      |
| Excel    | PhpSpreadsheet (Composer)        |
| Auth     | Session-based + bcrypt           |

---

## 🚀 Setup

### 1. Clone / Copy ke web server

```
c:/laragon/www/brainstorm/cbt/
```

### 2. Import Database

Buka phpMyAdmin → import file:

```
database/schema.sql
```

### 3. Generate Password (Jalankan Sekali)

```
http://localhost/brainstorm/cbt/database/seed.php
```

> ⚠️ **Hapus `database/seed.php` setelah dijalankan!**

### 4. Install Dependencies

```bash
composer install --ignore-platform-reqs
```

### 5. Konfigurasi Database

Edit `config/db.php` jika password MySQL bukan kosong:

```php
define('DB_PASS', 'password_kamu');
```

### 6. Akses Website

```
http://localhost/brainstorm/cbt/
```

---

## 👤 Akun Default

| Username | Password   | Role       |
| -------- | ---------- | ---------- |
| `admin`  | `admin123` | Admin/Guru |
| `budi`   | `siswa123` | Siswa      |
| `siti`   | `siswa123` | Siswa      |

---

## 📁 Struktur Direktori

```
cbt/
├── admin/
│   ├── dashboard.php       # Dashboard statistik admin
│   ├── import.php          # Import soal via Excel
│   ├── students/           # CRUD siswa
│   ├── exams/              # CRUD ujian + assign peserta
│   ├── questions/          # CRUD soal per ujian
│   └── results/            # Lihat nilai + grading essay
├── user/
│   ├── dashboard.php       # Daftar ujian siswa
│   ├── exam.php            # Ruang ujian (timer + anti-cheat)
│   ├── submit_exam.php     # Handler submit & autosave
│   ├── record_tab_switch.php # AJAX tab switch detection
│   └── result.php          # Hasil ujian siswa
├── auth/
│   ├── login.php           # Halaman login
│   └── logout.php          # Handler logout
├── config/
│   ├── db.php              # Koneksi database + timezone
│   └── auth.php            # Auth helper (session, guard)
├── includes/
│   ├── header.php          # Layout header + sidebar
│   ├── footer.php          # Layout footer
│   └── functions.php       # Utility functions
├── assets/
│   └── download_template.php  # Download template Excel
├── database/
│   ├── schema.sql          # Skema database lengkap
│   └── seed.php            # Seeder password (hapus setelah dipakai!)
├── uploads/                # Folder upload sementara
├── vendor/                 # Composer dependencies
└── composer.json
```

---

## 📊 Template Excel Import

Download template: `http://localhost/brainstorm/cbt/assets/download_template.php`

| Kolom                 | Keterangan                                       |
| --------------------- | ------------------------------------------------ |
| `tipe_soal`           | `pg` / `multiple_choice` / `essay`               |
| `pertanyaan`          | Teks soal                                        |
| `opsi_a` s/d `opsi_e` | Pilihan jawaban                                  |
| `jawaban_benar`       | `A` untuk PG, `A,C` untuk MC, kosong untuk essay |
| `poin`                | Bobot nilai soal                                 |

---

## 🔒 Fitur Keamanan Ujian

| Fitur                | Detail                                          |
| -------------------- | ----------------------------------------------- |
| Disable copy/paste   | Event `copy`, `cut`, `paste` diblokir           |
| Disable right-click  | `contextmenu` dinonaktifkan                     |
| Disable shortcut     | Ctrl+C/V/X/A/U/S, F12 diblokir                  |
| Tab switch detection | `visibilitychange` API → counter di DB via AJAX |
| Popup peringatan     | Muncul saat siswa kembali ke tab ujian          |
| Auto-submit          | Form otomatis submit saat timer = 00:00         |
| Auto-save            | Jawaban tersimpan tiap 30 detik                 |

---

## 📝 Lisensi

MIT License — bebas digunakan dan dimodifikasi.
