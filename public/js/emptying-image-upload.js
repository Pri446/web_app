(function () {
    'use strict';

    var COMPRESSION_THRESHOLD = 1 * 1024 * 1024;
    var MAX_UPLOAD_BYTES = 5 * 1024 * 1024;
    var MAX_DIMENSION = 1600;
    var JPEG_QUALITY = 0.70;
    var processingInputs = new Set();

    function setFormBusy(input, busy) {
        var form = input.form;
        if (busy) {
            processingInputs.add(input);
        } else {
            processingInputs.delete(input);
        }

        if (!form) return;
        form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(function (button) {
            button.disabled = processingInputs.size > 0;
        });
    }

    function feedbackFor(input) {
        if (input.id === 'receipt_image') {
            return document.getElementById('receiptImageFeedback');
        }

        return document.getElementById('imageFileFeedback') ||
            document.getElementById('houseImageFeedback');
    }

    function previewFor(input) {
        return document.getElementById(
            input.id === 'receipt_image' ? 'receiptImagePreview' : 'houseImagePreview'
        );
    }

    function showFeedback(input, message, isError) {
        var feedback = feedbackFor(input);
        if (!feedback) return;
        feedback.textContent = message;
        feedback.className = 'file-feedback mt-1 ' + (isError ? 'text-danger' : 'text-success');
    }

    function formatBytes(bytes) {
        return bytes < 1024 * 1024
            ? (bytes / 1024).toFixed(1) + ' KB'
            : (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function resetPreview(input) {
        var preview = previewFor(input);
        if (!preview) return;
        if (preview._previewUrl) URL.revokeObjectURL(preview._previewUrl);
        preview._previewUrl = null;

        var defaultSrc = preview.getAttribute('data-default-src');
        if (defaultSrc) {
            preview.src = defaultSrc;
            preview.classList.remove('d-none');
        } else {
            preview.removeAttribute('src');
            preview.classList.add('d-none');
        }
    }

    function loadImage(file) {
        return new Promise(function (resolve, reject) {
            var url = URL.createObjectURL(file);
            var image = new Image();
            image.onload = function () {
                URL.revokeObjectURL(url);
                resolve(image);
            };
            image.onerror = function () {
                URL.revokeObjectURL(url);
                reject(new Error('The selected image could not be processed.'));
            };
            image.src = url;
        });
    }

    function canvasToBlob(canvas) {
        return new Promise(function (resolve) {
            canvas.toBlob(resolve, 'image/jpeg', JPEG_QUALITY);
        });
    }

    function replaceFile(input, file) {
        if (typeof DataTransfer === 'undefined') {
            throw new Error('This browser cannot optimize large images. Please use an updated browser.');
        }
        var transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;
    }

    function updatePreview(input, file) {
        var preview = previewFor(input);
        if (!preview) return;
        if (preview._previewUrl) URL.revokeObjectURL(preview._previewUrl);
        preview._previewUrl = URL.createObjectURL(file);
        preview.src = preview._previewUrl;
        preview.classList.remove('d-none');
    }

    async function optimizeSelectedImage(event) {
        var input = event.currentTarget;
        var file = input.files && input.files[0];
        if (!file) {
            resetPreview(input);
            return;
        }

        var extensionIsValid = /\.(jpe?g)$/i.test(file.name);
        var mimeIsValid = !file.type || file.type === 'image/jpeg' || file.type === 'image/jpg';
        if (!extensionIsValid || !mimeIsValid) {
            input.value = '';
            resetPreview(input);
            showFeedback(input, 'Only JPG or JPEG images are allowed.', true);
            return;
        }

        setFormBusy(input, true);

        try {
            var image = await loadImage(file);
            var shouldOptimize = file.size > COMPRESSION_THRESHOLD ||
                image.naturalWidth > MAX_DIMENSION || image.naturalHeight > MAX_DIMENSION;
            var uploadFile = file;

            if (shouldOptimize) {
                showFeedback(input, 'Optimizing image for upload…', false);
                var scale = Math.min(1, MAX_DIMENSION / Math.max(image.naturalWidth, image.naturalHeight));
                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

                var blob = await canvasToBlob(canvas);
                if (!blob) throw new Error('The selected image could not be optimized.');

                uploadFile = new File([blob], file.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                });
                replaceFile(input, uploadFile);
            }

            if (uploadFile.size > MAX_UPLOAD_BYTES) {
                throw new Error('The image is still larger than 5 MB after optimization.');
            }

            input.dataset.imageOptimized = 'true';
            updatePreview(input, uploadFile);

            var label = input.nextElementSibling;
            if (label && label.classList.contains('custom-file-label')) {
                label.textContent = file.name;
            }
            showFeedback(
                input,
                shouldOptimize
                    ? 'Image optimized from ' + formatBytes(file.size) + ' to ' + formatBytes(uploadFile.size) + '.'
                    : 'Image ready for upload (' + formatBytes(uploadFile.size) + ').',
                false
            );
        } catch (error) {
            input.value = '';
            resetPreview(input);
            showFeedback(input, error.message, true);
        } finally {
            setFormBusy(input, false);
        }
    }

    function initialize() {
        ['house_image', 'receipt_image'].forEach(function (id) {
            var input = document.getElementById(id);
            if (input && input.dataset.imageUploadBound !== 'true') {
                input.dataset.imageUploadBound = 'true';
                input.addEventListener('change', optimizeSelectedImage);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
