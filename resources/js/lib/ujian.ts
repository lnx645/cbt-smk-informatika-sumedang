export type KategoriUjian = 'kuis' | 'uts' | 'uas' | 'usbk';
export type TipeSoal = 'pg' | 'multi' | 'benar_salah' | 'isian' | 'esai';
export type Kesulitan = 'mudah' | 'sedang' | 'sulit';

export const KESULITAN_INFO: Record<Kesulitan, { label: string; color: string }> = {
    mudah: { label: 'Mudah', color: 'success' },
    sedang: { label: 'Sedang', color: 'warning' },
    sulit: { label: 'Sulit', color: 'danger' },
};

export const KATEGORI_INFO: Record<
    KategoriUjian,
    { label: string; color: string }
> = {
    kuis: { label: 'Kuis', color: 'info' },
    uts: { label: 'UTS', color: 'primary' },
    uas: { label: 'UAS', color: 'warning' },
    usbk: { label: 'USBK', color: 'danger' },
};

export const DEFAULT_KATEGORI: Record<
    KategoriUjian,
    {
        acak_soal: boolean;
        acak_opsi: boolean;
        wajib_fullscreen: boolean;
        maks_pelanggaran: number;
        bobot: number;
    }
> = {
    kuis: {
        acak_soal: false,
        acak_opsi: false,
        wajib_fullscreen: false,
        maks_pelanggaran: 0,
        bobot: 1,
    },
    uts: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 5,
        bobot: 2,
    },
    uas: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 5,
        bobot: 3,
    },
    usbk: {
        acak_soal: true,
        acak_opsi: true,
        wajib_fullscreen: true,
        maks_pelanggaran: 3,
        bobot: 3,
    },
};

export const TIPE_SOAL_INFO: Record<
    TipeSoal,
    { label: string; icon: string; objektif: boolean; butuhOpsi: boolean }
> = {
    pg: {
        label: 'Pilihan Ganda',
        icon: 'bi-ui-radios',
        objektif: true,
        butuhOpsi: true,
    },
    multi: {
        label: 'Multi-jawaban',
        icon: 'bi-ui-checks',
        objektif: true,
        butuhOpsi: true,
    },
    benar_salah: {
        label: 'Benar / Salah',
        icon: 'bi-toggles',
        objektif: true,
        butuhOpsi: true,
    },
    isian: {
        label: 'Isian Singkat',
        icon: 'bi-input-cursor-text',
        objektif: true,
        butuhOpsi: false,
    },
    esai: {
        label: 'Esai',
        icon: 'bi-textarea-resize',
        objektif: false,
        butuhOpsi: false,
    },
};

export const STATUS_PENGERJAAN_INFO: Record<
    string,
    { label: string; color: string }
> = {    belum: { label: 'Belum', color: 'secondary' },
    berlangsung: { label: 'Berlangsung', color: 'info' },
    selesai: { label: 'Selesai', color: 'success' },
    auto_submit: { label: 'Auto-submit', color: 'warning' },
    diskualifikasi: { label: 'Diskualifikasi', color: 'danger' },
};

/**
 * Format detik menjadi HH:MM:SS untuk timer countdown.
 */
export function formatDurasi(detik: number): string {
    const d = Math.max(0, Math.floor(detik));
    const h = Math.floor(d / 3600)
        .toString()
        .padStart(2, '0');
    const m = Math.floor((d % 3600) / 60)
        .toString()
        .padStart(2, '0');
    const s = (d % 60).toString().padStart(2, '0');
    return `${h}:${m}:${s}`;
}
