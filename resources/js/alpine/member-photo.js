const MAX_DIMENSION = 1200;
const JPEG_QUALITY = 0.85;
const MAX_FILE_SIZE = 10 * 1024 * 1024;
const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

export function createMemberPhotoHandlers() {
    return {
        photoPreview: null,
        photoProcessing: false,
        photoError: null,

        async handlePhotoChange(event) {
            const input = event.target;
            const file = input.files?.[0];

            if (! file) {
                this.clearPhoto(input);

                return;
            }

            this.photoProcessing = true;
            this.photoError = null;

            try {
                if (! ALLOWED_TYPES.includes(file.type)) {
                    throw new Error('Please choose a JPG, PNG, or WebP image.');
                }

                if (file.size > MAX_FILE_SIZE) {
                    throw new Error('Image is too large. Please choose a file under 10 MB.');
                }

                const optimized = await this.optimizeImage(file);
                this.photoPreview = optimized.previewUrl;
                this.replaceFileInput(input, optimized.file);
            } catch (error) {
                this.photoError = error instanceof Error ? error.message : 'Unable to process the selected image.';
                this.clearPhoto(input);
            } finally {
                this.photoProcessing = false;
            }
        },

        clearPhoto(input) {
            if (this.photoPreview) {
                URL.revokeObjectURL(this.photoPreview);
            }

            this.photoPreview = null;

            if (input) {
                input.value = '';
            }
        },

        optimizeImage(file) {
            return new Promise((resolve, reject) => {
                const objectUrl = URL.createObjectURL(file);
                const image = new Image();

                image.onload = () => {
                    URL.revokeObjectURL(objectUrl);

                    const scale = Math.min(1, MAX_DIMENSION / Math.max(image.width, image.height));
                    const width = Math.max(1, Math.round(image.width * scale));
                    const height = Math.max(1, Math.round(image.height * scale));
                    const canvas = document.createElement('canvas');

                    canvas.width = width;
                    canvas.height = height;

                    const context = canvas.getContext('2d');

                    if (! context) {
                        reject(new Error('Unable to prepare the image preview.'));

                        return;
                    }

                    context.drawImage(image, 0, 0, width, height);

                    canvas.toBlob(
                        (blob) => {
                            if (! blob) {
                                reject(new Error('Unable to optimize the selected image.'));

                                return;
                            }

                            const optimizedFile = new File(
                                [blob],
                                this.buildOptimizedFileName(file.name),
                                { type: 'image/jpeg', lastModified: Date.now() },
                            );

                            resolve({
                                file: optimizedFile,
                                previewUrl: URL.createObjectURL(optimizedFile),
                            });
                        },
                        'image/jpeg',
                        JPEG_QUALITY,
                    );
                };

                image.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('Unable to read the selected image.'));
                };

                image.src = objectUrl;
            });
        },

        replaceFileInput(input, file) {
            const dataTransfer = new DataTransfer();

            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
        },

        buildOptimizedFileName(originalName) {
            const baseName = originalName.replace(/\.[^.]+$/, '') || 'member-photo';

            return `${baseName}.jpg`;
        },
    };
}
