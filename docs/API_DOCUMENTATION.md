\# PacakLaporAi - API Documentation



Backend REST API untuk Sistem Pelaporan Publik Palembang.



\## Base URL



\- \*\*Local Development\*\*: `http://127.0.0.1:8000/api`

\- \*\*Production\*\*: TBD



\## Authentication



API ini menggunakan \*\*Laravel Sanctum\*\* dengan Bearer Token.



Setiap request ke protected endpoint harus menyertakan header:



Token didapat dari endpoint `POST /api/login`.



\---



\## Postman Collection



Import file `PacakLaporAi.postman\_collection.json` ke Postman.



\*\*Variables yang perlu di-set di Collection:\*\*

\- `base\_url` = `http://127.0.0.1:8000`

\- `auth\_token` = (otomatis terisi setelah login via script)

\- `laporan\_id` = (otomatis terisi setelah create laporan via script)



\---



\## Endpoints Overview



\### Authentication (Public)



| Method | Endpoint | Description |

|--------|----------|-------------|

| POST | `/api/register` | Register user baru |

| POST | `/api/login` | Login, return Bearer token |



\### Authentication (Protected)



| Method | Endpoint | Description |

|--------|----------|-------------|

| GET | `/api/user` | Get current user info |

| POST | `/api/logout` | Logout, revoke token |



\### Laporan CRUD



| Method | Endpoint | Description | Role |

|--------|----------|-------------|------|

| GET | `/api/laporan` | List laporan (pagination 10) | All |

| POST | `/api/laporan` | Buat laporan baru | All |

| GET | `/api/laporan/{id}` | Detail laporan | All (warga: own only) |

| PUT | `/api/laporan/{id}` | Update laporan | Warga (own, status=pending only) |

| DELETE | `/api/laporan/{id}` | Soft delete laporan | Warga (own) / Admin |



\*\*Query filter untuk GET /api/laporan:\*\*

\- `?status=pending` (pending / verifikasi / diproses / selesai / ditolak)

\- `?kategori\_id=1`



\### Laporan Additional



| Method | Endpoint | Description | Role |

|--------|----------|-------------|------|

| POST | `/api/laporan/{id}/upload-foto` | Upload foto ke Cloudinary | Warga (own) |

| PUT | `/api/laporan/{id}/status-change` | Ubah status (state machine) | Petugas / Admin |

| POST | `/api/laporan/{id}/rating` | Beri rating laporan selesai | Warga (own, status=selesai, 1x only) |



\### State Machine — Transisi Status Valid



| Dari | Boleh ke |

|---|---|

| `pending` | `verifikasi`, `ditolak` |

| `verifikasi` | `diproses`, `ditolak` |

| `diproses` | `selesai`, `ditolak` |

| `selesai` | (final, tidak bisa diubah) |

| `ditolak` | (final, tidak bisa diubah) |



\### Profile



| Method | Endpoint | Description |

|--------|----------|-------------|

| GET | `/api/profile` | Get profile user yang login |

| PUT | `/api/profile` | Update profile (termasuk foto \& password) |



\*\*Update Profile — Field Opsional:\*\*

\- `name`, `email`, `nik`, `telepon`, `alamat`

\- `foto\_profil` (file image, multipart/form-data dengan `\_method=PUT`)

\- `current\_password` + `new\_password` + `new\_password\_confirmation` (untuk ganti password)



\### Notifikasi



| Method | Endpoint | Description |

|--------|----------|-------------|

| GET | `/api/notifikasi` | List notifikasi user (pagination 15) + `unread\_count` |

| PUT | `/api/notifikasi/{id}/read` | Mark as read (idempotent) |



\---



\## Response Format



\### Success



```json

{

&#x20;   "success": true,

&#x20;   "message": "Pesan deskriptif",

&#x20;   "data": { ... }

}

```



\### Validation Error (422)



```json

{

&#x20;   "message": "The field is required.",

&#x20;   "errors": {

&#x20;       "field\_name": \["Error message"]

&#x20;   }

}

```



\### Error Umum



```json

{

&#x20;   "success": false,

&#x20;   "message": "Pesan error"

}

```



\### HTTP Status Codes



\- `200 OK` — Sukses GET, PUT, DELETE

\- `201 Created` — Sukses POST yang membuat resource baru

\- `401 Unauthenticated` — Token tidak ada/invalid

\- `403 Forbidden` — User tidak punya akses (role/ownership)

\- `404 Not Found` — Resource tidak ditemukan

\- `422 Unprocessable Entity` — Validasi gagal atau aturan bisnis dilanggar



\---



\## Test Accounts



| Email | Password | Role |

|-------|----------|------|

| `admin@palembang.go.id` | `password123` | admin |

| `rinto@test.com` | `password123` | petugas |

| `warga1@test.com` | `password123` | warga |

| `warga2@test.com` \~ `warga8@test.com` | `password123` | warga |



\---



\## Quick Start Guide (untuk Frontend Dev)



1\. \*\*Import Postman Collection\*\* — file `PacakLaporAi.postman\_collection.json`

2\. \*\*Set collection variable\*\* `base\_url` = `http://127.0.0.1:8000`

3\. \*\*Login\*\* dulu — token otomatis tersimpan di `auth\_token`

4\. \*\*Coba endpoint lain\*\* — Authorization otomatis pakai Bearer token dari variable



\---



\## Contact



\- \*\*Backend Developer\*\*: Rachmat

\- \*\*GitHub\*\*: https://github.com/rachmatkurniawandev-web/PacakLaporAi

