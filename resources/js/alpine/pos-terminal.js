import { initDatePicker } from '../pickers';

const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const POS_STORAGE_KEY = 'sg-pos-terminal-state';

document.addEventListener('alpine:init', () => {
    Alpine.data('posTerminal', (config) => ({
        products: config.initialProducts ?? [],
        categories: config.categories ?? [],
        members: config.members ?? [],
        currencySymbol: config.currencySymbol ?? '$',
        searchRoutes: config.routes,

        searchQuery: '',
        selectedCategory: '',
        barcodeInput: '',
        cart: [],
        memberId: '',
        discountAmount: 0,
        amountPaid: 0,
        amountPaidLocked: false,
        dueAt: '',
        paymentMethod: 'cash',
        paymentReference: '',
        notes: '',
        isSearching: false,
        isScanning: false,
        isSubmitting: false,
        cartError: null,
        scanError: null,
        searchDebounce: null,
        saveDebounce: null,

        init() {
            this.restoreState();
            this.syncPayAmount();

            this.$nextTick(() => {
                this.$refs.barcodeInput?.focus();

                if (this.searchQuery || this.selectedCategory) {
                    this.fetchProducts();
                }

                if (this.paymentStatus !== 'full') {
                    this.initDueDatePicker();
                }
            });

            this.$watch('paymentStatus', (status) => {
                if (status === 'full') {
                    return;
                }

                if (! this.dueAt) {
                    this.dueAt = this.defaultDueDate();
                }

                this.initDueDatePicker();
            });

            [
                'cart',
                'memberId',
                'discountAmount',
                'amountPaid',
                'amountPaidLocked',
                'dueAt',
                'paymentMethod',
                'paymentReference',
                'notes',
                'searchQuery',
                'selectedCategory',
            ].forEach((key) => {
                this.$watch(key, () => this.persistState());
            });

            this.$watch('totalDue', () => this.syncPayAmount());
        },

        defaultDueDate() {
            const date = new Date();
            date.setDate(date.getDate() + 7);

            return date.toISOString().slice(0, 10);
        },

        initDueDatePicker() {
            this.$nextTick(() => {
                const element = this.$refs.dueDateInput;

                if (! element || this.paymentStatus === 'full') {
                    return;
                }

                if (element._flatpickr) {
                    if (this.dueAt) {
                        element._flatpickr.setDate(this.dueAt, false);
                    }

                    return;
                }

                initDatePicker(element, {
                    minDate: 'today',
                    defaultDate: this.dueAt || this.defaultDueDate(),
                    onChange: (_selectedDates, dateStr) => {
                        this.dueAt = dateStr;
                    },
                });

                if (! this.dueAt) {
                    this.dueAt = this.defaultDueDate();
                }
            });
        },

        syncDueDatePicker() {
            const element = this.$refs.dueDateInput;

            if (element?._flatpickr && this.dueAt) {
                element._flatpickr.setDate(this.dueAt, false);
            }
        },

        get subtotal() {
            return this.cart.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
        },

        get totalDue() {
            return Math.max(0, this.subtotal - Number(this.discountAmount || 0));
        },

        get payAmount() {
            return Number(this.amountPaid || 0);
        },

        get balanceAfterPayment() {
            return Math.max(0, this.totalDue - this.payAmount);
        },

        get paymentStatus() {
            if (this.payAmount <= 0) {
                return 'due';
            }

            if (this.payAmount >= this.totalDue) {
                return 'full';
            }

            return 'partial';
        },

        get paymentStatusLabel() {
            return {
                full: 'Full payment',
                partial: 'Partial payment',
                due: 'Full due',
            }[this.paymentStatus];
        },

        get cartCount() {
            return this.cart.reduce((count, item) => count + item.quantity, 0);
        },

        get submitLabel() {
            if (this.paymentStatus === 'due') {
                return 'Create Due Order';
            }

            if (this.paymentStatus === 'partial') {
                return 'Create Order & Record Payment';
            }

            return 'Complete Sale';
        },

        syncPayAmount() {
            if (this.amountPaidLocked) {
                if (this.payAmount > this.totalDue) {
                    this.amountPaid = this.totalDue;
                }

                return;
            }

            this.amountPaid = this.totalDue;
        },

        onPayAmountInput() {
            this.amountPaidLocked = true;
        },

        persistState() {
            clearTimeout(this.saveDebounce);

            this.saveDebounce = setTimeout(() => {
                localStorage.setItem(POS_STORAGE_KEY, JSON.stringify({
                    cart: this.cart,
                    memberId: this.memberId,
                    discountAmount: this.discountAmount,
                    amountPaid: this.amountPaid,
                    amountPaidLocked: this.amountPaidLocked,
                    dueAt: this.dueAt,
                    paymentMethod: this.paymentMethod,
                    paymentReference: this.paymentReference,
                    notes: this.notes,
                    searchQuery: this.searchQuery,
                    selectedCategory: this.selectedCategory,
                }));
            }, 150);
        },

        restoreState() {
            const raw = localStorage.getItem(POS_STORAGE_KEY);

            if (! raw) {
                this.dueAt = this.defaultDueDate();

                return;
            }

            try {
                const state = JSON.parse(raw);

                this.cart = Array.isArray(state.cart) ? state.cart : [];
                this.memberId = state.memberId ?? '';
                this.discountAmount = Number(state.discountAmount ?? 0);
                this.amountPaid = Number(state.amountPaid ?? 0);
                this.amountPaidLocked = Boolean(state.amountPaidLocked);
                this.dueAt = state.dueAt || this.defaultDueDate();
                this.paymentMethod = state.paymentMethod ?? 'cash';
                this.paymentReference = state.paymentReference ?? '';
                this.notes = state.notes ?? '';
                this.searchQuery = state.searchQuery ?? '';
                this.selectedCategory = state.selectedCategory ?? '';
                this.syncDueDatePicker();
            } catch (error) {
                localStorage.removeItem(POS_STORAGE_KEY);
                this.dueAt = this.defaultDueDate();
            }
        },

        clearStoredState() {
            localStorage.removeItem(POS_STORAGE_KEY);
        },

        scheduleSearch() {
            clearTimeout(this.searchDebounce);
            this.searchDebounce = setTimeout(() => this.fetchProducts(), 250);
        },

        async fetchProducts() {
            this.isSearching = true;

            try {
                const params = new URLSearchParams();

                if (this.searchQuery) {
                    params.set('search', this.searchQuery);
                }

                if (this.selectedCategory) {
                    params.set('category', this.selectedCategory);
                }

                const response = await fetch(`${this.searchRoutes.search}?${params.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (! response.ok) {
                    throw new Error('Unable to load products.');
                }

                const payload = await response.json();
                this.products = payload.data ?? [];
            } catch (error) {
                this.products = [];
            } finally {
                this.isSearching = false;
            }
        },

        async scanBarcode() {
            const code = this.barcodeInput.trim();

            if (! code) {
                return;
            }

            this.isScanning = true;
            this.scanError = null;

            try {
                const response = await fetch(this.searchRoutes.scan, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                    },
                    body: JSON.stringify({ code }),
                });

                const payload = await response.json();

                if (! response.ok) {
                    this.scanError = payload.message ?? 'Product not found.';
                    this.barcodeInput = '';

                    return;
                }

                this.addProduct(payload.data);
                this.barcodeInput = '';
                this.$refs.barcodeInput?.focus();
            } catch (error) {
                this.scanError = 'Unable to scan barcode.';
            } finally {
                this.isScanning = false;
            }
        },

        addProduct(product) {
            this.cartError = null;

            const existing = this.cart.find((item) => item.product_id === product.id);

            if (existing) {
                if (existing.quantity >= product.stock) {
                    this.cartError = `Only ${product.stock} units of ${product.name} available.`;

                    return;
                }

                existing.quantity += 1;
                existing.stock = product.stock;

                return;
            }

            this.cart.push({
                product_id: product.id,
                name: product.name,
                sku: product.sku,
                unit_price: Number(product.selling_price),
                stock: product.stock,
                quantity: 1,
            });
        },

        incrementItem(productId) {
            const item = this.cart.find((entry) => entry.product_id === productId);

            if (! item) {
                return;
            }

            if (item.quantity >= item.stock) {
                this.cartError = `Only ${item.stock} units of ${item.name} available.`;

                return;
            }

            this.cartError = null;
            item.quantity += 1;
        },

        decrementItem(productId) {
            const item = this.cart.find((entry) => entry.product_id === productId);

            if (! item) {
                return;
            }

            if (item.quantity <= 1) {
                this.removeItem(productId);

                return;
            }

            item.quantity -= 1;
            this.cartError = null;
        },

        removeItem(productId) {
            this.cart = this.cart.filter((item) => item.product_id !== productId);
            this.cartError = null;
        },

        clearCart() {
            this.cart = [];
            this.discountAmount = 0;
            this.paymentReference = '';
            this.notes = '';
            this.memberId = '';
            this.amountPaid = 0;
            this.amountPaidLocked = false;
            this.dueAt = this.defaultDueDate();
            this.syncDueDatePicker();
            this.cartError = null;
            this.clearStoredState();
        },

        formatMoney(amount) {
            return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        submitSale() {
            if (this.cart.length === 0) {
                this.cartError = 'Add at least one product to the cart.';

                return;
            }

            if (this.totalDue <= 0) {
                this.cartError = 'Total due must be greater than zero.';

                return;
            }

            if (this.paymentStatus !== 'full' && ! this.memberId) {
                this.cartError = 'Select a member when the pay amount is less than the billing total.';

                return;
            }

            if (this.payAmount > this.totalDue) {
                this.cartError = 'Pay amount cannot exceed the billing total.';

                return;
            }

            this.isSubmitting = true;
            this.clearStoredState();
            this.$refs.checkoutForm.submit();
        },
    }));
});
