<?php

/**
 * Helper format tampilan (UI standar SRS 3.1): tanggal DD-MM-YYYY, Rupiah dengan titik ribuan,
 * dan label status berwarna. Dimuat lewat Config\Autoload::$helpers = [..., 'format'].
 */

if (! function_exists('format_rupiah')) {
    /** "Rp 1.950.000". Desimal tampil (koma) hanya bila bukan .00. */
    function format_rupiah(mixed $value): string
    {
        $n = (float) $value;

        return 'Rp ' . number_format($n, fmod($n, 1) === 0.0 ? 0 : 2, ',', '.');
    }
}

if (! function_exists('format_date')) {
    /** Tanggal (kolom DATE) menjadi DD-MM-YYYY. */
    function format_date(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        try {
            return (new DateTimeImmutable($value))->format('d-m-Y');
        } catch (Throwable) {
            return '-';
        }
    }
}

if (! function_exists('format_datetime')) {
    /** Waktu (kolom TIMESTAMPTZ) menjadi DD-MM-YYYY HH:MM pada zona waktu aplikasi. */
    function format_datetime(?string $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        try {
            $tz = new DateTimeZone(config('App')->appTimezone ?? 'UTC');

            return (new DateTimeImmutable($value))->setTimezone($tz)->format('d-m-Y H:i');
        } catch (Throwable) {
            return '-';
        }
    }
}

if (! function_exists('status_badge')) {
    /** Label status berwarna (Draft, Approved, Open, Aktif, Dibatalkan). Warna dari Config\Documents. */
    function status_badge(?string $status): string
    {
        $status = (string) $status;
        $class  = config('Documents')->statusBadges[$status] ?? 'text-bg-light border';

        return '<span class="badge ' . $class . '">' . esc($status) . '</span>';
    }
}
