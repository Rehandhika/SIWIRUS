<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'Super Admin';
    case ADMIN = 'Admin';
    case PENGURUS = 'Pengurus';
    case ANGGOTA = 'Anggota';
    
    // Organization Roles (Wirus Angkatan 66)
    case KETUA = 'Ketua';
    case WAKIL_KETUA = 'Wakil Ketua';
    case SEKRETARIS = 'Sekretaris';
    case BENDAHARA_UMUM = 'Bendahara Umum';
    case BENDAHARA_KEGIATAN = 'Bendahara Kegiatan';
    case BENDAHARA_TOKO = 'Bendahara Toko';
    case KOORDINATOR_TOKO = 'Koordinator Toko';
    case KOORDINATOR_PSDA = 'Koordinator PSDA';
    case KOORDINATOR_PRODUKSI = 'Koordinator Produksi';
    case KOORDINATOR_DESAIN = 'Koordinator Desain';
    case KOORDINATOR_HUMSAR = 'Koordinator Humsar';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Administrator',
            self::ADMIN => 'Administrator',
            self::PENGURUS => 'Pengurus',
            self::ANGGOTA => 'Anggota Koperasi',
            self::KETUA => 'Ketua Umum',
            self::WAKIL_KETUA => 'Wakil Ketua',
            self::SEKRETARIS => 'Sekretaris',
            self::BENDAHARA_UMUM => 'Bendahara Umum',
            self::BENDAHARA_KEGIATAN => 'Bendahara Kegiatan',
            self::BENDAHARA_TOKO => 'Bendahara Toko',
            self::KOORDINATOR_TOKO => 'Koordinator Toko',
            self::KOORDINATOR_PSDA => 'Koordinator PSDA',
            self::KOORDINATOR_PRODUKSI => 'Koordinator Produksi',
            self::KOORDINATOR_DESAIN => 'Koordinator Desain',
            self::KOORDINATOR_HUMSAR => 'Koordinator Humsar',
        };
    }
}
