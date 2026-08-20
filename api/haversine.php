<?php
/**
 * SIMAS-GPS — Fungsi Haversine
 * Menghitung jarak antara dua koordinat latitude & longitude di permukaan bumi
 */

/**
 * Hitung jarak antara dua titik koordinat (dalam meter)
 * Menggunakan rumus Haversine.
 * 
 * @param float $lat1  Latitude titik pertama
 * @param float $lon1  Longitude titik pertama
 * @param float $lat2  Latitude titik kedua
 * @param float $lon2  Longitude titik kedua
 * @return float Jarak dalam meter
 */
function hitungJarak(float $lat1, float $lon1, float $lat2, float $lon2): float {
    // Radius bumi dalam meter (sekitar 6.371 km)
    $earthRadius = 6371000;

    // Konversi derajat ke radian
    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo   = deg2rad($lat2);
    $lonTo   = deg2rad($lon2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos($latFrom) * cos($latTo) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
         
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}
