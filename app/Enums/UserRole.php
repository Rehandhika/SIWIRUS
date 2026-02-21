<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'Super Admin';
    case KETUA = 'Ketua';
    case WAKIL_KETUA = 'Wakil Ketua';
    case SEKRETARIS = 'Sekretaris';
    case BENDAHARA = 'Bendahara'; // General term for all bendahara types if needed, or specific
    case BPH = 'BPH'; // Badan Pengurus Harian
    case KOORDINATOR = 'Koordinator'; // General term
    case ANGGOTA = 'Anggota';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::KETUA => 'Ketua Umum',
            self::WAKIL_KETUA => 'Wakil Ketua',
            self::SEKRETARIS => 'Sekretaris',
            self::BENDAHARA => 'Bendahara',
            self::BPH => 'Badan Pengurus Harian',
            self::KOORDINATOR => 'Koordinator Divisi',
            self::ANGGOTA => 'Anggota Koperasi',
        };
    }
}
