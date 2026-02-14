<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-<?= $schedule ? 'edit' : 'plus' ?>"></i> 
                        <?= $title ?>
                    </h4>
                </div>
                <div class="card-body">
                    
                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6><i class="fas fa-exclamation-triangle"></i> Terjadi Kesalahan:</h6>
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="/admin/cron-manager/save" method="post" id="scheduleForm">
                        <?= csrf_field() ?>
                        
                        <?php if ($schedule): ?>
                            <input type="hidden" name="id" value="<?= $schedule['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Nama Schedule <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control <?= session()->getFlashdata('errors.name') ? 'is-invalid' : '' ?>" 
                                           id="name" 
                                           name="name" 
                                           value="<?= old('name', $schedule['name'] ?? '') ?>" 
                                           required>
                                    <div class="form-text">Nama yang mudah diingat untuk schedule ini</div>
                                    <?php if (session()->getFlashdata('errors.name')): ?>
                                        <div class="invalid-feedback"><?= session()->getFlashdata('errors.name') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_type" class="form-label">Tipe Task <span class="text-danger">*</span></label>
                                    <select class="form-select <?= session()->getFlashdata('errors.task_type') ? 'is-invalid' : '' ?>" 
                                            id="task_type" 
                                            name="task_type" 
                                            required>
                                        <option value="">Pilih Tipe Task</option>
                                        <option value="auto_isolir" <?= old('task_type', $schedule['task_type'] ?? '') === 'auto_isolir' ? 'selected' : '' ?>>
                                            🔒 Auto Isolir Pelanggan
                                        </option>
                                        <option value="backup_db" <?= old('task_type', $schedule['task_type'] ?? '') === 'backup_db' ? 'selected' : '' ?>>
                                            💾 Backup Database
                                        </option>
                                        <option value="send_reminders" <?= old('task_type', $schedule['task_type'] ?? '') === 'send_reminders' ? 'selected' : '' ?>>
                                            📧 Kirim Pengingat Tagihan
                                        </option>
                                        <option value="custom_script" <?= old('task_type', $schedule['task_type'] ?? '') === 'custom_script' ? 'selected' : '' ?>>
                                            ⚙️ Custom Script
                                        </option>
                                    </select>
                                    <div class="form-text">Pilih jenis task yang akan dijalankan</div>
                                    <?php if (session()->getFlashdata('errors.task_type')): ?>
                                        <div class="invalid-feedback"><?= session()->getFlashdata('errors.task_type') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="schedule_time" class="form-label">Waktu Eksekusi <span class="text-danger">*</span></label>
                                    <input type="time" 
                                           class="form-control <?= session()->getFlashdata('errors.schedule_time') ? 'is-invalid' : '' ?>" 
                                           id="schedule_time" 
                                           name="schedule_time" 
                                           value="<?= old('schedule_time', $schedule['schedule_time'] ?? '02:00') ?>" 
                                           required>
                                    <div class="form-text">Waktu eksekusi dalam format 24 jam</div>
                                    <?php if (session()->getFlashdata('errors.schedule_time')): ?>
                                        <div class="invalid-feedback"><?= session()->getFlashdata('errors.schedule_time') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="schedule_days" class="form-label">Jadwal Hari <span class="text-danger">*</span></label>
                                    <select class="form-select <?= session()->getFlashdata('errors.schedule_days') ? 'is-invalid' : '' ?>" 
                                            id="schedule_days" 
                                            name="schedule_days" 
                                            required>
                                        <option value="">Pilih Jadwal</option>
                                        <option value="daily" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'daily' ? 'selected' : '' ?>>
                                            Setiap Hari
                                        </option>
                                        <option value="weekly" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'weekly' ? 'selected' : '' ?>>
                                            Setiap Minggu
                                        </option>
                                        <option value="monthly" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'monthly' ? 'selected' : '' ?>>
                                            Setiap Bulan
                                        </option>
                                        <option value="monday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'monday' ? 'selected' : '' ?>>
                                            Setiap Senin
                                        </option>
                                        <option value="tuesday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'tuesday' ? 'selected' : '' ?>>
                                            Setiap Selasa
                                        </option>
                                        <option value="wednesday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'wednesday' ? 'selected' : '' ?>>
                                            Setiap Rabu
                                        </option>
                                        <option value="thursday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'thursday' ? 'selected' : '' ?>>
                                            Setiap Kamis
                                        </option>
                                        <option value="friday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'friday' ? 'selected' : '' ?>>
                                            Setiap Jumat
                                        </option>
                                        <option value="saturday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'saturday' ? 'selected' : '' ?>>
                                            Setiap Sabtu
                                        </option>
                                        <option value="sunday" <?= old('schedule_days', $schedule['schedule_days'] ?? '') === 'sunday' ? 'selected' : '' ?>>
                                            Setiap Minggu
                                        </option>
                                    </select>
                                    <div class="form-text">Pilih frekuensi eksekusi</div>
                                    <?php if (session()->getFlashdata('errors.schedule_days')): ?>
                                        <div class="invalid-feedback"><?= session()->getFlashdata('errors.schedule_days') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               id="is_active" 
                                               name="is_active" 
                                               value="1" 
                                               <?= old('is_active', $schedule['is_active'] ?? true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">
                                            Aktifkan schedule ini
                                        </label>
                                    </div>
                                    <div class="form-text">Schedule hanya akan berjalan jika diaktifkan</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Simpan Schedule
                                    </button>
                                    <a href="/admin/cron-manager" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-lightbulb"></i> Tips Penggunaan:</h6>
                <ul class="mb-0">
                    <li><strong>Auto Isolir Pelanggan:</strong> Jalankan setiap hari jam 02:00 pagi untuk mengecek tagihan overdue</li>
                    <li><strong>Backup Database:</strong> Jalankan setiap minggu untuk backup otomatis</li>
                    <li><strong>Kirim Pengingat Tagihan:</strong> Jalankan setiap hari untuk kirim notifikasi tagihan</li>
                    <li>Pastikan cronjob server sudah di-setup agar scheduler ini berjalan otomatis</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#scheduleForm').on('submit', function(e) {
        e.preventDefault();
        
        var form = this;
        var btn = $(this).find('button[type="submit"]');
        var originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...').prop('disabled', true);
        
        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: $(form).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.href = '/admin/cron-manager';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                    btn.html(originalText).prop('disabled', false);
                }
            },
            error: function() {
                // Fallback to normal form submission
                form.submit();
            }
        });
    });
});
</script>
<?= $this->endSection() ?>