import { createMemberPhotoHandlers } from './member-photo';

document.addEventListener('alpine:init', () => {
    Alpine.data('memberRegistration', (config) => ({
        ...createMemberPhotoHandlers(),
        plans: config.plans,
        selectedPlanId: config.selectedPlanId ? String(config.selectedPlanId) : '',
        amountReceived: config.amountReceived ?? '',
        currencySymbol: config.currencySymbol,

        init() {
            this.syncAmount();
        },

        get selectedPlan() {
            return this.plans.find((plan) => String(plan.id) === String(this.selectedPlanId)) ?? null;
        },

        get totalDue() {
            if (! this.selectedPlan) {
                return 0;
            }

            return this.selectedPlan.admission_fee + this.selectedPlan.membership_fee;
        },

        get expiryLabel() {
            if (! this.selectedPlan) {
                return '';
            }

            return `${this.selectedPlan.duration_days} days`;
        },

        syncAmount() {
            if (this.selectedPlan) {
                this.amountReceived = this.totalDue.toFixed(2);
            }
        },

        formatMoney(amount) {
            return this.currencySymbol + Number(amount).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    }));
});
