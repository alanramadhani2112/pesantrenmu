@props(['label'])

<div class="menu-item menu-section spm-sidebar-section" data-ui-sidebar-section="metronic">
    <div class="menu-content">
        <span class="menu-heading fw-semibold text-uppercase fs-7 text-muted">{{ $label }}</span>
    </div>
</div>

{{ $slot }}
