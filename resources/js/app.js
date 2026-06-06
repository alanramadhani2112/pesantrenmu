import Dropzone from 'dropzone';
import axios from 'axios';
import autosize from 'autosize';
import { createPopper } from '@popperjs/core';
import formValidation from './validation';
import { Livewire, Alpine as LivewireAlpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

window.Dropzone = Dropzone;
window.autosize = autosize;
window.Popper = { createPopper };

const Alpine = LivewireAlpine;
Alpine.data('formValidation', formValidation);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
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

let pageLoadingTimer = null;

const showPageLoadingOverlay = () => {
    clearTimeout(pageLoadingTimer);

    document.body?.classList.add('spm-is-navigating');
};

const hidePageLoadingOverlay = () => {
    clearTimeout(pageLoadingTimer);

    pageLoadingTimer = window.setTimeout(() => {
        document.body?.classList.remove('spm-is-navigating');
    }, 120);
};

window.showPageLoadingOverlay = showPageLoadingOverlay;
window.hidePageLoadingOverlay = hidePageLoadingOverlay;

const submitLockButtonSelector = [
    'button[type="submit"]',
    'input[type="submit"]',
    '[data-spm-submit-button="true"]',
].join(',');

const getSubmitLockButtons = (form) => Array.from(form.querySelectorAll(submitLockButtonSelector))
    .filter((button) => !button.matches('[data-spm-submit-lock="off"]'));

const lockSubmitButtons = (form) => {
    form.setAttribute('data-spm-submit-lock', 'active');
    form.setAttribute('aria-busy', 'true');

    getSubmitLockButtons(form).forEach((button) => {
        if (button.disabled) {
            button.setAttribute('data-spm-submit-was-disabled', 'true');
        } else {
            button.setAttribute('data-spm-submit-locked-by-guard', 'true');
            button.disabled = true;
        }

        button.setAttribute('aria-disabled', 'true');
        button.classList.add('spm-submit-locking');
    });
};

const releaseSubmitLockGuard = (form) => {
    form.removeAttribute('data-spm-submit-lock');
    form.removeAttribute('data-spm-submit-started-at');
    form.removeAttribute('aria-busy');

    getSubmitLockButtons(form).forEach((button) => {
        if (button.getAttribute('data-spm-submit-locked-by-guard') === 'true') {
            button.disabled = false;
        }

        button.removeAttribute('data-spm-submit-locked-by-guard');
        button.removeAttribute('data-spm-submit-was-disabled');
        button.removeAttribute('aria-disabled');
        button.classList.remove('spm-submit-locking');
    });
};

const releaseAllSubmitLockGuards = () => {
    document
        .querySelectorAll('form[data-spm-submit-lock="active"]')
        .forEach((form) => releaseSubmitLockGuard(form));
};

const initSubmitLockGuard = () => {
    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) return;
        if (form.matches('[data-spm-submit-lock="off"]')) return;

        if (form.getAttribute('data-spm-submit-lock') === 'active') {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        const submitter = event.submitter;

        if (submitter?.matches?.('[data-spm-submit-lock="off"]')) return;
        if (!form.noValidate && typeof form.checkValidity === 'function' && !form.checkValidity()) return;

        form.setAttribute('data-spm-submit-lock', 'active');
        form.setAttribute('data-spm-submit-started-at', String(Date.now()));
        window.requestAnimationFrame(() => lockSubmitButtons(form));
    }, true);
};

window.releaseSubmitLockGuard = releaseSubmitLockGuard;
window.releaseAllSubmitLockGuards = releaseAllSubmitLockGuards;

const swalButtonClasses = {
    primary: 'btn btn-primary',
    secondary: 'btn btn-secondary',
    success: 'btn btn-success',
    danger: 'btn btn-danger',
    warning: 'btn btn-warning',
    info: 'btn btn-info',
    light: 'btn btn-light',
};

const swalSuccessTimer = 3000;

let swalPromise = null;

const getSwal = () => {
    swalPromise ??= import('sweetalert2').then((module) => {
        const Swal = module.default ?? module;
        window.Swal = Swal;

        return Swal;
    });

    return swalPromise;
};

