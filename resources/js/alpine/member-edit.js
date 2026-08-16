import { createMemberPhotoHandlers } from './member-photo';

document.addEventListener('alpine:init', () => {
    Alpine.data('memberEdit', (config) => ({
        ...createMemberPhotoHandlers(),

        init() {
            if (config.initialPhotoUrl) {
                this.photoPreview = config.initialPhotoUrl;
            }
        },
    }));
});
