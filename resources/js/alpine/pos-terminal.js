const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

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
        paymentMethod: 'cash',
        paymentReference: '',
        notes: '',
        isSearching: false,
        isScanning: false,
        isSubmitting: false,
        cartError: null,
        scanError: null,
        searchDebounce: null,

        init() {
            this.$nextTick(() => {
                this.$refs.barcodeInput?.focus();
            });
        },

        get subtotal() {
            return this.cart.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
        },

        get totalDue() {
            return Math.max(0, this.subtotal - Number(this.discountAmount || 0));
        },

        get cartCount() {
            return this.cart.reduce((count, item) => count + item.quantity, 0);
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
            this.cartError = null;
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

            this.isSubmitting = true;
            this.$refs.checkoutForm.submit();
        },
    }));
});
