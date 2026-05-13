import axios from 'axios';
import Swal from 'sweetalert2';
import { Livewire, Alpine as LivewireAlpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

const Alpine = LivewireAlpine;

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Swal = Swal;
window.Alpine = Alpine;

const initMetronic = () => {
    requestAnimationFrame(() => {
        window.KTComponents?.init?.();
        window.KTMenu?.init?.();
        window.KTDrawer?.init?.();
        window.KTScroll?.init?.();
        window.KTSticky?.init?.();
    });
};

window.initMetronic = initMetronic;

const swalButtonClasses = {
    primary: 'btn btn-primary',
    secondary: 'btn btn-secondary',
    success: 'btn btn-success',
    danger: 'btn btn-danger',
    warning: 'btn btn-warning',
    info: 'btn btn-info',
    light: 'btn btn-light',
};

const buildMetronicSwalOptions = (options = {}) => {
    const confirmVariant = options.confirmVariant ?? 'primary';
    const cancelVariant = options.cancelVariant ?? 'light';

    return {
        icon: options.icon ?? 'warning',
        title: options.title ?? 'Konfirmasi',
        text: options.text,
        html: options.html,
        showCancelButton: options.showCancelButton ?? false,
        showConfirmButton: options.showConfirmButton ?? true,
        confirmButtonText: options.confirmButtonText ?? 'OK',
        cancelButtonText: options.cancelButtonText ?? 'Batal',
        timer: options.timer,
        timerProgressBar: options.timerProgressBar,
        allowOutsideClick: options.allowOutsideClick,
        buttonsStyling: false,
        customClass: {
            popup: 'spm-swal-popup',
            title: 'fw-bold text-gray-900',
            htmlContainer: 'fw-semibold text-gray-600',
            confirmButton: swalButtonClasses[confirmVariant] ?? swalButtonClasses.primary,
            cancelButton: swalButtonClasses[cancelVariant] ?? swalButtonClasses.light,
            actions: 'd-flex align-items-center justify-content-center gap-3',
            ...(options.customClass ?? {}),
        },
    };
};

const fireMetronicSwal = (options = {}) => Swal.fire(buildMetronicSwalOptions(options));

const ask = (options = {}) => fireMetronicSwal({
    text: 'Lanjutkan proses ini?',
    ...options,
    showCancelButton: true,
    confirmButtonText: options.confirmButtonText ?? 'Ya, lanjutkan',
});

window.SpmSwal = {
    fire: fireMetronicSwal,
    confirm: ask,
    success: (title, text, options = {}) => fireMetronicSwal({
        icon: 'success',
        title,
        text,
        confirmVariant: 'success',
        ...options,
    }),
    error: (title, text, options = {}) => fireMetronicSwal({
        icon: 'error',
        title,
        text,
        confirmVariant: 'danger',
        ...options,
    }),
};

const callWire = (wire, method, ...args) => {
    if (!wire) return null;
    if (typeof wire[method] === 'function') return wire[method](...args);
    if (typeof wire.call === 'function') return wire.call(method, ...args);
    return null;
};

const validateFile = (event, options = {}) => {
    const file = event?.target?.files?.[0];
    if (!file) return true;

    const maxMb = options.maxMb ?? 2;
    const allowed = options.allowed ?? [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    if (!allowed.includes(file.type)) {
        event.target.value = '';
        window.SpmSwal.error('Format tidak valid', 'File harus berupa PDF, JPG, JPEG, atau PNG.');
        return false;
    }

    if (file.size > maxMb * 1024 * 1024) {
        event.target.value = '';
        window.SpmSwal.error('File terlalu besar', `Ukuran file maksimal ${maxMb}MB.`);
        return false;
    }

    return true;
};

const wilayahProvinsi = [
    { kode: '11', nama: 'Aceh' },
    { kode: '12', nama: 'Sumatera Utara' },
    { kode: '13', nama: 'Sumatera Barat' },
    { kode: '14', nama: 'Riau' },
    { kode: '15', nama: 'Jambi' },
    { kode: '16', nama: 'Sumatera Selatan' },
    { kode: '17', nama: 'Bengkulu' },
    { kode: '18', nama: 'Lampung' },
    { kode: '19', nama: 'Kepulauan Bangka Belitung' },
    { kode: '21', nama: 'Kepulauan Riau' },
    { kode: '31', nama: 'DKI Jakarta' },
    { kode: '32', nama: 'Jawa Barat' },
    { kode: '33', nama: 'Jawa Tengah' },
    { kode: '34', nama: 'DI Yogyakarta' },
    { kode: '35', nama: 'Jawa Timur' },
    { kode: '36', nama: 'Banten' },
    { kode: '51', nama: 'Bali' },
    { kode: '52', nama: 'Nusa Tenggara Barat' },
    { kode: '53', nama: 'Nusa Tenggara Timur' },
    { kode: '61', nama: 'Kalimantan Barat' },
    { kode: '62', nama: 'Kalimantan Tengah' },
    { kode: '63', nama: 'Kalimantan Selatan' },
    { kode: '64', nama: 'Kalimantan Timur' },
    { kode: '65', nama: 'Kalimantan Utara' },
    { kode: '71', nama: 'Sulawesi Utara' },
    { kode: '72', nama: 'Sulawesi Tengah' },
    { kode: '73', nama: 'Sulawesi Selatan' },
    { kode: '74', nama: 'Sulawesi Tenggara' },
    { kode: '75', nama: 'Gorontalo' },
    { kode: '76', nama: 'Sulawesi Barat' },
    { kode: '81', nama: 'Maluku' },
    { kode: '82', nama: 'Maluku Utara' },
    { kode: '91', nama: 'Papua Barat' },
    { kode: '92', nama: 'Papua Barat Daya' },
    { kode: '93', nama: 'Papua Selatan' },
    { kode: '94', nama: 'Papua' },
    { kode: '95', nama: 'Papua Tengah' },
    { kode: '96', nama: 'Papua Pegunungan' },
];

window.deleteConfirmation = () => ({
    confirmDelete(id, method = 'delete', text = 'Data yang dihapus tidak dapat dikembalikan.') {
        ask({
            title: 'Hapus data?',
            text,
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, hapus',
        }).then((result) => {
            if (result.isConfirmed) callWire(this.$wire, method, id);
        });
    },
    confirmAction(method, title = 'Simpan perubahan?', text = 'Data akan disimpan.', confirmButtonText = 'Ya, simpan', confirmVariant = 'primary') {
        ask({
            title,
            text,
            confirmButtonText,
            confirmVariant,
        }).then((result) => {
            if (result.isConfirmed) callWire(this.$wire, method);
        });
    },
});

window.adminManagement = () => ({
    confirmToggleStatus(wire, id, currentStatus, name = 'akun', label = 'Akun') {
        const nextAction = Number(currentStatus) === 1 ? 'nonaktifkan' : 'aktifkan';
        ask({
            title: `${nextAction.charAt(0).toUpperCase()}${nextAction.slice(1)} ${label}?`,
            text: `${label} ${name} akan di${nextAction}.`,
            confirmButtonText: `Ya, ${nextAction}`,
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'toggleStatus', id);
        });
    },
    confirmDeleteUser(wire, id, name = 'akun ini') {
        ask({
            title: 'Hapus akun?',
            text: `Akun ${name} akan dihapus permanen.`,
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, hapus',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'deleteUser', id);
        });
    },
    confirmSaveAccount(wire) {
        ask({
            title: 'Simpan akun?',
            text: 'Data akun akan disimpan ke sistem.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAccount');
        });
    },
    confirmSaveNV(wire) {
        ask({
            title: 'Simpan nilai verifikasi?',
            text: 'Nilai NV akan disimpan untuk proses validasi.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAdminNv');
        });
    },
    confirmApprove(wire) {
        ask({
            title: 'Setujui akreditasi?',
            text: 'Pastikan nomor SK, masa berlaku, sertifikat, dan nilai sudah benar.',
            icon: 'success',
            confirmVariant: 'success',
            confirmButtonText: 'Ya, setujui',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'approve');
        });
    },
    confirmReject(wire) {
        ask({
            title: 'Tolak akreditasi?',
            text: 'Catatan penolakan akan dikirim ke pesantren.',
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, tolak',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'reject');
        });
    },
    confirmVerifikasiPengajuan(wire) {
        const isReject = wire?.action_type === 'reject';

        ask({
            title: isReject ? 'Stop pengajuan?' : 'Lanjutkan pengajuan?',
            text: isReject
                ? 'Pengajuan akan dikembalikan dengan catatan penolakan.'
                : 'Pengajuan akan masuk ke tahap assessment sesuai asesor dan jadwal yang dipilih.',
            icon: isReject ? 'warning' : 'success',
            confirmVariant: isReject ? 'danger' : 'success',
            confirmButtonText: isReject ? 'Ya, stop' : 'Ya, lanjutkan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'verifikasi');
        });
    },
});