window.getSpmSwal = getSwal;

const closeOpenActionMenus = () => {
    window.dispatchEvent(new CustomEvent('spm:action-menu-close-all'));
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
            title: 'fw-semibold text-gray-900',
            htmlContainer: 'fw-semibold text-gray-600',
            confirmButton: swalButtonClasses[confirmVariant] ?? swalButtonClasses.primary,
            cancelButton: swalButtonClasses[cancelVariant] ?? swalButtonClasses.light,
            actions: 'd-flex align-items-center justify-content-center gap-3',
            ...(options.customClass ?? {}),
        },
    };
};

const fireMetronicSwal = async (options = {}) => {
    const Swal = await getSwal();

    return Swal.fire(buildMetronicSwalOptions(options));
};

const ask = (options = {}) => {
    closeOpenActionMenus();

    return fireMetronicSwal({
        text: 'Lanjutkan proses ini?',
        ...options,
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText ?? 'Ya, lanjutkan',
    }).then((result) => {
        if (!result.isConfirmed) releaseAllSubmitLockGuards();

        return result;
    }).catch((error) => {
        releaseAllSubmitLockGuards();

        throw error;
    });
};

window.SpmSwal = {
    fire: fireMetronicSwal,
    confirm: ask,
    success: (title, text, options = {}) => fireMetronicSwal({
        icon: 'success',
        title,
        text,
        timer: options.timer ?? swalSuccessTimer,
        timerProgressBar: options.timerProgressBar ?? true,
        showConfirmButton: options.showConfirmButton ?? false,
        confirmVariant: 'success',
        ...options,
    }),
    error: (title, text, options = {}) => fireMetronicSwal({
        icon: 'error',
        title,
        text,
        timer: options.timer,
        timerProgressBar: options.timerProgressBar,
        showConfirmButton: options.showConfirmButton ?? true,
        confirmVariant: 'danger',
        ...options,
    }),
    warning: (title, text, options = {}) => fireMetronicSwal({
        icon: 'warning',
        title,
        text,
        timer: options.timer,
        timerProgressBar: options.timerProgressBar,
        showConfirmButton: options.showConfirmButton ?? true,
        confirmVariant: 'warning',
        ...options,
    }),
    info: (title, text, options = {}) => fireMetronicSwal({
        icon: 'info',
        title,
        text,
        timer: options.timer,
        timerProgressBar: options.timerProgressBar,
        showConfirmButton: options.showConfirmButton ?? true,
        confirmVariant: 'primary',
        ...options,
    }),
};

const normalizeAlertPayload = (data = {}) => {
    if (Array.isArray(data)) return data[0] ?? {};
    if (data.detail) return data.detail;

    return data ?? {};
};

const normalizeSwalIcon = (type) => {
    const icon = type ?? 'info';

    if (icon === 'danger') return 'error';
    if (['success', 'error', 'warning', 'info', 'question'].includes(icon)) return icon;

    return 'info';
};

const confirmVariantForAlert = (icon) => ({
    success: 'success',
    error: 'danger',
    warning: 'warning',
    info: 'primary',
    question: 'primary',
}[icon] ?? 'primary');

