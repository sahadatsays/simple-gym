document.addEventListener('alpine:init', () => {
    Alpine.data('searchableSelect', (config) => ({
        name: config.name,
        options: config.options ?? [],
        selected: config.selected ? String(config.selected) : '',
        search: '',
        open: false,
        selecting: false,
        menuStyle: '',
        placeholder: config.placeholder ?? 'Select an option',
        required: config.required ?? false,

        init() {
            if (this.selected) {
                this.search = this.selectedLabel;
            }

            this.handleReposition = () => {
                if (this.open) {
                    this.updateMenuPosition();
                }
            };

            window.addEventListener('scroll', this.handleReposition, true);
            window.addEventListener('resize', this.handleReposition);

            const form = this.$el.closest('form');

            if (form && this.required) {
                form.addEventListener('submit', (event) => {
                    if (this.selected) {
                        return;
                    }

                    event.preventDefault();
                    this.$refs.input?.classList.add('is-invalid');
                    this.$refs.input?.focus();
                });
            }
        },

        destroy() {
            window.removeEventListener('scroll', this.handleReposition, true);
            window.removeEventListener('resize', this.handleReposition);
        },

        get selectedLabel() {
            const option = this.options.find((item) => String(item.value) === String(this.selected));

            return option?.label ?? '';
        },

        get filteredOptions() {
            const query = this.search.trim().toLowerCase();

            if (query === '' || (this.selected && this.search === this.selectedLabel)) {
                return this.options;
            }

            return this.options.filter((option) => option.label.toLowerCase().includes(query));
        },

        updateMenuPosition() {
            if (! this.$refs.input) {
                return;
            }

            const rect = this.$refs.input.getBoundingClientRect();

            this.menuStyle = [
                'position: fixed',
                `top: ${rect.bottom + 4}px`,
                `left: ${rect.left}px`,
                `width: ${rect.width}px`,
                'z-index: 2000',
            ].join('; ');
        },

        openMenu() {
            this.open = true;

            this.$nextTick(() => {
                this.updateMenuPosition();
            });

            if (this.selected && this.search === this.selectedLabel) {
                this.search = '';
            }
        },

        closeMenu() {
            this.open = false;

            if (this.selected) {
                this.search = this.selectedLabel;
            } else {
                this.search = '';
            }
        },

        handleBlur() {
            setTimeout(() => {
                if (this.selecting) {
                    this.selecting = false;

                    return;
                }

                this.closeMenu();
            }, 150);
        },

        selectOption(option) {
            this.selecting = true;
            this.selected = String(option.value);
            this.search = option.label;
            this.open = false;
            this.$refs.input?.classList.remove('is-invalid');
        },
    }));
});