window.akreditasiPesantren = () => ({
    confirmCreate() {
        ask({
            title: 'Buat pengajuan akreditasi?',
            text: 'Data profil akan dikunci selama proses akreditasi berjalan.',
            confirmButtonText: 'Ya, ajukan',
        }).then((result) => {
            if (result.isConfirmed) callWire(this.$wire, 'create');
        });
    },
    confirmCancel(id, year = '') {
        ask({
            title: 'Batalkan pengajuan?',
            text: `Pengajuan ${year ? `periode ${year} ` : ''}akan dibatalkan dan data profil dibuka kembali.`,
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, batalkan',
        }).then((result) => {
            if (result.isConfirmed) callWire(this.$wire, 'cancelSubmission', id);
        });
    },
    confirmResubmit(id) {
        ask({
            title: 'Ajukan ulang?',
            text: 'Sistem akan membuat pengajuan baru berdasarkan pengajuan yang ditolak.',
            confirmButtonText: 'Ya, ajukan ulang',
        }).then((result) => {
            if (result.isConfirmed) callWire(this.$wire, 'create', id);
        });
    },
});

window.akreditasiManagement = () => ({
    confirmUploadKartu(wire) {
        ask({
            title: 'Unggah kartu kendali?',
            text: 'File akan disimpan dan dikirim ke admin untuk validasi.',
            confirmButtonText: 'Ya, unggah',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'uploadKartuKendali');
        });
    },
    confirmSaveDraft(wire) {
        ask({
            title: 'Simpan draf assessment?',
            text: 'Nilai yang sudah diisi akan disimpan sebagai draf.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAsesorEdpm');
        });
    },
    confirmVerification(wire) {
        ask({
            title: 'Selesaikan assessment?',
            text: 'Pastikan semua nilai asesor dan NK sudah lengkap.',
            icon: 'success',
            confirmVariant: 'success',
            confirmButtonText: 'Ya, selesaikan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'finalizeVerification');
        });
    },
    confirmAsesor2Final(wire) {
        ask({
            title: 'Finalkan penilaian?',
            text: 'Nilai asesor 2 akan disimpan sebagai final.',
            icon: 'success',
            confirmVariant: 'success',
            confirmButtonText: 'Ya, finalkan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAsesorEdpm', true);
        });
    },
    confirmUploadLaporan(wire) {
        ask({
            title: 'Unggah laporan visitasi?',
            text: 'File laporan akan disimpan permanen.',
            confirmButtonText: 'Ya, unggah',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'uploadLaporanVisitasi');
        });
    },
    confirmRescheduleVisitasi(wire) {
        ask({
            title: 'Simpan jadwal visitasi?',
            text: 'Jadwal visitasi akan diperbarui.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveVisitasiReschedule');
        });
    },
});

