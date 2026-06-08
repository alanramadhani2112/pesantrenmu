/**
 * Alpine.js client-side form validation — supplements Laravel server-side validation.
 *
 * Usage:
 *   <form x-data="formValidation" method="POST">
 *     <div class="fv-row" data-validate="required|email">
 *       <input type="email" name="email" ...>
 *     </div>
 *   </form>
 *
 * Rules: required, email, min:N, same:fieldId
 */
const RULES = {
    required: (val) => (val ?? '').toString().trim().length > 0,
    email: (val) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val ?? ''),
};

const MESSAGES = {
    required: 'Wajib diisi.',
    email: 'Format email tidak valid.',
    min: (n) => `Minimal ${n} karakter.`,
    same: 'Nilai tidak cocok.',
};

export default () => ({
    /** Validate a single fv-row field. */
    validateField(row) {
        const input = row.querySelector('input, select, textarea');
        if (!input) return true;

        const rules = (row.dataset.validate || '').split('|').filter(Boolean);
        const val = input.value;
        let valid = true;

        row.classList.add('was-validated');

        for (const rule of rules) {
            if (rule === 'required' && !RULES.required(val)) {
                valid = false; break;
            }
            if (rule === 'email' && val.trim() && !RULES.email(val)) {
                valid = false; break;
            }
            if (rule.startsWith('min:') && val.trim()) {
                const n = parseInt(rule.slice(4), 10);
                if (val.length < n) { valid = false; break; }
            }
            if (rule.startsWith('same:')) {
                const targetId = rule.slice(5);
                const target = document.getElementById(targetId);
                if (target && val !== target.value) { valid = false; break; }
            }
        }

        row.classList.toggle('is-valid', valid && rules.length > 0);
        row.classList.toggle('is-invalid', !valid);

        // Update error message
        let fb = row.querySelector('.fv-feedback');
        if (!valid && rules.length) {
            if (!fb) {
                fb = document.createElement('div');
                fb.className = 'invalid-feedback fv-feedback fw-semibold';
                row.appendChild(fb);
            }
            fb.textContent = this.getErrorMessage(rules, val);
        } else if (fb) {
            fb.remove();
        }

        // Toggle is-invalid on the input itself for Bootstrap consistency
        if (input.classList.contains('form-control')) {
            input.classList.toggle('is-invalid', !valid);
            input.classList.toggle('is-valid', valid && rules.length > 0);
        }

        return valid;
    },

    getErrorMessage(rules, val) {
        for (const rule of rules) {
            if (rule === 'required' && !RULES.required(val)) return MESSAGES.required;
            if (rule === 'email' && val.trim() && !RULES.email(val)) return MESSAGES.email;
            if (rule.startsWith('min:') && val.trim()) {
                const n = parseInt(rule.slice(4), 10);
                if (val.length < n) return MESSAGES.min(n);
            }
            if (rule.startsWith('same:')) return MESSAGES.same;
        }
        return 'Tidak valid.';
    },

    /** Validate all rows on the form. Returns true if all pass. */
    validateAll() {
        const rows = this.$el.querySelectorAll('.fv-row[data-validate]');
        let allValid = true;
        rows.forEach((row) => {
            if (!this.validateField(row)) allValid = false;
        });
        return allValid;
    },

    /** Handle blur — validate field on first blur. */
    onBlur(event) {
        const row = event.target.closest('.fv-row');
        if (row && row.dataset.validate) {
            this.validateField(row);
        }
    },

    /** Handle input — re-validate only if field was already validated. */
    onInput(event) {
        const row = event.target.closest('.fv-row');
        if (row && row.classList.contains('was-validated')) {
            this.validateField(row);
        }
    },
});