const fireNotificationAlert = (data = {}) => {
    const payload = normalizeAlertPayload(data);
    const icon = normalizeSwalIcon(payload.type ?? payload.icon);

    return window.SpmSwal.fire({
        icon,
        title: payload.title ?? (icon === 'success' ? 'Berhasil' : 'Informasi'),
        text: payload.text ?? payload.message,
        html: payload.html,
        timer: payload.timer ?? (icon === 'success' ? swalSuccessTimer : undefined),
        timerProgressBar: payload.timerProgressBar ?? (icon === 'success'),
        showConfirmButton: payload.showConfirmButton ?? (icon !== 'success'),
        confirmVariant: payload.confirmVariant ?? confirmVariantForAlert(icon),
    });
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
    confirmUnlinkSso(wire, id, name = 'akun ini') {
        ask({
            title: 'Unlink SSO?',
            text: `Akun ${name} akan di-unlink dari Muhammadiyah ID. Data profil SSO akan dihapus.`,
            icon: 'warning',
            confirmVariant: 'warning',
            confirmButtonText: 'Ya, unlink',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'unlinkSso', id);
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
                : 'Pengajuan akan masuk ke tahap Review Asesor sesuai tim penilai yang dipilih.',
            icon: isReject ? 'warning' : 'success',
            confirmVariant: isReject ? 'danger' : 'success',
            confirmButtonText: isReject ? 'Ya, stop' : 'Ya, lanjutkan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'verifikasi');
        });
    },
    confirmToggleLock(wire, isLocked) {
        const action = isLocked ? 'buka kunci' : 'kunci';
        ask({
            title: `${isLocked ? 'Buka kunci' : 'Kunci'} data pesantren?`,
            text: isLocked
                ? 'Data pesantren akan dapat diedit kembali oleh pesantren.'
                : 'Data pesantren akan dikunci dan tidak dapat diedit.',
            icon: 'warning',
            confirmVariant: isLocked ? 'success' : 'warning',
            confirmButtonText: `Ya, ${action}`,
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'toggleLock');
        });
    },
    confirmReassignAsesor(wire) {
        ask({
            title: 'Ganti asesor?',
            text: 'Asesor lama akan dilepas dari tugas ini dan digantikan asesor baru.',
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, ganti asesor',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'reassignAsesor');
        });
    },
    confirmRejectFinal(wire) {
        ask({
            title: 'Tolak akreditasi secara final?',
            text: 'Keputusan penolakan final tidak dapat dibatalkan. Pesantren akan menerima notifikasi.',
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, tolak final',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'reject');
        });
    },
    confirmAssignReviewer(wire) {
        ask({
            title: 'Tugaskan reviewer?',
            text: 'Pilih reviewer di modal yang akan muncul.',
            confirmButtonText: 'Pilih Reviewer',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'openAssignModal');
        });
    },
    confirmReassignReviewer(wire) {
        ask({
            title: 'Ganti reviewer?',
            text: 'Pilih reviewer baru di modal yang akan muncul.',
            icon: 'warning',
            confirmVariant: 'warning',
            confirmButtonText: 'Pilih Reviewer Baru',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'openAssignModal');
        });
    },
    confirmBandingDecision(wire, type) {
        const isAccept = type === 'accept';
        ask({
            title: isAccept ? 'Terima banding?' : 'Tolak banding?',
            text: isAccept
                ? 'Banding akan diterima dan proses akreditasi kembali ke tahap Validasi Admin.'
                : 'Banding akan ditolak. Keputusan ini tidak dapat dibatalkan.',
            icon: isAccept ? 'success' : 'warning',
            confirmVariant: isAccept ? 'success' : 'danger',
            confirmButtonText: isAccept ? 'Ya, terima' : 'Ya, tolak',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'openDecisionModal', type);
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
    confirmSubmitPerbaikan(wire) {
        ask({
            title: 'Kirim perbaikan?',
            text: 'Asesor akan menerima notifikasi bahwa perbaikan dokumen telah dikirim.',
            confirmButtonText: 'Ya, kirim',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'submitPerbaikan');
        });
    },
});

