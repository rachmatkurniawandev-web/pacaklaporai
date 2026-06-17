<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\StatusHistory;
use App\Models\Rating;
use App\Models\Notifikasi;
use App\Models\User;
use App\Models\Dinas;
use App\Models\Kategori;
use Carbon\Carbon;

class LaporanDemoSeeder extends Seeder
{
    // Mapping kategori → dinas yang sesuai (untuk auto-routing)
    private array $kategoriDinasMap = [
        'Kebersihan'      => 'DKP',
        'Infrastruktur'   => 'DPU',
        'Lampu Jalan'     => 'PJU',
        'Banjir'          => 'BPBD',
        'Pohon Tumbang'   => 'BPBD',
        'Lainnya'         => 'DKP',
    ];

    // Template laporan realistis Palembang
    private array $laporanTemplates = [
        [
            'kategori' => 'Kebersihan',
            'judul' => 'Sampah menumpuk di Pasar 16 Ilir',
            'deskripsi' => 'Tumpukan sampah sudah 3 hari belum diangkut. Bau menyengat dan mengganggu pengunjung pasar.',
            'lokasi' => 'Jl. Pasar 16 Ilir, Palembang',
            'lat' => -2.9890, 'lng' => 104.7565,
        ],
        [
            'kategori' => 'Kebersihan',
            'judul' => 'Selokan tersumbat sampah plastik',
            'deskripsi' => 'Selokan tersumbat sampah plastik dan dedaunan, menyebabkan genangan air saat hujan.',
            'lokasi' => 'Jl. Veteran, Palembang',
            'lat' => -2.9756, 'lng' => 104.7458,
        ],
        [
            'kategori' => 'Infrastruktur',
            'judul' => 'Jalan berlubang di Jl. Sudirman',
            'deskripsi' => 'Lubang besar di tengah jalan, sudah ada beberapa pengendara motor yang terjatuh.',
            'lokasi' => 'Jl. Jenderal Sudirman, depan RS Charitas',
            'lat' => -2.9897, 'lng' => 104.7421,
        ],
        [
            'kategori' => 'Infrastruktur',
            'judul' => 'Trotoar rusak parah',
            'deskripsi' => 'Trotoar pecah dan berlubang, membahayakan pejalan kaki terutama lansia.',
            'lokasi' => 'Jl. Kapten A. Rivai, Palembang',
            'lat' => -2.9788, 'lng' => 104.7491,
        ],
        [
            'kategori' => 'Lampu Jalan',
            'judul' => 'Lampu jalan mati total',
            'deskripsi' => 'Sepanjang 200m lampu jalan mati, daerah jadi gelap dan rawan kejahatan.',
            'lokasi' => 'Jl. Demang Lebar Daun, Palembang',
            'lat' => -2.9612, 'lng' => 104.7390,
        ],
        [
            'kategori' => 'Lampu Jalan',
            'judul' => 'Lampu PJU berkedip-kedip',
            'deskripsi' => 'Lampu jalan menyala redup dan berkedip, kemungkinan kabel bermasalah.',
            'lokasi' => 'Jl. Kol. H. Burlian, Palembang',
            'lat' => -2.9542, 'lng' => 104.7311,
        ],
        [
            'kategori' => 'Banjir',
            'judul' => 'Banjir setelah hujan sore',
            'deskripsi' => 'Air sudah masuk ke rumah warga, ketinggian sekitar 40cm. Drainase tidak berfungsi.',
            'lokasi' => 'Jl. Rajawali, Sukarami, Palembang',
            'lat' => -2.9234, 'lng' => 104.7156,
        ],
        [
            'kategori' => 'Banjir',
            'judul' => 'Genangan air permanen di jalan',
            'deskripsi' => 'Genangan air tidak pernah surut bahkan saat tidak hujan. Mengganggu lalu lintas.',
            'lokasi' => 'Jl. Sumatra, Bukit Besar, Palembang',
            'lat' => -2.9889, 'lng' => 104.7521,
        ],
        [
            'kategori' => 'Pohon Tumbang',
            'judul' => 'Pohon besar tumbang tutupi jalan',
            'deskripsi' => 'Pohon angsana tumbang menutupi seluruh badan jalan akibat hujan deras semalam.',
            'lokasi' => 'Jl. Mayor Ruslan, Palembang',
            'lat' => -2.9678, 'lng' => 104.7445,
        ],
        [
            'kategori' => 'Pohon Tumbang',
            'judul' => 'Dahan pohon patah hampir menimpa rumah',
            'deskripsi' => 'Dahan pohon besar patah dan menggantung, bahaya jatuh sewaktu-waktu.',
            'lokasi' => 'Jl. Letjen Harun Sohar, Palembang',
            'lat' => -2.9421, 'lng' => 104.7298,
        ],
        [
            'kategori' => 'Lainnya',
            'judul' => 'Tiang listrik miring berbahaya',
            'deskripsi' => 'Tiang listrik di pinggir jalan miring sekitar 30 derajat, takut roboh.',
            'lokasi' => 'Jl. Angkatan 45, Palembang',
            'lat' => -2.9756, 'lng' => 104.7389,
        ],
        [
            'kategori' => 'Lainnya',
            'judul' => 'Kabel listrik menjuntai rendah',
            'deskripsi' => 'Kabel menjuntai sekitar 2 meter dari tanah, bisa tersangkut kendaraan tinggi.',
            'lokasi' => 'Jl. R. Sukamto, Palembang',
            'lat' => -2.9712, 'lng' => 104.7634,
        ],
    ];

