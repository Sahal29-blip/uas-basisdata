<?php
// index.php - Dashboard User dengan simulasi hardware monitoring
session_start();
include 'includes/db_config.php';

date_default_timezone_set('Asia/Jakarta'); // waktu GMT +7

// Ambil data sensor terbaru
$latest_data = [];
$sql_latest = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 1";
$result_latest = $conn->query($sql_latest);
$sql_first = "SELECT MIN(timestamp) as first_time FROM sensor_data WHERE DATE(timestamp) = CURDATE()";
$result_first = $conn->query($sql_first);
$first_time = null;
if ($result_first && $row = $result_first->fetch_assoc()) {
    $first_time = $row['first_time'];
}

if ($result_latest->num_rows > 0) {
    $latest_data = $result_latest->fetch_assoc();
}

// Ambil 10 data terbaru untuk chart (diurutkan ASC untuk tampilan chart yang benar)
$chart_data = [];
$sql_chart = "SELECT DATE_FORMAT(timestamp, '%H:%i') as time_label, 
              temperature, humidity, motion_detected, timestamp
              FROM sensor_data 
              ORDER BY timestamp DESC 
              LIMIT 10";
$result_chart = $conn->query($sql_chart);

if ($result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_data[] = $row;
    }
    // Reverse array agar urutan waktu dari lama ke baru untuk chart
    $chart_data = array_reverse($chart_data);
}

// Hitung statistik hari ini
$stats = [
    'avg_temp' => 0,
    'max_temp' => 0,
    'min_temp' => 0,
    'avg_humidity' => 0,
    'motion_count' => 0,
    'total_records' => 0,
    'sensor_status' => 'offline'
];

$sql_stats = "SELECT 
              AVG(temperature) as avg_temp,
              MAX(temperature) as max_temp,
              MIN(temperature) as min_temp,
              AVG(humidity) as avg_humidity,
              SUM(motion_detected) as motion_count,
              COUNT(*) as total_records,
              MAX(timestamp) as last_update
              FROM sensor_data 
              WHERE DATE(timestamp) = CURDATE()";
$result_stats = $conn->query($sql_stats);

if ($result_stats->num_rows > 0) {
    $stats = $result_stats->fetch_assoc();
}

// Tentukan status sensor berdasarkan data terakhir
$sensor_status = 'offline';
if (isset($latest_data['timestamp'])) {
    $last_update = strtotime($latest_data['timestamp']);
    $now = time();
    $sensor_status = ($now - $last_update) < 300 ? 'online' : 'offline'; // 5 menit
}

// Status koneksi sensor
if ($first_time) {
    $uptime_seconds = max(0, time() - strtotime($first_time));
    $hours = floor($uptime_seconds / 3600);
    $minutes = floor(($uptime_seconds % 3600) / 60);
    $seconds = $uptime_seconds % 60;
    $uptime = "{$hours}h {$minutes}m {$seconds}s";
} else {
    $uptime = "0h 0m 0s";
}

$sensor_connection = [
    'dht11_status' => $sensor_status,
    'pir_status' => $sensor_status,
    'last_ping' => isset($latest_data['timestamp']) ? $latest_data['timestamp'] : null,
    'uptime' => $uptime, // Uptime dinamis
    'signal_strength' => rand(75, 100)
];

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Sensor Monitoring - DHT11 & PIR Hardware</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="static/css/user.css">
    <link rel="icon" type="image/png" href="static/images/EspressifLogo.png">
    <style>
        .signal-container-vertical {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            }
        #signal-bars {
            display: flex;
            align-items: flex-end;
            height: 30px;
            gap: 2px;
        }
        .signal-bar {
            width: 7px;
            background: #bbb;
            border-radius: 2px 2px 0 0;
            transition: background 0.3s;
            margin-right: 1px;
            display: inline-block;
        }
        .signal-bar.active {
            background: #4caf50;
        }
    </style>