window.spmActionMenu = (menuId) => ({
    isOpen: false,
    placementStyle: 'position: fixed; top: 0; left: 0; visibility: hidden; z-index: 1200;',
    menuId,
    init() {
        this.menuId = menuId || this.$id('spm-action-menu');
    },
    get resolvedMenuId() {
        return this.menuId;
    },
    destroy() {
        this.cleanupOpenState();
    },
    close() {
        this.isOpen = false;
        this.placementStyle = 'position: fixed; top: 0; left: 0; visibility: hidden; z-index: 1200;';
        this.cleanupOpenState();
    },
    markOpenState() {
        this.$el.classList.add('is-open');
        this.$el.closest('td')?.classList.add('spm-action-cell-open');
        this.$el.closest('tr')?.classList.add('spm-action-row-open');
    },
    cleanupOpenState() {
        this.$el.classList.remove('is-open');
        this.$el.closest('td')?.classList.remove('spm-action-cell-open');
        this.$el.closest('tr')?.classList.remove('spm-action-row-open');
    },
    updatePosition() {
        const trigger = this.$refs.trigger;
        const menu = this.$refs.menu;

        if (!trigger || !menu) return;

        const rect = trigger.getBoundingClientRect();
        const computedWidth = Number.parseFloat(window.getComputedStyle(menu).width);
        const width = Math.min(Math.max(menu.offsetWidth || 0, computedWidth || 0, 210), window.innerWidth - 32);
        const height = Math.min(menu.scrollHeight || menu.offsetHeight || 240, window.innerHeight - 32);
        const spaceBelow = window.innerHeight - rect.bottom - 16;
        const openUp = height > spaceBelow && rect.top > spaceBelow;
        const top = openUp
            ? Math.max(16, rect.top - height - 8)
            : Math.min(rect.bottom + 8, window.innerHeight - height - 16);
        const left = Math.max(16, Math.min(rect.right - width, window.innerWidth - width - 16));

        this.placementStyle = `position: fixed; top: ${top}px; left: ${left}px; width: ${width}px; visibility: visible; z-index: 1200;`;
    },
    toggle() {
        if (this.isOpen) {
            this.close();
            return;
        }

        window.dispatchEvent(new CustomEvent('spm:action-menu-open', { detail: { id: this.menuId } }));
        this.placementStyle = 'position: fixed; top: 0; left: 0; width: 210px; visibility: hidden; z-index: 1200;';
        this.isOpen = true;
        this.markOpenState();
        this.$nextTick(() => window.requestAnimationFrame(() => this.updatePosition()));
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
            title: 'Simpan draf penilaian?',
            text: 'Nilai yang sudah diisi akan disimpan sebagai draf.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAsesorEdpm');
        });
    },
    confirmVerification(wire) {
        ask({
            title: 'Finalisasi penilaian pasca visitasi?',
            text: 'Pastikan Nilai Ketua, Nilai Anggota, Nilai Kelompok, laporan visitasi, dan kartu kendali sudah lengkap.',
            icon: 'success',
            confirmVariant: 'success',
            confirmButtonText: 'Ya, finalisasi',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'finalizeScoring');
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
    confirmSaveInstrumen(wire) {
        ask({
            title: 'Simpan penilaian instrumen?',
            text: 'Nilai Ketua, Nilai Anggota, atau Nilai Kelompok yang sudah terbuka akan disimpan.',
            confirmButtonText: 'Ya, simpan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveAsesorEdpm');
        });
    },
    confirmTerimaPerbaikan(wire) {
        ask({
            title: 'Terima perbaikan?',
            text: 'Perbaikan dokumen dari pesantren akan diterima dan proses visitasi dapat dilanjutkan.',
            icon: 'success',
            confirmVariant: 'success',
            confirmButtonText: 'Ya, terima',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'acceptPerbaikan');
        });
    },
    confirmKirimPenolakan(wire) {
        ask({
            title: 'Kirim penolakan dokumen?',
            text: 'Pesantren akan menerima notifikasi dan diminta memperbaiki dokumen yang ditolak.',
            icon: 'warning',
            confirmVariant: 'danger',
            confirmButtonText: 'Ya, kirim penolakan',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'submitRejection');
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
    confirmSaveDraft(wire) {
        ask({
            title: 'Submit draft profil?',
            text: 'Data yang sudah diisi akan disimpan sebagai draft dan masih bisa dilengkapi nanti.',
            confirmButtonText: 'Ya, simpan draft',
        }).then((result) => {
            if (result.isConfirmed) callWire(wire, 'saveDraft');
        });
    },
    confirmSubmitProfile(wire) {
        ask({
            title: 'Submit profil pesantren?',
            text: 'Pastikan data inti profil sudah lengkap dan benar.',
            icon: 'warning',
            confirmButtonText: 'Ya, submit',
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
    showProvinsiDropdown: false,
    showKabupatenDropdown: false,
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
        const resolvedProvinsiKode = this.currentProvinsiKode;
        if (!this.selectedProvinsiKode && resolvedProvinsiKode) {
            this.selectedProvinsiKode = resolvedProvinsiKode;
        }
        if (resolvedProvinsiKode) this.loadKabupaten(resolvedProvinsiKode);
    },
    selectProvinsi(item) {
        this.selectedProvinsiKode = item.kode;
        this.selectedProvinsiNama = item.nama;
        this.provinsiSearch = item.nama;
        this.selectedKabupatenKode = '';
        this.selectedKabupatenNama = '';
        this.kabupatenSearch = '';
        this.showProvinsiConfig = false;
        this.showProvinsiDropdown = false;
        this.loadKabupaten(item.kode);
    },
    selectKabupaten(item) {
        this.selectedKabupatenKode = item.kode;
        this.selectedKabupatenNama = item.nama;
        this.kabupatenSearch = item.nama;
        this.showKabupatenConfig = false;
        this.showKabupatenDropdown = false;
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
            if (!this.selectedKabupatenKode && this.selectedKabupatenNama) {
                const matchedKabupaten = this.kabupatenList.find((item) => item.nama === this.selectedKabupatenNama);
                if (matchedKabupaten) this.selectedKabupatenKode = matchedKabupaten.kode;
            }
        } catch (error) {
            this.kabupatenList = [];
        }
    },
});

window.dashboardCharts = (chartData = [], stats = {}) => ({
    init() {
        this.$nextTick(() => {
            if (!window.Chart) return;

            const brandPrimary = '#005533';
            const brandPrimaryHover = '#006b40';
            const brandSuccess = '#10b981';
            const brandDanger = '#ef4444';

            const monthly = document.getElementById('monthlyChart');
            if (monthly) {
                const ctx = monthly.getContext('2d');
                const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(0, 85, 51, 0.25)');
                gradient.addColorStop(1, 'rgba(0, 85, 51, 0.02)');

                new window.Chart(monthly, {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                        datasets: [{
                            label: 'Pengajuan',
                            data: chartData,
                            borderColor: brandPrimary,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#ffffff',
                            pointBorderColor: brandPrimary,
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: brandPrimary,
                            pointHoverBorderColor: '#ffffff',
                            pointHoverBorderWidth: 3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: brandPrimary,
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                padding: 12,
                                cornerRadius: 8,
                                displayColors: false,
                                titleFont: { weight: 'bold', size: 13 },
                                bodyFont: { size: 12 },
                                callbacks: {
                                    label: (ctx) => ` ${ctx.parsed.y} pengajuan`,
                                },
                            },
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: '#7e8299', font: { size: 11, weight: '600' } },
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0,0,0,0.05)', drawBorder: false },
                                ticks: { color: '#7e8299', font: { size: 11 }, stepSize: 1, precision: 0 },
                            },
                        },
                    },
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
                            backgroundColor: [brandSuccess, brandDanger],
                            hoverBackgroundColor: ['#0ea372', '#dc3545'],
                            borderWidth: 4,
                            borderColor: '#ffffff',
                            hoverOffset: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '72%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: brandPrimary,
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                padding: 12,
                                cornerRadius: 8,
                                titleFont: { weight: 'bold', size: 13 },
                                bodyFont: { size: 12 },
                            },
                        },
                    },
                });
            }
        });
    },
});

