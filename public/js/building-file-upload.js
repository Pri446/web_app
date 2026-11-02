(function () {
    'use strict';

    var KML_MAX_BYTES = 1 * 1024 * 1024;
    var IMAGE_MAX_BYTES = 5 * 1024 * 1024;
    var IMAGE_COMPRESSION_THRESHOLD = 1 * 1024 * 1024;
    var IMAGE_MAX_DIMENSION = 1600;
    var IMAGE_QUALITY = 0.70;
    var previewUrl = null;
    var imageProcessing = false;
    var kmlProcessing = false;

    function formatBytes(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }

        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }

        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    function setFeedback(element, message, isValid) {
        if (!element) {
            return;
        }

        element.textContent = message;
        element.classList.toggle('text-success', isValid);
        element.classList.toggle('text-danger', !isValid);
    }

    function setHintStatus(hintElement, status) {
        if (!hintElement) {
            return;
        }

        if (status === 'success') {
            hintElement.classList.remove('text-muted', 'text-danger');
            hintElement.classList.add('text-success');
            hintElement.style.color = '#28a745';
        } else if (status === 'error') {
            hintElement.classList.remove('text-muted', 'text-success');
            hintElement.classList.add('text-danger');
            hintElement.style.color = '#dc3545';
        } else {
            hintElement.classList.remove('text-success', 'text-danger');
            hintElement.classList.add('text-muted');
            hintElement.style.color = '';
        }
    }

    function clearInput(input) {
        input.value = '';
    }

    function setFormBusy(input, busy, fileType) {
        var form = input.form;

        if (fileType === 'image') {
            imageProcessing = busy;
        } else if (fileType === 'kml') {
            kmlProcessing = busy;
        }

        if (!form) {
            return;
        }

        Array.prototype.forEach.call(
            form.querySelectorAll('button[type="submit"], input[type="submit"]'),
            function (button) {
                button.disabled = imageProcessing || kmlProcessing;
            }
        );
    }

    function replaceInputFile(input, file) {
        if (typeof DataTransfer === 'undefined') {
            return false;
        }

        var transfer = new DataTransfer();
        transfer.items.add(file);
        input.files = transfer.files;

        return true;
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
                reject(new Error('The selected file could not be read as an image.'));
            };
            image.src = url;
        });
    }

    function canvasToBlob(canvas) {
        return new Promise(function (resolve) {
            canvas.toBlob(resolve, 'image/jpeg', IMAGE_QUALITY);
        });
    }

    function showImagePreview(file) {
        var preview = document.getElementById('houseImagePreview');

        if (!preview) {
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }

        previewUrl = URL.createObjectURL(file);
        preview.src = previewUrl;
        preview.classList.remove('d-none');
    }

    async function handleImageChange(event) {
        var input = event.currentTarget;
        var feedback = document.getElementById('imageFileFeedback');
        var hint = document.getElementById('fileSizeHintImg');
        var file = input.files && input.files[0];
        var originalSize = file ? file.size : 0;

        if (!file) {
            setHintStatus(hint, 'default');
            setFeedback(feedback, '', true);
            return;
        }

        var extensionIsValid = /\.(jpe?g)$/i.test(file.name);
        var mimeIsValid = !file.type || file.type === 'image/jpeg';

        if (!extensionIsValid || !mimeIsValid) {
            clearInput(input);
            setHintStatus(hint, 'error');
            setFeedback(feedback, 'Only JPG or JPEG images are allowed.', false);
            return;
        }

        setFormBusy(input, true, 'image');

        try {
            var image = await loadImage(file);
            var shouldCompress = file.size > IMAGE_COMPRESSION_THRESHOLD ||
                image.naturalWidth > IMAGE_MAX_DIMENSION ||
                image.naturalHeight > IMAGE_MAX_DIMENSION;
            var uploadFile = file;

            if (shouldCompress) {
                var scale = Math.min(
                    1,
                    IMAGE_MAX_DIMENSION / Math.max(image.naturalWidth, image.naturalHeight)
                );
                var canvas = document.createElement('canvas');
                canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                var context = canvas.getContext('2d');
                context.drawImage(image, 0, 0, canvas.width, canvas.height);

                var blob = await canvasToBlob(canvas);

                if (blob && blob.size < file.size) {
                    var compressedFile = new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });

                    if (replaceInputFile(input, compressedFile)) {
                        uploadFile = compressedFile;
                    }
                }
            }

            if (uploadFile.size > IMAGE_MAX_BYTES) {
                clearInput(input);
                setHintStatus(hint, 'error');
                setFeedback(
                    feedback,
                    'The image is still larger than 5 MB after compression. Choose a smaller image.',
                    false
                );
                return;
            }

            showImagePreview(uploadFile);
            setHintStatus(hint, 'success');
            setFeedback(
                feedback,
                uploadFile !== file
                    ? 'Image optimized from ' + formatBytes(originalSize) + ' to ' + formatBytes(uploadFile.size) + '.'
                    : 'Image ready for upload (' + formatBytes(uploadFile.size) + ').',
                true
            );
        } catch (error) {
            clearInput(input);
            setHintStatus(hint, 'error');
            setFeedback(feedback, error.message, false);
        } finally {
            setFormBusy(input, false, 'image');
        }
    }

    function handleKmlChange(event) {
        var input = event.currentTarget;
        var feedback = document.getElementById('kmlFileFeedback');
        var hint = document.getElementById('fileSizeHintKML');
        var file = input.files && input.files[0];

        if (!file) {
            setHintStatus(hint, 'default');
            setFeedback(feedback, '', true);
            return;
        }

        if (!/\.kml$/i.test(file.name)) {
            clearInput(input);
            setHintStatus(hint, 'error');
            setFeedback(feedback, 'Only KML files are allowed.', false);
            return;
        }

        if (file.size > KML_MAX_BYTES) {
            clearInput(input);
            setHintStatus(hint, 'error');
            setFeedback(feedback, 'The KML file must not exceed 1 MB.', false);
            return;
        }

        setFormBusy(input, true, 'kml');

        var reader = new FileReader();
        reader.onload = function () {
            var documentXml = new DOMParser().parseFromString(reader.result, 'application/xml');
            var hasParserError = documentXml.getElementsByTagName('parsererror').length > 0;
            var hasPolygon = documentXml.getElementsByTagNameNS('*', 'Polygon').length > 0 ||
                documentXml.getElementsByTagName('Polygon').length > 0;

            if (hasParserError || !hasPolygon) {
                clearInput(input);
                setHintStatus(hint, 'error');
                setFeedback(
                    feedback,
                    'The KML is invalid or does not contain a Polygon geometry.',
                    false
                );
                setFormBusy(input, false, 'kml');
                return;
            }

            setHintStatus(hint, 'success');
            setFeedback(feedback, '', true);
            setFormBusy(input, false, 'kml');
        };
        reader.onerror = function () {
            clearInput(input);
            setHintStatus(hint, 'error');
            setFeedback(feedback, 'The KML file could not be read.', false);
            setFormBusy(input, false, 'kml');
        };
        reader.readAsText(file);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var imageInput = document.getElementById('house_image');
        var kmlInput = document.getElementById('geom');
        var form = imageInput ? imageInput.form : (kmlInput ? kmlInput.form : null);

        if (imageInput) {
            imageInput.addEventListener('change', handleImageChange);
        }

        if (kmlInput) {
            kmlInput.addEventListener('change', handleKmlChange);
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                if (!imageProcessing && !kmlProcessing) {
                    return;
                }

                event.preventDefault();
                setFeedback(
                    imageProcessing
                        ? document.getElementById('imageFileFeedback')
                        : document.getElementById('kmlFileFeedback'),
                    'Please wait until file processing finishes.',
                    false
                );
            });
        }
    });

    window.addEventListener('beforeunload', function () {
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
    });
}());