</head>
<body>
    <!-- Header dengan status koneksi -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <img src="static/images/EspressifLogo.png" alt="Smart Sensor" style="height:40px;vertical-align:middle;margin-right:8px;">
                Smart Sensor Monitor
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-house-user me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                            <i class="fas fa-clock me-1"></i>
                            <span id="current-time"><?= date('H:i:s') ?></span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header Dashboard -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-5 fw-bold  mb-2" style="color: white;">
                        <i class="fas fa-satellite-dish me-3"></i>Sensor Monitoring
                    </h1>
                    <p class="lead" style="color: white;">Pemantauan Suhu & Kelembaban dengan sensor DHT11 dan Sensor Gerak PIR secara real-time</p>
                    <div class="d-flex justify-content-center align-items-center gap-3 mt-3">
                        <span class="badge bg-info fs-6 px-3 py-2">
                            <i class="fas fa-thermometer-half me-2"></i>Sensor DHT11
                        </span>
                        <span class="badge bg-warning fs-6 px-3 py-2">
                            <i class="fas fa-running me-2"></i>Sensor PIR
                        </span>
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <i class="fas fa-microchip me-2"></i>ESP32
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Koneksi Hardware -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-dark text-white">
                    <div class="card-body py-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator <?= $sensor_connection['dht11_status'] === 'online' ? 'online' : 'offline' ?> me-3"></div>
                                    <div>
                                        <h6 class="mb-0 ">Sensor DHT11</h6>
                                        <small>Suhu & Kelembaban</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="status-indicator <?= $sensor_connection['pir_status'] === 'online' ? 'online' : 'offline' ?> me-3"></div>
                                    <div>
                                        <h6 class="mb-0 ">Sensor PIR</h6>
                                        <small>Deteksi Gerakan</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h6 class="mb-0">Kekuatan Sinyal</h6>
                                    <div class="signal-container-vertical">
                                        <span id="signal-strength-text"></span>
                                        <div id="signal-bars" title="Kekuatan Sinyal"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h6 class="mb-0">Waktu Aktif Sistem</h6>
                                    <small class="text-success sensor-uptime"><?= $sensor_connection['uptime'] ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Sensor Readings -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-center shadow-lg border-0 bg-gradient-danger text-white sensor-card">
                    <div class="card-body">
                        <div class="sensor-icon mb-3">
                            <i class="fas fa-thermometer-three-quarters fa-3x"></i>
                        </div>
                        <h5 class="card-title">Suhu</h5>
                        <div class="sensor-reading">
                            <span class="display-4 fw-bold" id="current-temp">
                                <?= isset($latest_data['temperature']) ? number_format($latest_data['temperature'], 1) : '0.0' ?>
                            </span>
                            <span class="fs-3">°C</span>
                        </div>
                        <div class="sensor-range mt-1">
                            <small id="temp-status">
                                <?php
                                $temperature = isset($latest_data['temperature']) ? $latest_data['temperature'] : 0;
                                if ($temperature < 21) echo 'Dingin';
                                elseif ($temperature <= 30) echo 'Normal';
                                else echo 'Panas';
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-center shadow-lg border-0 bg-gradient-info text-white sensor-card">
                    <div class="card-body">
                        <div class="sensor-icon mb-3">
                            <i class="fas fa-tint fa-3x"></i>
                        </div>
                        <h5 class="card-title">Kelembaban</h5>
                        <div class="sensor-reading">
                            <span class="display-4 fw-bold" id="current-humidity">
                                <?= isset($latest_data['humidity']) ? number_format($latest_data['humidity'], 1) : '0.0' ?>
                            </span>
                            <span class="fs-3">%</span>
                        </div>
                        <div class="humidity-level mt-1">
                            <small id="humidity-status">
                                <?php
                                $humidity = isset($latest_data['humidity']) ? $latest_data['humidity'] : 0;
                                if ($humidity < 40) echo 'Kering';
                                elseif ($humidity <= 60) echo 'Normal';
                                else echo 'Lembab';
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-center shadow-lg border-0 <?= (isset($latest_data['motion_detected']) && $latest_data['motion_detected']) ? 'bg-gradient-warning motion-detected' : 'bg-gradient-success' ?> text-white sensor-card">
                    <div class="card-body">
                        <div class="sensor-icon mb-3">
                            <i class="fas <?= (isset($latest_data['motion_detected']) && $latest_data['motion_detected']) ? 'fa-running' : 'fa-user-slash' ?> fa-3x"></i>
                        </div>
                        <h5 class="card-title">Sensor Gerak</h5>
                        <div class="sensor-reading">
                            <span class="display-6 fw-bold" id="motion-status">
                                <?= (isset($latest_data['motion_detected']) && $latest_data['motion_detected']) ? 'TERDETEKSI' : 'TIDAK ADA' ?>
                            </span>
                        </div>
                        <div class="motion-count mt-1">
                            <small id="motionEvents">Hari Ini: <?= isset($stats['motion_count']) ? $stats['motion_count'] : '0' ?> deteksi </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card text-center shadow-lg border-0 bg-gradient-secondary text-white sensor-card">
                    <div class="card-body">
                        <div class="sensor-icon mb-3">
                            <i class="fas fa-chart-line fa-3x"></i>
                        </div>
                        <h5 class="card-title">Data Tersimpan Hari Ini</h5>
                        <div class="sensor-reading">
                            <span class="display-4 fw-bold" id="total-records">
                                <?= isset($stats['total_records']) ? $stats['total_records'] : '0' ?>
                            </span>
                        </div>
                        <div class="avg-temp mt-1">
                            <small id="avgTemp">Suhu Rata-rata: <?= isset($stats['avg_temp']) ? number_format($stats['avg_temp'], 1) : '0.0' ?>°C</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Monitoring Suhu
                        </h5>
                        <small>Sensor DHT11 - Data Real-time</small>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px; position: relative;">
                            <canvas id="temperatureChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-area me-2"></i>Monitoring Kelembaban
                        </h5>
                        <small>Sensor DHT11 - Data Real-time</small>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px; position: relative;">
                            <canvas id="humidityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Motion Detection Timeline -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gradient-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-timeline me-2"></i>Timeline Sensor Monitoring
                        </h5>
                        <small>Sensor DHT11 & PIR</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="sensor-table">
                                <thead>
                                    <tr>
                                        <th><i class="fas fa-clock me-1"></i>Waktu</th>
                                        <th><i class="fas fa-thermometer-half me-1"></i>Suhu</th>
                                        <th><i class="fas fa-tint me-1"></i>Kelembaban</th>
                                        <th><i class="fas fa-running me-1"></i>Gerakan</th>
                                        <th><i class="fas fa-info-circle me-1"></i>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="sensor-data">
                                    <!-- Data akan dimuat via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h6><i class="fas fa-microchip me-2"></i>Hardware Info</h6>
                    <small>DHT11 + PIR + ESP32</small>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-wifi me-2"></i>Connection</h6>
                    <small>Real-time Data Sensor</small>
                </div>
                <div class="col-md-4">
                    <h6><i class="fas fa-calendar me-2"></i>System</h6>
                    <small>&copy; 2025 Smart Sensor Monitor</small>
                </div>
            </div>
        </div>
    </footer>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Data dari PHP
        const chartData = <?= json_encode($chart_data) ?>;
        
        // Siapkan data untuk chart
        const labels = chartData.map(item => item.time_label);
        const temperatures = chartData.map(item => parseFloat(item.temperature));
        const humidities = chartData.map(item => parseFloat(item.humidity));

        let firstTimeToday = <?= $first_time ? ('"' . $first_time . '"') : 'null' ?>;
        
        // Chart Suhu dengan styling hardware monitoring
        const tempCtx = document.getElementById('temperatureChart').getContext('2d');
        const temperatureChart = new Chart(tempCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Suhu (°C)',
                    data: temperatures,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#dc3545',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#dc3545',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Suhu (°C)',
                            font: {
                                weight: 'bold'
                            },
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu',
                            font: {
                                weight: 'bold'
                            },
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Chart Kelembaban
        const humCtx = document.getElementById('humidityChart').getContext('2d');
        const humidityChart = new Chart(humCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Kelembaban (%)',
                    data: humidities,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: '#17a2b8',
                        borderWidth: 1
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Kelembaban (%)',
                            font: {
                                weight: 'bold'
                            },
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Waktu',
                            font: {
                                weight: 'bold'
                            },
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        ticks: {
                            color: 'rgba(255, 255, 255, 0.8)'
                        },
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        // Fungsi untuk update chart dengan data baru
        function updateCharts() {
            fetch('api/get_chart_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newLabels = data.data.map(item => item.time_label);
                        const newTemperatures = data.data.map(item => parseFloat(item.temperature));
                        const newHumidities = data.data.map(item => parseFloat(item.humidity));
                        
                        // Update temperature chart
                        temperatureChart.data.labels = newLabels;
                        temperatureChart.data.datasets[0].data = newTemperatures;
                        temperatureChart.update('none'); // Smooth update
                        
                        // Update humidity chart
                        humidityChart.data.labels = newLabels;
                        humidityChart.data.datasets[0].data = newHumidities;
                        humidityChart.update('none'); // Smooth update
                    }
                })
                .catch(error => console.error('Error updating charts:', error));
        }
        
        // Update waktu real-time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString();
        }
        
        // Update data sensor secara real-time
        function updateSensorData() {
            fetch('api/get_latest_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update temperature
                        document.getElementById('current-temp').textContent = parseFloat(data.data.temperature).toFixed(1);
                        
                        // Update humidity
                        document.getElementById('current-humidity').textContent = parseFloat(data.data.humidity).toFixed(1);
                        document.getElementById('motionEvents').textContent = 'Hari Ini: ' + (data.data.motion_count ?? 0) + ' deteksi';
                        document.getElementById('avgTemp').textContent = 'Suhu Rata-rata: ' + (data.data.avg_temp ?? 0) + '°C';

                        
                        // Update temperature status
                        const tempVal = parseFloat(data.data.temperature);
                        let tempStatus = '';
                        if (tempVal < 21) tempStatus = 'Dingin';
                        else if (tempVal <= 30) tempStatus = 'Normal';
                        else tempStatus = 'Panas';
                        document.getElementById('temp-status').textContent = tempStatus;

                        // Update humidity status
                        const humVal = parseFloat(data.data.humidity);
                        let humStatus = '';
                        if (humVal < 40) humStatus = 'Kering';
                        else if (humVal <= 60) humStatus = 'Normal';
                        else humStatus = 'Lembab';
                        document.getElementById('humidity-status').textContent = humStatus;
                        
                        document.getElementById('total-records').textContent = data.data.total_records;

                        // Update motion status
                        const motionStatus = document.getElementById('motion-status');
                        const motionCard = motionStatus.closest('.card');
                        
                        if (data.data.motion_detected == 1) {
                            motionStatus.textContent = 'TERDETEKSI';
                            motionCard.className = 'card text-center shadow-lg border-0 bg-gradient-warning motion-detected text-white sensor-card';
                            motionCard.querySelector('.sensor-icon i').className = 'fas fa-running fa-3x';
                        } else {
                            motionStatus.textContent = 'TIDAK ADA';
                            motionCard.className = 'card text-center shadow-lg border-0 bg-gradient-success text-white sensor-card';
                            motionCard.querySelector('.sensor-icon i').className = 'fas fa-user-slash fa-3x';
                        }

                        // Update firstTimeToday jika berubah
                        if (data.data.first_time && data.data.first_time !== firstTimeToday) {
                            firstTimeToday = data.data.first_time;
                        }
                                
                        // Update connection status
                        document.getElementById('connection-status').innerHTML = '<i class="fas fa-wifi me-1"></i>CONNECTED';
                        document.getElementById('connection-status').className = 'badge bg-success ms-2 sensor-status';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Update status ke offline jika error
                    document.getElementById('connection-status').innerHTML = '<i class="fas fa-wifi-slash me-1"></i>OFFLINE';
                    document.getElementById('connection-status').className = 'badge bg-danger ms-2 sensor-status';
                });
                
            // Update table
            loadSensorTable();
        }
        
        function loadSensorTable() {
            fetch('api/get_sensor_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const tbody = document.getElementById('sensor-data');
                        tbody.innerHTML = '';
                        
                        data.data.forEach(row => {
                            const tr = document.createElement('tr');
                            const motionBadge = row.motion_detected == 1 ? 
                                '<span class="badge bg-warning"></i>Gerakan Terdeteksi</span>' : 
                                '<span class="badge bg-success"></i>Tidak Ada Gerakan</span>';
                            
                            const tempStatus = parseFloat(row.temperature) > 30 ? 'text-danger' : parseFloat(row.temperature) < 20 ? 'text-info' : 'text-success';
                            
                            tr.innerHTML = `
                                <td>${new Date(row.timestamp).toLocaleString('id-ID')}</td>
                                <td><span class="${tempStatus} fw-bold">${parseFloat(row.temperature).toFixed(1)}°C</span></td>
                                <td><span class="text-info fw-bold">${parseFloat(row.humidity).toFixed(1)}%</span></td>
                                <td>${motionBadge}</td>
                                <td><span class="badge bg-primary"><i class="fas fa-check-circle me-1"></i>Online</span></td>
                            `;
                            tbody.appendChild(tr);
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function updateUptime() {
            if (!firstTimeToday) {
                document.querySelectorAll('.sensor-uptime').forEach(el => el.textContent = '0h 0m 0s');
                return;
            }
            const start = new Date(firstTimeToday.replace(' ', 'T'));
            const now = new Date();
            let diff = Math.floor((now - start) / 1000);
            if (diff < 0) diff = 0;
            const h = Math.floor(diff / 3600);
            const m = Math.floor((diff % 3600) / 60);
            const s = diff % 60;
            const uptimeStr = `${h}h ${m}m ${s}s`;
            document.querySelectorAll('.sensor-uptime').forEach(el => el.textContent = uptimeStr);
        }

        setInterval(updateUptime, 1000);
        updateUptime();
        
        // Load initial data
        loadSensorTable();
        
        // Update setiap detik untuk waktu, 5 detik untuk data sensor, 10 detik untuk chart
        setInterval(updateTime, 1000);
        setInterval(updateSensorData, 3000);
        setInterval(updateCharts, 10000);
        
        // Simulasi generate data baru setiap 30 detik
        setInterval(() => {
            fetch('scripts/sensor_simulator.php')
                .then(response => response.text())
                .then(data => {
                    console.log('New sensor data generated');
                    // Update charts setelah data baru dibuat
                    setTimeout(updateCharts, 1000);
                })
                .catch(error => console.error('Error generating data:', error));
        }, 10000);

        function drawSignalBars(strength) {
            const bars = 5; // Jumlah bar sinyal
            // Bagi kekuatan sinyal ke 5 level (0-20, 21-40, dst)
            let level = Math.ceil((strength / 100) * bars);
            if (level < 0) level = 0;
            if (level > bars) level = bars;

            let html = '';
            for (let i = 1; i <= bars; i++) {
                html += `<div class="signal-bar${i <= level ? ' active' : ''}" style="height:${6*i}px"></div>`;
            }
            document.getElementById('signal-bars').innerHTML = html;
        }

        // Contoh pemanggilan di fetch:
        function updateSignalStrength() {
            fetch('api/get_latest_data.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success && result.data) {
                        drawSignalBars(result.data.signal_strength);
                    } else {
                        drawSignalBars(0);
                    }
                })
                .catch(() => {
                    drawSignalBars(0);
                });
        }
        setInterval(updateSignalStrength, 3000);
        updateSignalStrength();
    </script>
</body>
</html>