window.asesorManagement = () => ({
    confirmSaveProfile(wire) {
        ask({
            title: 'Simpan profil asesor?',
            text: 'Perubahan data profil asesor akan disimpan.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'save');
        });
    },
});

window.fileManagement = () => ({
    validate: validateFile,
    confirmSave(wire) {
        ask({
            title: 'Simpan perubahan?',
            text: 'Data dan dokumen yang dipilih akan diperbarui.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'save');
        });
    },
});

window.ipmManagement = () => ({
    validate: validateFile,
    confirmSave(wire) {
        ask({
            title: 'Simpan data IPM?',
            text: 'Dokumen IPM yang dipilih akan disimpan.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'save');
        });
    },
});

window.sdmManagement = () => ({
    confirmSave(wire) {
        ask({
            title: 'Simpan data SDM?',
            text: 'Rekap SDM pesantren akan diperbarui.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'save');
        });
    },
});

window.edpmManagement = () => ({
    validateAndNext(wire) {
        callWire(wire, 'nextStep');
    },
    confirmSaveDraft(wire) {
        ask({
            title: 'Simpan draf EDPM?',
            text: 'Nilai dan tautan bukti yang sudah diisi akan disimpan sebagai draf.',
            confirmButtonText: 'Ya, simpan draf',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveDraft');
        });
    },
    confirmSimpan(wire) {
        ask({
            title: 'Simpan EDPM permanen?',
            text: 'Pastikan seluruh nilai dan tautan bukti sudah benar.',
            icon: 'warning',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'save');
        });
    },
});

