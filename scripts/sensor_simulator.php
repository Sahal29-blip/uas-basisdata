<?php
// Script simulator sensor untuk generate data dummy secara berkala
include '../includes/db_config.php';  // Menghubungkan ke database

// Fungsi untuk generate data sensor yang realistis dengan range lebih luas
function generateSensorData() {
    $current_time = time();  // Mengambil waktu saat ini
    $hour = date('H', $current_time); // Mengambil jam saat ini (0-23)
    $day_of_year = date('z', $current_time); // Mengambil hari ke-berapa dalam setahun 0-365
    $month = date('n', $current_time); // Mengambil bulan saat ini 1-12
    
    // Simulasi suhu berdasarkan waktu dan musim (15-40°C)
    // Base temperature berdasarkan jam (pola harian)
    $daily_temp_curve = sin(($hour - 6) * pi() / 12); // Mengambil kurva suhu harian, puncak di jam 14:00
    $base_daily_temp = 27 + ($daily_temp_curve * 8); // Suhu dasar harian, berkisar antara 19-35°C 
    
    // Variasi musiman (simulasi)
    $seasonal_variation = sin(($month - 1) * pi() / 6) * 5; // Variasi suhu musiman, ±5°C
    
    // Random weather variation (cuaca)
    $weather_factor = (rand(-100, 100) / 100) * 6; // Variasi Acak suhu ±6°C
    
    // Occasional extreme temperatures
    $extreme_chance = rand(1, 100); // Peluang munculnya suhu ekstrim
    if ($extreme_chance <= 5) { // 5% chance suhu ekstrim
        $extreme_factor = rand(5, 12); // +5 to +12°C
    } elseif ($extreme_chance <= 10) { // 5% chance extreme cold  
        $extreme_factor = rand(-12, -5); // -12 to -5°C
    } else {
        $extreme_factor = 0;
    }
    // Menentukan nilai suhu ekstrim secara acak
    
    // Calculate final temperature
    $temperature = $base_daily_temp + $seasonal_variation + $weather_factor + $extreme_factor;
    
    // Nilai akhir dibatasi antara 10-45°C
    $temperature = max(10, min(45, $temperature));
    $temperature = round($temperature, 1);
    
    // Simulasi kelembaban berbanding terbalik dengan suhu
    $base_humidity = 85 - (($temperature - 20) * 1.8); // Inverse relationship
    
    // Add humidity variations
    $humidity_noise = (rand(-150, 150) / 10); // ±15% noise
    $humidity = $base_humidity + $humidity_noise;
    
    // Nilai kelembaban antara 20-95%
    $humidity = max(20, min(95, round($humidity, 1)));
    
    // Simulasi deteksi gerakan berdasarkan waktu dan aktivitas
    $motion_probability = 0.1; // Base probability
    
    // Time-based gerakan patterns
    if ($hour >= 6 && $hour <= 9) {
        $motion_probability = 0.45; // Waktu Pagi
    } elseif ($hour >= 10 && $hour <= 16) {
        $motion_probability = 0.25; // Waktu Siang
    } elseif ($hour >= 17 && $hour <= 22) {
        $motion_probability = 0.55; // Waktu Sore
    } elseif ($hour >= 23 || $hour <= 5) {
        $motion_probability = 0.08; // Waktu malam
    }
    
    // Random motion spikes
    if (rand(1, 100) <= 3) { // 3% chance of random activity
        $motion_probability = 0.8; // Menambah kemungkinan gerakan acak
    }
    
    $motion_detected = (rand(0, 1000) / 1000) < $motion_probability ? 1 : 0; // menentukan apakah gerakan terdeteksi (0) atau tidak (1)
    
    return [
        'temperature' => $temperature,
        'humidity' => $humidity,
        'motion_detected' => $motion_detected,
        'debug_info' => [
            'hour' => $hour,
            'base_daily' => round($base_daily_temp, 1),
            'seasonal' => round($seasonal_variation, 1),
            'weather' => round($weather_factor, 1),
            'extreme' => round($extreme_factor, 1),
            'motion_prob' => round($motion_probability * 100, 1) . '%'
        ]
    ];
}

// Generate dan simpan data baru
$sensor_data = generateSensorData(); // Mengambil fungsi untuk mendapatkan data sensor baru

$stmt = $conn->prepare("INSERT INTO sensor_data (timestamp, temperature, humidity, motion_detected) VALUES (NOW(), ?, ?, ?)"); // query untuk menyimpan data ke database, (NOW) memasukkan waktu saat ini
$stmt->bind_param("ddi", $sensor_data['temperature'], $sensor_data['humidity'], $sensor_data['motion_detected']);  // untuk parameter suhu, kelembaban, dan gerakan

if ($stmt->execute()) { // Jika eksekusi query berhasil
    echo "✓ Data sensor berhasil disimpan:\n";
    echo "  Waktu: " . date('Y-m-d H:i:s') . "\n";
    echo "  Suhu: " . $sensor_data['temperature'] . "°C\n";
    echo "  Kelembaban: " . $sensor_data['humidity'] . "%\n";
    echo "  Gerakan: " . ($sensor_data['motion_detected'] ? 'Terdeteksi' : 'Tidak Ada') . "\n";
    echo "  Status: Sensor Online\n";
    
    // Debug information
    echo "\n--- Debug Info ---\n";
    echo "  Jam: " . $sensor_data['debug_info']['hour'] . ":00\n";
    echo "  Base Daily: " . $sensor_data['debug_info']['base_daily'] . "°C\n";
    echo "  Seasonal: " . $sensor_data['debug_info']['seasonal'] . "°C\n";
    echo "  Weather: " . $sensor_data['debug_info']['weather'] . "°C\n";
    echo "  Extreme: " . $sensor_data['debug_info']['extreme'] . "°C\n";
    echo "  Motion Prob: " . $sensor_data['debug_info']['motion_prob'] . "\n";
} else {
    echo "✗ Error: " . $stmt->error . "\n";  // Jika gagal menyimpan data, tampilkan pesan error
}

$stmt->close(); // Menutup statement setelah selesai

// Hapus data lama (simpan hanya 7 hari terakhir)
$cleanup = $conn->prepare("DELETE FROM sensor_data WHERE timestamp < DATE_SUB(NOW(), INTERVAL 7 DAY)"); // query untuk menghapus data yang lebih dari 7 hari
$cleanup->execute(); // Menjalankan query untuk menghapus data lama
$cleanup->close(); // Menutup statement penghapusan

$conn->close(); // Menutup koneksi database
?>