    // URL foto dummy (sesuaikan dengan kategori)
    private array $fotoUrls = [
        'Kebersihan' => [
            'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?w=800',
            'https://images.unsplash.com/photo-1611284446314-60a58ac0deb9?w=800',
        ],
        'Infrastruktur' => [
            'https://images.unsplash.com/photo-1592859600972-1b0834d83747?w=800',
            'https://images.unsplash.com/photo-1494522855154-9297ac14b55f?w=800',
        ],
        'Lampu Jalan' => [
            'https://images.unsplash.com/photo-1519608487953-e999c86e7455?w=800',
        ],
        'Banjir' => [
            'https://images.unsplash.com/photo-1547683905-f686c993aae5?w=800',
        ],
        'Pohon Tumbang' => [
            'https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?w=800',
        ],
        'Lainnya' => [
            'https://images.unsplash.com/photo-1497436072909-60f360e1d4b1?w=800',
        ],
    ];

    public function run(): void
    {
        // Ambil user warga
        $wargas = User::where('role', 'warga')->get();
        if ($wargas->isEmpty()) {
            $this->command->error('Tidak ada user dengan role "warga". Jalankan UserSeeder dulu.');
            return;
        }

        // Ambil petugas dan admin untuk status change
        $petugas = User::where('role', 'petugas')->first();
        $admin = User::where('role', 'admin')->first();

        // Distribusi status untuk variasi demo (total 30 laporan)
        $statusDistribution = [
            'pending'    => 6,
            'verifikasi' => 5,
            'diproses'   => 7,
            'selesai'    => 10, // banyak yang selesai untuk demo rating
            'ditolak'    => 2,
        ];

        $totalLaporan = 0;

        foreach ($statusDistribution as $targetStatus => $count) {
            for ($i = 0; $i < $count; $i++) {
                // Pilih template acak
                $template = $this->laporanTemplates[array_rand($this->laporanTemplates)];

                // Cari kategori
                $kategori = Kategori::where('nama', $template['kategori'])->first();
                if (!$kategori) continue;

                // Cari dinas berdasarkan mapping
                $kodeDinas = $this->kategoriDinasMap[$template['kategori']] ?? 'DKP';
                $dinas = Dinas::where('kode', $kodeDinas)->first();

                // Tanggal lampau acak (1-30 hari yang lalu)
                $daysAgo = rand(1, 30);
                $createdAt = Carbon::now()->subDays($daysAgo);

                // Buat laporan
                $warga = $wargas->random();
                $laporan = Laporan::create([
                    'nomor_tiket' => Laporan::generateNomorTiket(),
                    'user_id' => $warga->id,
                    'kategori_id' => $kategori->id,
                    'dinas_id' => $dinas?->id,
                    'judul' => $template['judul'],
                    'deskripsi' => $template['deskripsi'],
                    'lokasi' => $template['lokasi'],
                    'latitude' => $template['lat'],
                    'longitude' => $template['lng'],
                    'tanggal_kejadian' => $createdAt->format('Y-m-d'),
                    'prioritas' => ['rendah', 'sedang', 'tinggi'][rand(0, 2)],
                    'status' => $targetStatus,
                    'tanggal_selesai' => $targetStatus === 'selesai' ? $createdAt->copy()->addDays(rand(1, 5)) : null,
                    'alasan_ditolak' => $targetStatus === 'ditolak' ? 'Foto tidak jelas atau laporan duplikat' : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Tambah foto (1-2 foto per laporan)
                $fotos = $this->fotoUrls[$template['kategori']] ?? $this->fotoUrls['Lainnya'];
                $jumlahFoto = min(count($fotos), rand(1, 2));
                for ($f = 0; $f < $jumlahFoto; $f++) {
                    LaporanFoto::create([
                        'laporan_id' => $laporan->id,
                        'url' => $fotos[$f],
                        'public_id' => null, // foto dummy, tidak ada di Cloudinary
                        'urutan' => $f + 1,
                        'is_primary' => $f === 0,
                        'keterangan' => $f === 0 ? 'Foto utama' : 'Foto pendukung',
                    ]);
                }

                // Buat status history sesuai alur
                $this->createStatusHistory($laporan, $targetStatus, $warga, $petugas, $admin, $createdAt);

                // Untuk laporan "selesai", tambah rating (50% kemungkinan)
                if ($targetStatus === 'selesai' && rand(0, 1) === 1) {
                    Rating::create([
                        'laporan_id' => $laporan->id,
                        'user_id' => $warga->id,
                        'nilai_rating' => rand(3, 5),
                        'komentar' => [
                            'Terima kasih, masalah cepat ditangani',
                            'Petugas responsif dan ramah',
                            'Penanganan kurang cepat tapi hasilnya bagus',
                            'Sangat puas dengan pelayanan',
                        ][rand(0, 3)],
                        'kecepatan_respon' => rand(3, 5),
                        'kualitas_penanganan' => rand(3, 5),
                        'sikap_petugas' => rand(4, 5),
                        'is_anonymous' => rand(0, 1) === 1,
                    ]);
                }

                // Buat notifikasi
                $this->createNotifikasi($laporan, $targetStatus, $createdAt);

                $totalLaporan++;
            }
        }

        $this->command->info("Berhasil membuat {$totalLaporan} laporan demo dengan status bervariasi.");
    }

    /**
     * Buat status history sesuai target status
     * pending → verifikasi → diproses → selesai/ditolak
     */
    private function createStatusHistory($laporan, $targetStatus, $warga, $petugas, $admin, $startDate): void
    {
        $changedBy = $petugas?->id ?? $admin?->id ?? $warga->id;

        // Step 1: NULL → pending (saat dibuat)
        StatusHistory::create([
            'laporan_id' => $laporan->id,
            'user_id' => $warga->id,
            'status_dari' => null,
            'status_ke' => 'pending',
            'keterangan' => 'Laporan baru dibuat oleh warga',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder',
            'created_at' => $startDate,
        ]);

        // Step 2-4: lanjut sesuai target status
        $alur = ['pending'];
        if (in_array($targetStatus, ['verifikasi', 'diproses', 'selesai'])) {
            $alur[] = 'verifikasi';
        }
        if (in_array($targetStatus, ['diproses', 'selesai'])) {
            $alur[] = 'diproses';
        }
        if ($targetStatus === 'selesai' || $targetStatus === 'ditolak') {
            $alur[] = $targetStatus;
        }

        // Buat history untuk setiap transisi
        for ($i = 1; $i < count($alur); $i++) {
            StatusHistory::create([
                'laporan_id' => $laporan->id,
                'user_id' => $changedBy,
                'status_dari' => $alur[$i - 1],
                'status_ke' => $alur[$i],
                'keterangan' => $this->keteranganTransisi($alur[$i]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'created_at' => $startDate->copy()->addHours($i * rand(2, 24)),
            ]);
        }
    }

    private function keteranganTransisi($status): string
    {
        return match ($status) {
            'verifikasi' => 'Laporan sedang diverifikasi petugas',
            'diproses'   => 'Laporan diteruskan ke dinas terkait untuk ditindaklanjuti',
            'selesai'    => 'Masalah telah ditangani dengan baik',
            'ditolak'    => 'Foto tidak jelas atau laporan duplikat',
            default      => 'Status diubah',
        };
    }

    private function createNotifikasi($laporan, $targetStatus, $createdAt): void
    {
        // Notifikasi sesuai status terakhir
        $notifMap = [
            'verifikasi' => [
                'tipe' => 'status_update',
                'judul' => 'Laporan Anda Sedang Diverifikasi',
                'pesan' => "Laporan #{$laporan->nomor_tiket} sedang dicek oleh petugas",
                'icon' => 'shield-check',
            ],
            'diproses' => [
                'tipe' => 'status_update',
                'judul' => 'Laporan Anda Sedang Diproses',
                'pesan' => "Laporan #{$laporan->nomor_tiket} sudah diteruskan ke dinas terkait",
                'icon' => 'cog',
            ],
            'selesai' => [
                'tipe' => 'laporan_selesai',
                'judul' => 'Laporan Anda Telah Selesai',
                'pesan' => "Laporan #{$laporan->nomor_tiket} sudah ditangani. Berikan rating untuk membantu kami!",
                'icon' => 'check-circle',
            ],
            'ditolak' => [
                'tipe' => 'status_update',
                'judul' => 'Laporan Anda Ditolak',
                'pesan' => "Laporan #{$laporan->nomor_tiket} ditolak. Lihat detail untuk informasi lebih lanjut.",
                'icon' => 'x-circle',
            ],
        ];

        if (! isset($notifMap[$targetStatus])) return;

        $notif = $notifMap[$targetStatus];

        Notifikasi::create([
            'user_id' => $laporan->user_id,
            'laporan_id' => $laporan->id,
            'judul' => $notif['judul'],
            'pesan' => $notif['pesan'],
            'tipe' => $notif['tipe'],
            'icon' => $notif['icon'],
            'action_url' => "/laporan/{$laporan->id}",
            'is_read' => rand(0, 1) === 1, // random sudah dibaca/belum
            'is_pushed' => false,
            'created_at' => $createdAt->copy()->addHours(rand(1, 48)),
        ]);
    }
}