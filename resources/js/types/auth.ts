export type Siswa = {
    nisn: string;
    nis: string;
    nama_lengkap: string;
    kelas: string;
    jurusan: string;
    tempat_lahir: string | null;
    tanggal_lahir: string | null;
    jenis_kelamin: string | null;
    alamat: string | null;
    foto_profil: string | null;
    is_aktif: boolean;
    created_at: string | null;
    updated_at: string | null;
};

export type Guru = {
    id: number;
    nip?: string;
    nama_lengkap: string;
    [key: string]: unknown;
};

export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    google_id: string | null;
    role: 'siswa' | 'guru' | 'admin' | string;
    nisn?: string | null;
    siswa?: Siswa | null;
    guru?: Guru | null;

    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
