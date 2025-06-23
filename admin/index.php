
<?php
session_start();
include '../includes/db_config.php';

// Cek login
check_admin_login();

// Ambil semua data sensor (10 terbaru)
$all_data = [];
$sql = "SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 10";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $all_data[] = $row;
    }
}

// Data untuk chart: hari ini
$chart_data = [];
$sql_chart = "SELECT * FROM sensor_data WHERE DATE(timestamp) = CURDATE() ORDER BY timestamp DESC";
$result_chart = $conn->query($sql_chart);
if ($result_chart->num_rows > 0) {
    while ($row = $result_chart->fetch_assoc()) {
        $chart_data[] = $row;
    }
}

// --- Tambahan statistik utama ---
$total_records = 0;
$avg_temp = 0;
$avg_hum = 0;
$motion_events = 0;

$sql_stats = "SELECT COUNT(*) as total, AVG(temperature) as avg_temp, AVG(humidity) as avg_hum, SUM(motion_detected) as motion_events FROM sensor_data WHERE DATE(timestamp) = CURDATE()";
$res_stats = $conn->query($sql_stats);
if ($res_stats && $row_stats = $res_stats->fetch_assoc()) {
    $total_records = $row_stats['total'];
    $avg_temp = $row_stats['avg_temp'];
    $avg_hum = $row_stats['avg_hum'];
    $motion_events = $row_stats['motion_events'];
}
$conn->close();