window.addEventListener('show-validation-alert', (event) => {
    fireNotificationAlert({
        type: 'warning',
        title: event.detail.title ?? 'Validasi gagal',
        html: event.detail.html ?? event.detail.message ?? '',
    });
});

window.addEventListener('validation-failed', (event) => {
    fireNotificationAlert({
        type: 'warning',
        title: event.detail.title ?? 'Validasi gagal',
        html: event.detail.html ?? event.detail.message ?? '',
    });
});

window.addEventListener('show-validation-error', () => {
    fireNotificationAlert({
        type: 'warning',
        title: 'Data belum lengkap',
        html: 'Mohon periksa kembali isian yang ditandai pada formulir.',
    });
});

window.addEventListener('show-metronic-alert', (event) => {
    fireNotificationAlert(event);
});

window.addEventListener('notification-received', (event) => {
    fireNotificationAlert(event);
});

document.addEventListener('DOMContentLoaded', () => {
    if (!window.__spmFlashAlert) return;

    fireNotificationAlert(window.__spmFlashAlert);
    window.__spmFlashAlert = null;
});

document.addEventListener('livewire:initialized', () => {
    Livewire.hook?.('commit', ({ succeed, fail }) => {
        closeOpenActionMenus();
        succeed?.(() => releaseAllSubmitLockGuards());
        fail?.(() => releaseAllSubmitLockGuards());
    });

    Livewire.on('swal:success', (data) => {
        fireNotificationAlert({ ...normalizeAlertPayload(data), type: 'success' });
    });

    Livewire.on('swal:error', (data) => {
        fireNotificationAlert({ ...normalizeAlertPayload(data), type: 'error' });
    });
});