window.quillEditor = () => ({
    quill: null,
    content: '',
    init() {
        if (!window.Quill || !this.$refs.quillEditor) return;

        this.quill = new window.Quill(this.$refs.quillEditor, {
            theme: 'snow',
            placeholder: this.$el.dataset.placeholder ?? '',
            readOnly: this.$el.dataset.readOnly === 'true',
        });

        this.quill.root.innerHTML = this.content ?? '';

        this.quill.on('text-change', () => {
            this.content = this.quill.root.innerHTML;
            this.$dispatch('input', this.content);
        });

        this.$watch('content', (value) => {
            if (this.quill.root.innerHTML !== value) {
                this.quill.root.innerHTML = value ?? '';
            }
        });
    },
});

window.wilayahSelector = (config = {}) => ({
    provinsiList: wilayahProvinsi,
    kabupatenList: [],
    provinsiSearch: config.selectedProvinsiNama ?? '',
    kabupatenSearch: config.selectedKabupatenNama ?? '',
    selectedProvinsiKode: config.selectedProvinsiKode ?? '',
    selectedKabupatenKode: config.selectedKabupatenKode ?? '',
    selectedProvinsiNama: config.selectedProvinsiNama ?? '',
    selectedKabupatenNama: config.selectedKabupatenNama ?? '',
    showProvinsiConfig: false,
    showKabupatenConfig: false,
    get currentProvinsiKode() {
        if (this.selectedProvinsiKode) return this.selectedProvinsiKode;
        return this.provinsiList.find((item) => item.nama === this.selectedProvinsiNama)?.kode ?? '';
    },
    get filteredProvinsi() {
        const needle = (this.provinsiSearch ?? '').toLowerCase();
        return this.provinsiList.filter((item) => item.nama.toLowerCase().includes(needle)).slice(0, 20);
    },
    get filteredKabupaten() {
        const needle = (this.kabupatenSearch ?? '').toLowerCase();
        return this.kabupatenList.filter((item) => item.nama.toLowerCase().includes(needle)).slice(0, 50);
    },
    init() {
        if (this.selectedProvinsiNama && !this.provinsiSearch) {
            this.provinsiSearch = this.selectedProvinsiNama;
        }
        if (this.selectedKabupatenNama && !this.kabupatenSearch) {
            this.kabupatenSearch = this.selectedKabupatenNama;
        }
        if (this.currentProvinsiKode) this.loadKabupaten(this.currentProvinsiKode);
    },
    selectProvinsi(item) {
        this.selectedProvinsiKode = item.kode;
        this.selectedProvinsiNama = item.nama;
        this.provinsiSearch = item.nama;
        this.selectedKabupatenKode = '';
        this.selectedKabupatenNama = '';
        this.kabupatenSearch = '';
        this.showProvinsiConfig = false;
        this.loadKabupaten(item.kode);
    },
    selectKabupaten(item) {
        this.selectedKabupatenKode = item.kode;
        this.selectedKabupatenNama = item.nama;
        this.kabupatenSearch = item.nama;
        this.showKabupatenConfig = false;
    },
    async loadKabupaten(kode) {
        if (!kode) return;
        try {
            const response = await fetch(`https://www.emsifa.com/api-wilayah-indonesia/api/regencies/${kode}.json`);
            const data = await response.json();
            this.kabupatenList = data.map((item) => ({
                kode: item.id,
                nama: item.name,
            }));
        } catch (error) {
            this.kabupatenList = [];
        }
    },
});

