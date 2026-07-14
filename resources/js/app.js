import Dropzone from 'dropzone';
import axios from 'axios';
import autosize from 'autosize';
import { createPopper } from '@popperjs/core';
import Chart from 'chart.js/auto';
import formValidation from './validation';
import Alpine from 'alpinejs';

window.Dropzone = Dropzone;
window.autosize = autosize;
window.Popper = { createPopper };
window.Chart = window.Chart ?? Chart;

Alpine.data('formValidation', formValidation);

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.Alpine = Alpine;

const initAutosize = () => autosize(document.querySelectorAll('[data-kt-autosize="true"]'));

const ktMenuFallbackInstances = new WeakMap();

const closeKtMenuFallback = (trigger) => {
    const instance = ktMenuFallbackInstances.get(trigger);
    if (!instance) return;

    instance.popper?.destroy?.();
    instance.menu.classList.remove('show');
    instance.menu.removeAttribute('data-popper-placement');
    instance.host.classList.remove('show', 'menu-dropdown');
    ktMenuFallbackInstances.delete(trigger);
};

const closeAllKtMenuFallbacks = (except = null) => {
    document.querySelectorAll('[data-kt-menu-trigger]').forEach((trigger) => {
        if (trigger !== except) closeKtMenuFallback(trigger);
    });
};

const findKtMenuFallback = (trigger) => {
    const host = trigger.closest('.app-navbar-item, .menu-item, [data-kt-menu-host]') ?? trigger.parentElement;
    const menu = host?.querySelector(':scope > [data-kt-menu="true"], :scope > .menu-sub-dropdown');

    if (!host || !menu) return null;

    return { host, menu };
};

const openKtMenuFallback = (trigger) => {
    const target = findKtMenuFallback(trigger);
    if (!target) return;

    closeAllKtMenuFallbacks(trigger);

    const { host, menu } = target;
    host.classList.add('show', 'menu-dropdown');
    menu.classList.add('show');

    const placement = trigger.dataset.ktMenuPlacement === 'bottom-end' ? 'bottom-end' : 'bottom-start';
    const popper = createPopper(trigger, menu, {
        placement,
        modifiers: [
            { name: 'offset', options: { offset: [0, 8] } },
            { name: 'preventOverflow', options: { padding: 12 } },
        ],
    });

    ktMenuFallbackInstances.set(trigger, { host, menu, popper });
};

const initKtMenuFallback = () => {
    document.querySelectorAll('[data-kt-menu-trigger]').forEach((trigger) => {
        if (trigger.dataset.spmKtMenuFallback === 'true') return;

        trigger.dataset.spmKtMenuFallback = 'true';
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (ktMenuFallbackInstances.has(trigger)) {
                closeKtMenuFallback(trigger);
                return;
            }

            openKtMenuFallback(trigger);
        });
    });
};

document.addEventListener('click', () => closeAllKtMenuFallbacks());
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeAllKtMenuFallbacks();
});

class KtPasswordMeterFallback {
    static instances = new WeakMap();

    static getInstance(element) {
        return KtPasswordMeterFallback.instances.get(element);
    }

    constructor(element, options = {}) {
        this.element = element;
        this.options = { minLength: 8, ...options };
        this.input = element.querySelector('input[type="password"], input[type="text"]');
        this.highlights = Array.from(element.querySelectorAll('[data-kt-password-meter-control="highlight"] > *'));
        this.visibility = element.querySelector('[data-kt-password-meter-control="visibility"]');
        this.onInput = () => this.update();
        this.onVisibility = () => this.toggleVisibility();

        this.input?.addEventListener('input', this.onInput);
        this.visibility?.addEventListener('click', this.onVisibility);
        KtPasswordMeterFallback.instances.set(element, this);
        this.update();
    }

    destroy() {
        this.input?.removeEventListener('input', this.onInput);
        this.visibility?.removeEventListener('click', this.onVisibility);
        KtPasswordMeterFallback.instances.delete(this.element);
    }

    toggleVisibility() {
        if (!this.input) return;

        this.input.type = this.input.type === 'password' ? 'text' : 'password';
    }

    update() {
        const value = this.input?.value ?? '';
        const checks = [
            value.length >= this.options.minLength,
            /[a-z]/.test(value) && /[A-Z]/.test(value),
            /\d/.test(value),
            /[^A-Za-z0-9]/.test(value),
        ];
        const strength = checks.filter(Boolean).length;

        this.highlights.forEach((bar, index) => {
            bar.classList.toggle('active', index < strength);
            bar.classList.toggle('bg-success', index < strength);
            bar.classList.toggle('bg-secondary', index >= strength);
        });
    }
}