initSubmitLockGuard();
document.addEventListener('DOMContentLoaded', initMetronic);
document.addEventListener('DOMContentLoaded', hidePageLoadingOverlay);
document.addEventListener('DOMContentLoaded', releaseAllSubmitLockGuards);
document.addEventListener('livewire:initialized', initMetronic);
document.addEventListener('livewire:navigate', showPageLoadingOverlay);
document.addEventListener('livewire:navigating', showPageLoadingOverlay);
document.addEventListener('livewire:navigated', () => {
    initMetronic();
    releaseAllSubmitLockGuards();
    hidePageLoadingOverlay();
});
window.addEventListener('pageshow', () => {
    releaseAllSubmitLockGuards();
    hidePageLoadingOverlay();
});

Alpine.store('sidebar', { open: false });
Alpine.data('deleteConfirmation', window.deleteConfirmation);
Alpine.data('adminManagement', window.adminManagement);
Alpine.data('akreditasiPesantren', window.akreditasiPesantren);
Alpine.data('spmActionMenu', window.spmActionMenu);
Alpine.data('edpmManagement', window.edpmManagement);
Alpine.data('ipmManagement', window.ipmManagement);
Alpine.data('sdmManagement', window.sdmManagement);
Alpine.data('fileManagement', window.fileManagement);
Alpine.data('asesorManagement', window.asesorManagement);

// ── Dropzone File Upload Bridge ──
// Bridges Dropzone drag-drop to Livewire's wire:model on hidden <input type="file">.
// Uses Dropzone as visual shell only; uploads handled by Livewire.
Alpine.data('fileDropzone', function (config = {}) {
    // Store element reference for Dropzone init
    const el = this.$el;

    const dropzoneConfig = {
        url: '/dev/null',
        autoProcessQueue: false,
        autoQueue: true,
        uploadMultiple: false,
        parallelUploads: 1,
        maxFiles: 1,
        maxFilesize: config.maxMb ?? 5,
        acceptedFiles: config.allowedTypes ?? '.pdf,.jpg,.jpeg,.png,.docx,.doc,.xlsx,.xls',
        addRemoveLinks: config.showRemove ?? true,
        createImageThumbnails: false,
        previewsContainer: false,
        clickable: true,
        dictDefaultMessage: '',
        dictFileTooBig: 'File terlalu besar (maks {{maxFilesize}}MB)',
        dictInvalidFileType: 'Format file tidak didukung',
        dictRemoveFile: config.removeText ?? 'Hapus',
        dictMaxFilesExceeded: 'Maksimal 1 file',
        init() {
            this.on('addedfile', (file) => {
                // Remove previous file (max 1)
                if (this.files.length > 1) {
                    this.removeFile(this.files[0]);
                }

                // Bridge to hidden input → Livewire wire:model
                const input = document.getElementById(config.inputId);
                if (input) {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    input.files = dt.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });

            this.on('removedfile', () => {
                const input = document.getElementById(config.inputId);
                if (input) {
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        },
    };

    return {
        dropzone: null,

        init() {
            // Idempotent: Dropzone.instances auto-populated, check before creating
            if (Dropzone.instances.some(dz => dz.element === el)) return;
            this.dropzone = new Dropzone(el, dropzoneConfig);
        },

        destroy() {
            this.dropzone?.destroy();
            this.dropzone = null;
        },
    };
});

// Init Metronic components after initial load and each Livewire DOM morph
document.addEventListener('DOMContentLoaded', () => initMetronic());
Livewire.hook('morph.updated', ({ component }) => { initMetronic(); });

Livewire.start();