window.dashboardCharts = (chartData = [], stats = {}) => ({
    init() {
        this.$nextTick(() => {
            if (!window.Chart) return;

            const monthly = document.getElementById('monthlyChart');
            if (monthly) {
                new window.Chart(monthly, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        datasets: [{
                            label: 'Pengajuan',
                            data: chartData,
                            borderColor: '#1e3a5f',
                            backgroundColor: 'rgba(30, 58, 95, 0.12)',
                            fill: true,
                            tension: 0.35,
                        }],
                    },
                    options: { responsive: true, maintainAspectRatio: false },
                });
            }

            const status = document.getElementById('statusChart');
            if (status) {
                new window.Chart(status, {
                    type: 'doughnut',
                    data: {
                        labels: ['Terakreditasi', 'Ditolak'],
                        datasets: [{
                            data: [stats.terakreditasi ?? 0, stats.ditolak ?? 0],
                            backgroundColor: ['#10b981', '#ef4444'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: { legend: { display: false } },
                    },
                });
            }
        });
    },
});

window.addEventListener('show-validation-alert', (event) => {
    window.SpmSwal.fire({
        icon: 'warning',
        title: event.detail.title ?? 'Validasi gagal',
        html: event.detail.html ?? event.detail.message ?? '',
    });
});

window.addEventListener('validation-failed', (event) => {
    window.SpmSwal.fire({
        icon: 'warning',
        title: event.detail.title ?? 'Validasi gagal',
        html: event.detail.html ?? event.detail.message ?? '',
    });
});

window.addEventListener('show-validation-error', () => {
    window.SpmSwal.fire({
        icon: 'warning',
        title: 'Data belum lengkap',
        html: 'Mohon periksa kembali isian yang ditandai pada formulir.',
    });
});

window.addEventListener('show-metronic-alert', (event) => {
    window.SpmSwal.fire({
        icon: event.detail.type ?? event.detail.icon ?? 'info',
        title: event.detail.title ?? 'Informasi',
        text: event.detail.message ?? event.detail.text,
        html: event.detail.html,
        confirmVariant: event.detail.type === 'error' ? 'danger' : 'primary',
    });
});

document.addEventListener('livewire:initialized', () => {
    Livewire.on('swal:success', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;

        window.SpmSwal.success(payload?.title ?? 'Berhasil', payload?.text ?? payload?.message ?? '', {
            timer: 3000,
            timerProgressBar: true,
        });
    });

    Livewire.on('swal:error', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;

        window.SpmSwal.error(payload?.title ?? 'Gagal', payload?.text ?? payload?.message ?? '');
    });
});

document.addEventListener('DOMContentLoaded', initMetronic);
document.addEventListener('livewire:initialized', initMetronic);
document.addEventListener('livewire:navigated', initMetronic);

Alpine.store('sidebar', { open: false });
Alpine.data('deleteConfirmation', window.deleteConfirmation);
Alpine.data('adminManagement', window.adminManagement);
Alpine.data('akreditasiPesantren', window.akreditasiPesantren);
Alpine.data('edpmManagement', window.edpmManagement);
Alpine.data('ipmManagement', window.ipmManagement);
Alpine.data('sdmManagement', window.sdmManagement);
Alpine.data('fileManagement', window.fileManagement);
Alpine.data('asesorManagement', window.asesorManagement);

Livewire.start();