window.KTMenu = window.KTMenu ?? { init: initKtMenuFallback };
window.KTDrawer = window.KTDrawer ?? { init: () => {} };
window.KTScroll = window.KTScroll ?? { init: () => {} };
window.KTSticky = window.KTSticky ?? { init: () => {} };
window.KTComponents = window.KTComponents ?? { init: initKtMenuFallback };
window.KTPasswordMeter = window.KTPasswordMeter ?? KtPasswordMeterFallback;

const initMetronic = () => {
    requestAnimationFrame(() => {
        initAutosize();
        window.KTComponents?.init?.();
        window.KTMenu?.init?.();
        window.KTDrawer?.init?.();
        window.KTScroll?.init?.();
        window.KTSticky?.init?.();
    });
};

window.initMetronic = initMetronic;

// Tab Throbber — favicon spinner during navigation
const tabThrobber = (() => {
    const spinnerSvg = 'data:image/svg+xml,' + encodeURIComponent(
        '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">' +
        '<circle cx="8" cy="8" r="6" fill="none" stroke="#999" stroke-width="2" ' +
        'stroke-dasharray="28" stroke-dashoffset="8" stroke-linecap="round">' +
        '<animateTransform attributeName="transform" type="rotate" ' +
        'from="0 8 8" to="360 8 8" dur="0.75s" repeatCount="indefinite"/>' +
        '</circle></svg>'
    );
    let originalHref = null;

    return {
        start() {
            const el = document.querySelector('link[rel="icon"]');
            if (!el) return;
            if (!originalHref) originalHref = el.getAttribute('href');
            el.setAttribute('href', spinnerSvg);
        },
        stop() {
            const el = document.querySelector('link[rel="icon"]');
            if (!el || !originalHref) return;
            el.setAttribute('href', originalHref);
        },
    };
})();

const showPageLoadingOverlay = () => { document.body.classList.add('spm-is-navigating'); tabThrobber.start(); };
const hidePageLoadingOverlay = () => { document.body.classList.remove('spm-is-navigating'); tabThrobber.stop(); };

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
    alert: (title, text, options = {}) => fireMetronicSwal({
        icon: options.icon ?? 'info',
        title,
        text,
        html: options.html,
        showConfirmButton: options.showConfirmButton ?? true,
        confirmVariant: options.confirmVariant ?? confirmVariantForAlert(normalizeSwalIcon(options.icon ?? 'info')),
        ...options,
    }),
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

window.spmActionMenu = (menuId) => ({
    isOpen: false,
    placementStyle: 'position: fixed; top: 0; left: 0; visibility: hidden; z-index: 1200;',
    menuId,
    resolvedMenuId: null,
    init() {
        this.resolvedMenuId = menuId || this.$id('spm-action-menu');
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

        window.dispatchEvent(new CustomEvent('spm:action-menu-open', { detail: { id: this.resolvedMenuId } }));
        this.placementStyle = 'position: fixed; top: 0; left: 0; width: 210px; visibility: hidden; z-index: 1200;';
        this.isOpen = true;
        this.markOpenState();
        this.$nextTick(() => window.requestAnimationFrame(() => this.updatePosition()));
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

window.addEventListener('spm:success', (event) => {
    fireNotificationAlert({ ...normalizeAlertPayload(event.detail), type: 'success' });
});

window.addEventListener('spm:error', (event) => {
    fireNotificationAlert({ ...normalizeAlertPayload(event.detail), type: 'error' });
});

initSubmitLockGuard();
document.addEventListener('DOMContentLoaded', initMetronic);
document.addEventListener('DOMContentLoaded', hidePageLoadingOverlay);
document.addEventListener('DOMContentLoaded', releaseAllSubmitLockGuards);
window.addEventListener('pageshow', () => {
    releaseAllSubmitLockGuards();
    hidePageLoadingOverlay();
});

Alpine.store('sidebar', { open: false });
Alpine.data('spmActionMenu', window.spmActionMenu);

// ── Dropzone File Upload Bridge ──
// Bridges Dropzone drag-drop to a hidden <input type="file">.
// Uses Dropzone as the visual shell; uploads are handled by standard form submission.
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

                // Bridge to hidden input for standard form submission
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

Alpine.start();