// Hitung kategori suhu, kelembaban, gerakan
$low = $mid = $high = 0;
$dry = $normal = $humid = 0;
$motionCount = 0;
$noMotionCount = 0;
foreach ($chart_data as $d) {
    if ($d['temperature'] <= 21.00) $low++;
    elseif ($d['temperature'] > 21.00 && $d['temperature'] <= 30.00) $mid++;
    elseif ($d['temperature'] > 30.00) $high++;
    if ($d['humidity'] < 30) $dry++;
    elseif ($d['humidity'] <= 60) $normal++;
    else $humid++;
    if ($d['motion_detected']) $motionCount++;
    else $noMotionCount++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sensor Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../static/css/admin.css">
    <link rel="icon" type="image/png" href="../static/images/EspressifLogo.png">
    
</head>
<body>
    <!-- Animasi partikel background -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="../static/images/EspressifLogo.png" alt="Smart Sensor" style="height:40px;vertical-align:middle;margin-right:8px;">
                Monitoring Admin Panel
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../"><i class="fas fa-home me-1"></i>Dashboard User</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-4">
        <!-- Main Title -->
        <h1 class="main-title">
            <i class="fas fa-cogs me-3"></i>Monitoring Sensor Management
        </h1>
        <!-- Add Data Button -->
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-success-modern btn-modern" data-bs-toggle="modal" data-bs-target="#addDataModal">
                <i class="fas fa-plus-circle me-2"></i>Tambah Data Sensor
            </button>
        </div>
        <div class="container-fluid mt-4">

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="totalRecords"><?= number_format($total_records) ?></div>
                    <div class="stats-label">Data Tersimpan Hari Ini</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="avgTemp"><?= is_null($avg_temp) ? '0' : number_format($avg_temp, 1) ?>°C</div>
                    <div class="stats-label">Suhu Rata-rata</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="avgHumidity"><?= is_null($avg_hum) ? '0' : number_format($avg_hum, 1) ?>%</div>
                    <div class="stats-label">Kelembaban Rata-rata</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-number" id="motionEvents"><?= number_format($motion_events) ?></div>
                    <div class="stats-label">Gerakan Terdeteksi Hari Ini</div>
                </div>
            </div>
        </div>
        <!-- Charts Section -->
        <div class="row mb-5">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-thermometer-half me-2"></i>Distribusi Suhu
                    </div>
                    <div class="chart-container">
                        <canvas id="tempChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tint me-2"></i>Level Kelembaban
                    </div>
                    <div class="chart-container">
                        <canvas id="humChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-running me-2"></i>Deteksi Gerakan
                    </div>
                    <div class="chart-container">
                        <canvas id="motionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Data Table -->
        <div class="table-container">
            <div class="p-4">
                <h5 class="text-white mb-4">
                    <i class="fas fa-table me-2"></i>Data Sensor Terbaru
                </h5>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Waktu</th>
                                <th>Suhu</th>
                                <th>Kelembaban</th>
                                <th>Gerakan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="sensor-table-body">
                            <?php if (!empty($all_data)): ?>
                                <?php foreach ($all_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data['id']) ?></td>
                                    <td><?= date('d/m/Y H:i:s', strtotime($data['timestamp'])) ?></td>
                                    <td><?= number_format($data['temperature'], 1) ?>°C</td>
                                    <td><?= number_format($data['humidity'], 1) ?>%</td>
                                    <td>
                                        <span class="badge <?= $data['motion_detected'] ? 'bg-warning-modern' : 'bg-success-modern' ?>">
                                            <?= $data['motion_detected'] ? 'Gerakan Terdeteksi' : 'Tidak Ada Gerakan' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary-modern btn-modern btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editDataModal"
                                            data-id="<?= $data['id'] ?>" 
                                            data-timestamp="<?= $data['timestamp'] ?>"
                                            data-temperature="<?= $data['temperature'] ?>"
                                            data-humidity="<?= $data['humidity'] ?>"
                                            data-motion="<?= $data['motion_detected'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger-modern btn-modern btn-sm btn-delete"
                                            data-id="<?= $data['id'] ?>" data-bs-toggle="modal" data-bs-target="#deleteDataModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada data sensor.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Tambah Data -->
    <div class="modal fade" id="addDataModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success-modern text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Data Sensor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="add.php" method="POST">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Waktu</label>
                            <input type="datetime-local" class="form-control" id="add_timestamp" name="timestamp" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Suhu (°C)</label>
                            <input type="number" step="0.1" class="form-control" name="temperature" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Kelembaban (%)</label>
                            <input type="number" step="0.1" class="form-control" name="humidity" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Deteksi Gerakan</label>
                            <select class="form-control" name="motion_detected" required>
                                <option value="0">Tidak Ada</option>
                                <option value="1">Terdeteksi</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success-modern btn-modern w-100">
                            <i class="fas fa-save me-2"></i>Simpan Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Edit Data -->
    <div class="modal fade" id="editDataModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-modern text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Data Sensor
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="edit.php" method="POST">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Waktu</label>
                            <input type="datetime-local" class="form-control" id="edit_timestamp" name="timestamp" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Suhu (°C)</label>
                            <input type="number" step="0.1" class="form-control" id="edit_temperature" name="temperature" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Kelembaban (%)</label>
                            <input type="number" step="0.1" class="form-control" id="edit_humidity" name="humidity" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Deteksi Gerakan</label>
                            <select class="form-control" id="edit_motion" name="motion_detected" required>
                                <option value="0">Tidak ada</option>
                                <option value="1">Terdeteksi</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                            <i class="fas fa-save me-2"></i>Update Data
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal Konfirmasi Hapus Data -->
    <div class="modal fade" id="deleteDataModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger-modern text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Konfirmasi Hapus Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="deleteForm" action="delete.php" method="GET">
                        <input type="hidden" id="delete_id" name="id">
                        <p class="mb-4 text-center fw-semibold fs-5" style="letter-spacing:0.5px;">
                            Apakah Anda yakin ingin menghapus data sensor ini?
                        </p>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger-modern btn-modern">
                                <i class="fas fa-trash me-2"></i>Hapus
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Chart Suhu Kategori
        new Chart(document.getElementById('tempChart'), {
            type: 'doughnut',
            data: {
                labels: ['Dingin', 'Normal', 'Panas'],
                datasets: [{
                    data: [<?= $low ?>, <?= $mid ?>, <?= $high ?>],
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)'
                    ],
                    borderColor: [
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(239, 68, 68, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white',
                            font: { size: 12, weight: '500' },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                elements: { arc: { borderWidth: 0 } }
            }
        });
        // Chart Kelembaban 
        new Chart(document.getElementById('humChart'), {
            type: 'doughnut',
            data: {
                labels: ['Kering', 'Normal', 'Lembab'],
                datasets: [{
                    data: [<?= $dry ?>, <?= $normal ?>, <?= $humid ?>],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
                    ],
                    borderColor: [
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white',
                            font: { size: 12, weight: '500' },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                elements: { arc: { borderWidth: 0 } }
            }
        });
        // Chart Gerakan
        new Chart(document.getElementById('motionChart'), {
            type: 'doughnut',
            data: {
                labels: ['Gerakan Terdeteksi', 'Tidak Ada Gerakan'],
                datasets: [{
                    data: [<?= $motionCount ?>, <?= $noMotionCount ?>],
                    backgroundColor: [
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderColor: [
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: 'white',
                            font: { size: 12, weight: '500' },
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: 'white',
                        bodyColor: 'white',
                        borderColor: 'rgba(255, 255, 255, 0.2)',
                        borderWidth: 1,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                elements: { arc: { borderWidth: 0 } }
            }
        });

        // Script untuk mengisi modal edit
        var editDataModal = document.getElementById('editDataModal');
        editDataModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var timestamp = button.getAttribute('data-timestamp');
            var temperature = button.getAttribute('data-temperature');
            var humidity = button.getAttribute('data-humidity');
            var motion = button.getAttribute('data-motion');
            // Format timestamp untuk input datetime-local
            var date = new Date(timestamp);
            var formattedDate = date.getFullYear() + '-' + 
                String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                String(date.getDate()).padStart(2, '0') + 'T' + 
                String(date.getHours()).padStart(2, '0') + ':' + 
                String(date.getMinutes()).padStart(2, '0');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_timestamp').value = formattedDate;
            document.getElementById('edit_temperature').value = temperature;
            document.getElementById('edit_humidity').value = humidity;
            document.getElementById('edit_motion').value = motion;
        });

        // Set default timestamp untuk modal tambah
        document.getElementById('add_timestamp').value = new Date().toISOString().slice(0, 16);

        // Fungsi fetch data sensor (opsional, jika ingin auto-refresh tabel)
        function fetchSensorData() {
            fetch('../api/get_all_data.php')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        const tbody = document.getElementById('sensor-table-body');
                        let html = '';
                        if (result.data.length > 0) {
                            result.data.forEach(data => {
                                html += `
                                <tr>
                                    <td>${data.id}</td>
                                    <td>${new Date(data.timestamp).toLocaleString('id-ID')}</td>
                                    <td>${parseFloat(data.temperature).toFixed(1)}°C</td>
                                    <td>${parseFloat(data.humidity).toFixed(1)}%</td>
                                    <td>
                                        <span class="badge ${data.motion_detected == 1 ? 'bg-warning-modern' : 'bg-success-modern'}">
                                            ${data.motion_detected == 1 ? 'Gerakan Terdeteksi' : 'Tidak Ada Gerakan'}
                                        </span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-primary-modern btn-modern btn-sm me-2" data-bs-toggle="modal" data-bs-target="#editDataModal"
                                            data-id="${data.id}"
                                            data-timestamp="${data.timestamp}"
                                            data-temperature="${data.temperature}"
                                            data-humidity="${data.humidity}"
                                            data-motion="${data.motion_detected}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger-modern btn-modern btn-sm btn-delete"
                                            data-id="${data.id}" data-bs-toggle="modal" data-bs-target="#deleteDataModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>`;
                            });
                        } else {
                            html = `<tr><td colspan="6" class="text-center">Belum ada data sensor.</td></tr>`;
                        }
                        tbody.innerHTML = html;
                    }
                });
        }
        function updateStats() {
            fetch('../api/stats_api.php')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('totalRecords').textContent = data.total.toLocaleString();
                    document.getElementById('avgTemp').textContent = (isNaN(data.avg_temp) ? '0' : data.avg_temp) + '°C';
                    document.getElementById('avgHumidity').textContent = (isNaN(data.avg_hum) ? '0' : data.avg_hum) + '%';
                    document.getElementById('motionEvents').textContent = data.motion_events.toLocaleString();
                });
        }

        // Isi modal hapus dengan id data
        document.addEventListener('click', function(e) {
            if (e.target.closest('.btn-delete')) {
                var btn = e.target.closest('.btn-delete');
                var id = btn.getAttribute('data-id');
                document.getElementById('delete_id').value = id;
            }
        });

        // Panggil saat load dan setiap 5 detik
        updateStats();
        setInterval(updateStats, 5000);
        // Jalankan setiap 5 detik
        setInterval(fetchSensorData, 5000);
        // Jalankan sekali saat halaman load
        fetchSensorData();
    </script>
</body>
</html>