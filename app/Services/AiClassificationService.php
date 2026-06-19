<?php

namespace App\Services;

/**
 * AiClassificationService
 *
 * Ini adalah simulasi AI berbasis aturan (rule-based).
 * Cara kerjanya: kita buat aturan logis berdasarkan
 * kata kunci di judul/deskripsi laporan, lalu hasilkan
 * confidence score dan metadata yang terlihat seperti AI.
 *
 * Analoginya: seperti dokter yang diagnosa penyakit
 * berdasarkan gejala — bukan pakai mesin, tapi pakai
 * pengetahuan dan aturan yang sudah dipelajari.
 */
class AiClassificationService
{
    /**
     * Kata kunci per kategori beserta bobotnya.
     * Bobot lebih tinggi = lebih yakin kalau kata ini muncul.
     */
    private array $keywordRules = [
        'banjir' => [
            'keywords' => ['banjir', 'genangan', 'banjir', 'air menggenang', 'luapan', 'drainase', 'gorong-gorong'],
            'severity_default' => 'tinggi',
        ],
        'infrastruktur' => [
            'keywords' => ['jalan rusak', 'jalan berlubang', 'aspal', 'trotoar', 'jembatan', 'retak', 'longsor', 'amblas'],
            'severity_default' => 'sedang',
        ],
        'kebersihan' => [
            'keywords' => ['sampah', 'kotor', 'limbah', 'bau', 'tempat sampah', 'tumpukan', 'pembuangan'],
            'severity_default' => 'rendah',
        ],
        'lampu jalan' => [
            'keywords' => ['lampu mati', 'lampu jalan', 'gelap', 'penerangan', 'listrik', 'tiang lampu'],
            'severity_default' => 'sedang',
        ],
        'keamanan' => [
            'keywords' => ['rawan', 'bahaya', 'berbahaya', 'kriminal', 'vandalisme', 'pencurian'],
            'severity_default' => 'tinggi',
        ],
        'pohon' => [
            'keywords' => ['pohon tumbang', 'pohon mati', 'dahan', 'ranting', 'menimpa', 'pohon roboh'],
            'severity_default' => 'darurat',
        ],
    ];

    /**
     * Kata kunci yang meningkatkan severity
     */
    private array $severityBooster = [
        'darurat' => ['darurat', 'bahaya', 'kritis', 'parah', 'korban', 'meninggal', 'luka', 'roboh', 'ambruk', 'terbakar'],
        'tinggi'  => ['rusak parah', 'berbahaya', 'tidak bisa', 'mengganggu', 'menghambat', 'urgent', 'segera'],
        'sedang'  => ['rusak', 'perlu perbaikan', 'mohon', 'sudah lama', 'berulang'],
        'rendah'  => ['kecil', 'ringan', 'sedikit', 'minta tolong'],
    ];

    /**
     * Method utama: analisis laporan dan hasilkan data AI
     *
     * @param string $judul      Judul laporan dari warga
     * @param string $deskripsi  Deskripsi laporan dari warga
     * @param bool   $adaFoto    Apakah laporan disertai foto
     * @param int|null $kategoriId ID kategori yang dipilih warga
     *
     * @return array [
     *   'ai_confidence' => float,    // 0-100
     *   'prioritas'     => string,   // rendah/sedang/tinggi/darurat
     *   'ai_metadata'   => array,    // detail analisis
     * ]
     */
    public function analisis(
        string $judul,
        string $deskripsi,
        bool $adaFoto = false,
        ?int $kategoriId = null
    ): array {
        // Gabungkan judul + deskripsi untuk analisis
        $teks = strtolower($judul . ' ' . $deskripsi);

        // Step 1: Hitung confidence score dasar
        $confidence = $this->hitungConfidence($teks, $adaFoto, $kategoriId);

        // Step 2: Tentukan severity/prioritas
        $prioritas = $this->tentukanPrioritas($teks);

        // Step 3: Tentukan kategori yang terdeteksi AI
        $kategoriTerdeteksi = $this->deteksiKategori($teks);

        // Step 4: Generate insight teks
        $insight = $this->generateInsight($judul, $prioritas, $kategoriTerdeteksi, $adaFoto);

        // Step 5: Buat metadata lengkap
        $metadata = [
            'model'               => 'PacakLaporAI-v1.0',
            'versi'               => '1.0.0',
            'dianalisis_pada'     => now()->toISOString(),
            'confidence_score'    => $confidence,
            'kategori_terdeteksi' => $kategoriTerdeteksi,
            'prioritas_suggested' => $prioritas,
            'ada_foto'            => $adaFoto,
            'kata_kunci_ditemukan'=> $this->temukan_kata_kunci($teks),
            'insight'             => $insight,
            'severity_level'      => $this->mapPrioritasKeLevel($prioritas),
        ];

        return [
            'ai_confidence' => $confidence,
            'prioritas'     => $prioritas,
            'ai_metadata'   => json_encode($metadata),
        ];
    }

