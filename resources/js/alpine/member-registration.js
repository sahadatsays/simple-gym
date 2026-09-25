import { createMemberPhotoHandlers } from './member-photo';

document.addEventListener('alpine:init', () => {
    Alpine.data('memberRegistration', (config) => ({
        ...createMemberPhotoHandlers(),
        plans: config.plans,
        selectedPlanId: config.selectedPlanId ? String(config.selectedPlanId) : '',
        discountAmount: config.discountAmount ?? 0,
        amountReceived: config.amountReceived ?? '',
        currencySymbol: config.currencySymbol,

        init() {
            this.$nextTick(() => this.syncAmount());
        },

        get selectedPlan() {
            return this.plans.find((plan) => String(plan.id) === String(this.selectedPlanId)) ?? null;
        },

        get subtotal() {
            if (! this.selectedPlan) {
                return 0;
            }

            return Number(this.selectedPlan.admission_fee) + Number(this.selectedPlan.membership_fee);
        },

        get normalizedDiscount() {
            const discount = Number(this.discountAmount || 0);

            if (discount <= 0) {
                return 0;
            }

            return Math.min(discount, this.subtotal);
        },

        get totalDue() {
            return Math.max(0, this.subtotal - this.normalizedDiscount);
        },

        get expiryLabel() {
            if (! this.selectedPlan) {
                return '';
            }

            return `${this.selectedPlan.duration_days} days`;
        },

        syncAmount() {
            if (this.$refs.chargeSummary) {
                this.$refs.chargeSummary.classList.toggle('d-none', ! this.selectedPlan);
            }

            if (! this.selectedPlan) {
                return;
            }

            if (Number(this.discountAmount || 0) > this.subtotal) {
                this.discountAmount = this.subtotal.toFixed(2);
            }

            this.amountReceived = this.totalDue.toFixed(2);
        },

        formatMoney(amount) {
            return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    }));
});
