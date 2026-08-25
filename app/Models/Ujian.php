<?php

namespace App\Models;

use Database\Factories\UjianFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[Table('ujians')]
class Ujian extends Model
{
    /** @use HasFactory<UjianFactory> */
    use HasFactory;

    protected $fillable = [
        'guru_id',
        'guru_kelas_id',
        'penilaian_id',
        'dibuat_oleh_admin',
        'kategori',
        'judul',
        'deskripsi',
        'tanggal_mulai',
        'tanggal_selesai',
        'durasi_menit',
        'maks_attempt',
        'acak_soal',
        'acak_opsi',
        'tampilkan_hasil',
        'wajib_fullscreen',
        'maks_pelanggaran',
        'token',
        'token_released_at',
        'token_pengelola',
        'nilai_maks',
        'bobot',
        'status',
    ];

    protected $casts = [
        'dibuat_oleh_admin' => 'boolean',
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'durasi_menit' => 'integer',
        'maks_attempt' => 'integer',
        'acak_soal' => 'boolean',
        'acak_opsi' => 'boolean',
        'tampilkan_hasil' => 'boolean',
        'wajib_fullscreen' => 'boolean',
        'maks_pelanggaran' => 'integer',
        'token_released_at' => 'datetime',
        'nilai_maks' => 'integer',
        'bobot' => 'integer',
    ];

    /**
     * Kategori yang tergolong ujian besar (butuh window jadwal & proctoring ketat).
     *
     * @var array<int, string>
     */
    public const KATEGORI_BESAR = ['uts', 'uas', 'usbk'];

    public function guru(): BelongsTo
    {
        return $this->belongsTo(Guru::class);
    }

    public function guruKelas(): BelongsTo
    {
        return $this->belongsTo(GuruKelas::class);
    }

    public function penilaian(): BelongsTo
    {
        return $this->belongsTo(Penilaian::class);
    }

    public function soals(): HasMany
    {
        return $this->hasMany(Soal::class)->orderBy('urutan')->orderBy('id');
    }

    public function pengerjaans(): HasMany
    {
        return $this->hasMany(UjianPengerjaan::class);
    }

    public function scopeBesar(Builder $query): Builder
    {
        return $query->whereIn('kategori', self::KATEGORI_BESAR);
    }

    public function kategoriBesar(): bool
    {
        return in_array($this->kategori, self::KATEGORI_BESAR, true);
    }

    public function sudahTerbit(): bool
    {
        return $this->status === 'terbit';
    }

    public function sudahMulai(): bool
    {
        return $this->tanggal_mulai === null || $this->tanggal_mulai->lte(Carbon::now());
    }

    public function sudahSelesai(): bool
    {
        return $this->tanggal_selesai !== null && $this->tanggal_selesai->lt(Carbon::now());
    }

    public function sedangBerlangsung(): bool
    {
        return $this->sudahTerbit() && $this->sudahMulai() && ! $this->sudahSelesai();
    }

    /**
     * Total poin semua soal (dipakai untuk normalisasi nilai ke nilai_maks).
     */
    public function totalPoin(): int
    {
        return (int) $this->soals()->sum('poin');
    }

    /**
     * Token valid: cocok, sudah dirilis, dan ujian sedang berlangsung.
     */
    public function tokenValid(?string $token): bool
    {
        if ($this->token === null) {
            return true;
        }

        if ($this->token_released_at === null) {
            return false;
        }

        return $token !== null && strcasecmp($token, $this->token) === 0;
    }
}
