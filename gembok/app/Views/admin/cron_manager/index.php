<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">📅 Cronjob Manager</h4>
                    <a href="/admin/cron-manager/create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tambah Schedule
                    </a>
                </div>
                <div class="card-body">
                    
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (session()->getFlashdata('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= session()->getFlashdata('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th width="5%">#</th>
                                    <th>Nama Schedule</th>
                                    <th>Tipe Task</th>
                                    <th>Jadwal</th>
                                    <th>Status</th>
                                    <th>Terakhir Run</th>
                                    <th>Next Run</th>
                                    <th width="15%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($schedules)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> Belum ada schedule yang dibuat
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($schedules as $index => $schedule): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= esc($schedule['name']) ?></strong>
                                                <?php if ($schedule['task_type'] === 'auto_isolir'): ?>
                                                    <span class="badge bg-primary">Auto Isolir</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= str_replace('_', ' ', ucfirst($schedule['task_type'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-clock"></i> <?= date('H:i', strtotime($schedule['schedule_time'])) ?>
                                                    <br>
                                                    <i class="fas fa-calendar"></i> <?= ucfirst($schedule['schedule_days']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($schedule['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Non-Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($schedule['last_run']): ?>
                                                    <small><?= date('d/m/Y H:i', strtotime($schedule['last_run'])) ?></small>
                                                    <?php if ($schedule['last_status'] === 'success'): ?>
                                                        <span class="badge bg-success">✓</span>
                                                    <?php elseif ($schedule['last_status'] === 'failed'): ?>
                                                        <span class="badge bg-danger">✗</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Belum pernah</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($schedule['next_run'] && $schedule['is_active']): ?>
                                                    <small><?= date('d/m/Y H:i', strtotime($schedule['next_run'])) ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <?php if ($schedule['is_active']): ?>
                                                        <a href="/admin/cron-manager/toggle/<?= $schedule['id'] ?>" 
                                                           class="btn btn-outline-warning" 
                                                           title="Non-aktifkan"
                                                           onclick="return confirm('Non-aktifkan schedule ini?')">
                                                            <i class="fas fa-pause"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/admin/cron-manager/toggle/<?= $schedule['id'] ?>" 
                                                           class="btn btn-outline-success" 
                                                           title="Aktifkan"
                                                           onclick="return confirm('Aktifkan schedule ini?')">
                                                            <i class="fas fa-play"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="/admin/cron-manager/execute/<?= $schedule['id'] ?>" 
                                                       class="btn btn-outline-primary" 
                                                       title="Jalankan Sekarang"
                                                       onclick="return confirm('Jalankan schedule ini sekarang?')">
                                                        <i class="fas fa-play-circle"></i>
                                                    </a>
                                                    
                                                    <a href="/admin/cron-manager/edit/<?= $schedule['id'] ?>" 
                                                       class="btn btn-outline-secondary" 
                                                       title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <a href="/admin/cron-manager/delete/<?= $schedule['id'] ?>" 
                                                       class="btn btn-outline-danger" 
                                                       title="Hapus"
                                                       onclick="return confirm('Hapus schedule ini?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($recentLogs)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">📊 Log Eksekusi Terakhir</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Output</th>
                                        <th>Durasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLogs as $log): ?>
                                        <tr>
                                            <td><small><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                                            <td><?= esc($log['schedule_name']) ?></td>
                                            <td>
                                                <?php if ($log['status'] === 'success'): ?>
                                                    <span class="badge bg-success">Sukses</span>
                                                <?php elseif ($log['status'] === 'failed'): ?>
                                                    <span class="badge bg-danger">Gagal</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Dimulai</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php if ($log['output']): ?>
                                                        <?= substr(esc($log['output']), 0, 100) ?><?= strlen($log['output']) > 100 ? '...' : '' ?>
                                                    <?php elseif ($log['error_message']): ?>
                                                        <span class="text-danger"><?= esc($log['error_message']) ?></span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small><?= $log['execution_time'] ?>s</small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Cara Setup Cronjob Server:</h6>
                <p class="mb-2">Tambahkan cronjob berikut di server Anda:</p>
                <code class="d-block bg-dark text-light p-2 rounded">
                    * * * * * /usr/bin/php <?= ROOTPATH ?>cron/scheduler.php >/dev/null 2>&1
                </code>
                <small class="text-muted">Cronjob ini akan mengecek dan menjalankan schedule setiap menit.</small>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>