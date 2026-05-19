# NextIntern Admin Sistem - PHP Native

Versi ini sudah diperbaiki:

- CSS dibuat ulang agar layout tidak pecah.
- Tidak memakai JavaScript.
- Tidak ada file `assets/js`.
- Sidebar responsif tanpa tombol toggle JavaScript.
- Form tambah pengguna/perusahaan memakai `<details>` bawaan HTML, bukan modal JS.
- Fungsi PHP dibuat lebih aman untuk XAMPP/PHP versi lama karena tidak memakai `match`.

## Cara menjalankan

1. Extract folder ini ke `htdocs`.
2. Import `database.sql` ke phpMyAdmin.
3. Buka:

```text
http://localhost/nextintern_admin_sistem_php_fixed_css_no_js/login.php
```

## Login Admin Sistem

```text
Email    : sysadmin@test.com
Password : password
```

## Struktur halaman Admin Sistem

- `sysadmin/dashboard.php`
- `sysadmin/users.php`
- `sysadmin/companies.php`
- `sysadmin/internships.php`
- `sysadmin/applications.php`
- `sysadmin/active-interns.php`
- `sysadmin/certificates.php`
- `sysadmin/documents.php`