    /**
     * Hitung confidence score (0-100)
     *
     * Faktor yang mempengaruhi:
     * 1. Kata kunci ditemukan di teks (+5 sampai +20 per kata kunci)
     * 2. Ada foto (+10) — AI lebih yakin kalau ada bukti visual
     * 3. Kategori dipilih (+5) — user sudah bantu klasifikasi
     * 4. Panjang deskripsi (+5 kalau detail)
     * 5. Base score 65 — minimal AI sudah cukup yakin
     */
    private function hitungConfidence(string $teks, bool $adaFoto, ?int $kategoriId): float
    {
        $score = 65.0; // base score

        // Bonus dari kata kunci yang ditemukan
        foreach ($this->keywordRules as $kategori => $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($teks, $keyword)) {
                    $score += 4.0;
                    break; // max 1x bonus per kategori
                }
            }
        }

        // Bonus ada foto
        if ($adaFoto) {
            $score += 10.0;
        }

        // Bonus kategori sudah dipilih
        if ($kategoriId) {
            $score += 5.0;
        }

        // Bonus deskripsi panjang (lebih dari 50 karakter)
        if (strlen($teks) > 50) {
            $score += 5.0;
        }

        // Tambah sedikit variasi random supaya tidak terlalu "robot"
        // Range: -2.5 sampai +2.5
        $score += (rand(0, 50) - 25) / 10;

        // Pastikan dalam range 60-98
        return round(min(98.0, max(60.0, $score)), 2);
    }

    /**
     * Tentukan prioritas/severity berdasarkan kata kunci
     */
    private function tentukanPrioritas(string $teks): string
    {
        // Cek dari severity tertinggi ke terendah
        foreach (['darurat', 'tinggi', 'sedang', 'rendah'] as $level) {
            foreach ($this->severityBooster[$level] as $keyword) {
                if (str_contains($teks, $keyword)) {
                    return $level;
                }
            }
        }

        // Default: sedang
        return 'sedang';
    }

    /**
     * Deteksi kategori laporan dari teks
     */
    private function deteksiKategori(string $teks): string
    {
        $skorTertinggi = 0;
        $kategoriTerpilih = 'Lainnya';

        foreach ($this->keywordRules as $kategori => $rule) {
            $skor = 0;
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($teks, $keyword)) {
                    $skor++;
                }
            }
            if ($skor > $skorTertinggi) {
                $skorTertinggi = $skor;
                $kategoriTerpilih = ucfirst($kategori);
            }
        }

        return $kategoriTerpilih;
    }

    /**
     * Temukan kata kunci yang ada di teks
     */
    private function temukan_kata_kunci(string $teks): array
    {
        $ditemukan = [];
        foreach ($this->keywordRules as $rule) {
            foreach ($rule['keywords'] as $keyword) {
                if (str_contains($teks, $keyword)) {
                    $ditemukan[] = $keyword;
                }
            }
        }
        return array_unique($ditemukan);
    }

    /**
     * Generate insight teks yang terlihat seperti AI
     */
    private function generateInsight(
        string $judul,
        string $prioritas,
        string $kategori,
        bool $adaFoto
    ): string {
        $insights = [
            'darurat' => [
                "Laporan ini terdeteksi memerlukan penanganan SEGERA. Indikasi kondisi darurat ditemukan.",
                "AI mendeteksi tingkat keparahan kritis. Disarankan penugasan petugas dalam 1 jam.",
                "Analisis menunjukkan potensi bahaya publik. Prioritaskan penanganan segera.",
            ],
            'tinggi' => [
                "Laporan berpotensi mengganggu aktivitas warga. Disarankan penanganan dalam 24 jam.",
                "AI mendeteksi kondisi yang memerlukan perhatian segera dari dinas terkait.",
                "Tingkat urgensi tinggi teridentifikasi. Rekomendasikan penugasan prioritas.",
            ],
            'sedang' => [
                "Laporan memerlukan penanganan dalam waktu normal. Tidak ada indikasi darurat.",
                "AI mengklasifikasikan laporan ini sebagai prioritas standar.",
                "Kondisi perlu diperbaiki namun tidak mengancam keselamatan langsung.",
            ],
            'rendah' => [
                "Laporan dapat ditangani dalam jadwal pemeliharaan rutin.",
                "AI mendeteksi kondisi yang perlu perhatian namun tidak mendesak.",
                "Tingkat urgensi rendah. Dapat dijadwalkan dalam program pemeliharaan.",
            ],
        ];

        $pilihan = $insights[$prioritas] ?? $insights['sedang'];
        $insight = $pilihan[array_rand($pilihan)];

        // Tambahkan info foto
        if ($adaFoto) {
            $insight .= " Bukti foto tersedia untuk verifikasi visual.";
        } else {
            $insight .= " Tidak ada foto bukti — verifikasi lapangan disarankan.";
        }

        return $insight;
    }

    /**
     * Map prioritas ke level angka untuk Flutter
     * (memudahkan Flutter tentukan warna badge)
     */
    private function mapPrioritasKeLevel(string $prioritas): int
    {
        return match ($prioritas) {
            'darurat' => 4,
            'tinggi'  => 3,
            'sedang'  => 2,
            'rendah'  => 1,
            default   => 2,
        };
    }

    /**
     * Generate AI Insight untuk dashboard
     * (Automatic Insight di mockup #2)
     *
     * Contoh output: "Wilayah Sako memiliki jumlah laporan banjir tertinggi bulan ini"
     */
    public static function generateDashboardInsight(array $hotspots, array $perKategori): string
    {
        // Insight berdasarkan hotspot terpanas
        if (! empty($hotspots) && isset($hotspots[0])) {
            $top = $hotspots[0];
            $area = $top['label'] ?? 'area tidak diketahui';
            $jumlah = $top['jumlah_laporan'] ?? 0;
            $kategori = strtolower($top['kategori_dominan'] ?? 'laporan');

            return "Area {$area} memiliki konsentrasi {$jumlah} laporan {$kategori} — perlu perhatian segera.";
        }

        // Fallback: insight berdasarkan kategori terbanyak
        if (! empty($perKategori)) {
            $top = $perKategori[0];
            $nama = $top['nama'] ?? 'tidak diketahui';
            $jumlah = $top['jumlah'] ?? 0;

            return "Kategori {$nama} mendominasi dengan {$jumlah} laporan bulan ini. Rekomendasikan peningkatan kapasitas penanganan.";
        }

        return "Sistem berjalan normal. Tidak ada anomali terdeteksi saat ini.";
    }
}