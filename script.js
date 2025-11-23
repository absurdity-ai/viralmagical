document.addEventListener('DOMContentLoaded', () => {
    const promptInput = document.getElementById('promptInput');
    const createBtn = document.getElementById('createBtn');
    const btnText = createBtn.querySelector('.btn-text');
    const loader = createBtn.querySelector('.loader');
    const sponsorCards = document.querySelectorAll('.sponsor-card');
    const galleryGrid = document.getElementById('galleryGrid');

    // Image Upload Elements
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const clearImageBtn = document.getElementById('clearImageBtn');

    let selectedSponsor = 'can';
    let selectedImageFiles = []; // Array to store processed images
    const MAX_IMAGES = 7;
    const MAX_FILE_SIZE_MB = 1;

    // Image Input Change - Handle Multiple Images
    imageInput.addEventListener('change', async (e) => {
        const files = Array.from(e.target.files);

        if (files.length + selectedImageFiles.length > MAX_IMAGES) {
            alert(`You can only upload up to ${MAX_IMAGES} images.`);
            return;
        }

        for (const file of files) {
            try {
                let processedFile = file;

                // Convert HEIC to JPEG if needed
                if (file.type === 'image/heic' || file.type === 'image/heif' || file.name.toLowerCase().endsWith('.heic') || file.name.toLowerCase().endsWith('.heif')) {
                    console.log('Attempting HEIC to JPEG conversion for:', file.name, 'Type:', file.type, 'Size:', file.size);

                    // Capture original filename before conversion
                    const originalFilename = file.name || `heic_${Date.now()}`;

                    // Check if HeicTo is available (note: capital H)
                    if (typeof window.HeicTo === 'undefined') {
                        console.error('HeicTo library not loaded!');
                        alert('HEIC conversion library not available. Please refresh the page or convert to JPG/PNG first.');
                        continue; // Skip this file
                    }

                    try {
                        const convertedBlob = await window.HeicTo({
                            blob: file,
                            type: 'image/jpeg',
                            quality: 0.9
                        });

                        if (!convertedBlob) {
                            throw new Error('HEIC conversion returned empty blob');
                        }

                        // Generate proper filename from original
                        let newFilename;
                        if (!originalFilename || originalFilename === 'blob') {
                            newFilename = `heic_converted_${Date.now()}.jpg`;
                        } else {
                            newFilename = originalFilename.replace(/\.heic$/i, '.jpg').replace(/\.heif$/i, '.jpg');
                        }

                        processedFile = new File([convertedBlob], newFilename, { type: 'image/jpeg' });
                        console.log('HEIC conversion successful, new file:', processedFile.name);

                    } catch (conversionError) {
                        console.error('Error during HEIC conversion:', conversionError);
                        alert(`Failed to convert HEIC: ${conversionError.message}. Please convert to JPG/PNG first.`);
                        continue; // Skip this file
                    }
                }

                // Compress image to max 1MB
                const options = {
                    maxSizeMB: MAX_FILE_SIZE_MB,
                    maxWidthOrHeight: 2048,
                    useWebWorker: true
                };

                const compressedFile = await imageCompression(processedFile, options);
                console.log(`Compressed from ${(file.size / 1024 / 1024).toFixed(2)}MB to ${(compressedFile.size / 1024 / 1024).toFixed(2)}MB`);

                selectedImageFiles.push(compressedFile);
                addThumbnail(compressedFile);

            } catch (error) {
                console.error('Error processing image:', error);
                alert(`Failed to process ${file.name}. Please try another image.`);
            }
        }

        // Clear input to allow re-selecting same file
        imageInput.value = '';

        // Show preview grid
        if (selectedImageFiles.length > 0) {
            imagePreview.classList.remove('hidden');
        }
    });

    // Add thumbnail to preview grid
    function addThumbnail(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'thumbnail-wrapper';

            const img = document.createElement('img');
            img.src = e.target.result;

            const removeBtn = document.createElement('button');
            removeBtn.className = 'thumbnail-remove';
            removeBtn.innerHTML = '×';
            removeBtn.onclick = () => removeThumbnail(file, wrapper);

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            imagePreview.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    }

    // Remove thumbnail
    function removeThumbnail(file, wrapper) {
        const index = selectedImageFiles.indexOf(file);
        if (index > -1) {
            selectedImageFiles.splice(index, 1);
        }
        wrapper.remove();

        if (selectedImageFiles.length === 0) {
            imagePreview.classList.add('hidden');
        }
    }


    // Sponsor Selection
    sponsorCards.forEach(card => {
        card.addEventListener('click', () => {
            sponsorCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedSponsor = card.dataset.sponsor;
        });
    });

    // Create Button Click
    createBtn.addEventListener('click', async () => {
        const prompt = promptInput.value.trim();
        if (!prompt && selectedImageFiles.length === 0) {
            alert('Please describe your idea or upload an image!');
            return;
        }

        // Show modal
        const modal = document.getElementById('creationModal');
        const modalStatus = document.getElementById('modalStatus');
        const modalResult = document.getElementById('modalResult');
        const modalClose = document.getElementById('modalClose');

        modal.classList.remove('hidden');
        document.querySelector('.progress-spinner').classList.remove('hidden');
        modalStatus.textContent = 'Processing your images...';
        modalResult.classList.add('hidden');
        modalClose.classList.add('hidden');

        // UI Loading State
        createBtn.disabled = true;
        btnText.classList.add('hidden');
        loader.classList.remove('hidden');

        try {
            const formData = new FormData();
            formData.append('prompt', prompt);
            formData.append('sponsor', selectedSponsor);

            // Append all images
            selectedImageFiles.forEach((file, index) => {
                formData.append('images[]', file);
            });

            modalStatus.textContent = 'Uploading images and generating your creation...';

            const response = await fetch('api/create.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                modalStatus.textContent = 'Success! Your creation is ready! 🎉';

                // Show result image and share URL in modal
                const shareUrl = data.app.share_url || `${window.location.origin}/app/${data.app.short_code}`;
                modalResult.innerHTML = `
                    <img src="${data.app.image_url}" alt="${data.app.prompt}">
                    <p style="margin-top: 1rem; opacity: 0.9;">Sponsored by ${data.app.sponsor}</p>
                    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(0,0,0,0.3); border-radius: 12px;">
                        <p style="font-size: 0.9rem; opacity: 0.8; margin-bottom: 0.5rem;">Share your creation:</p>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="text" value="${shareUrl}" readonly 
                                   id="shareUrlInput"
                                   style="flex: 1; padding: 0.5rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; color: white; font-size: 0.9rem;">
                            <button onclick="navigator.clipboard.writeText('${shareUrl}').then(() => { this.textContent = '✓ Copied!'; setTimeout(() => this.textContent = 'Copy', 2000); })" 
                                    style="padding: 0.5rem 1rem; background: linear-gradient(45deg, #ff0055, #0055ff); border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: 600; white-space: nowrap;">
                                Copy
                            </button>
                        </div>
                    </div>
                `;
                // Hide spinner
                document.querySelector('.progress-spinner').classList.add('hidden');

                modalResult.classList.remove('hidden');
                modalClose.classList.remove('hidden');

                // Add to gallery
                addGalleryItem(data.app, true);

                // Clear form
                promptInput.value = '';
                selectedImageFiles = [];
                imagePreview.innerHTML = '';
                imagePreview.classList.add('hidden');
            } else {
                // Hide spinner
                document.querySelector('.progress-spinner').classList.add('hidden');

                modalStatus.textContent = 'Error: ' + (data.error || 'Unknown error');
                modalClose.classList.remove('hidden');
                modalClose.textContent = 'Close';
            }

        } catch (error) {
            console.error('Error:', error);
            modalStatus.textContent = 'Something went wrong. Please try again.';
            modalClose.classList.remove('hidden');
            modalClose.textContent = 'Close';
        } finally {
            // Reset button UI
            createBtn.disabled = false;
            btnText.classList.remove('hidden');
            loader.classList.add('hidden');
        }
    });

    // Modal close button
    document.getElementById('modalClose').addEventListener('click', () => {
        const modal = document.getElementById('creationModal');
        modal.classList.add('hidden');

        // Scroll to gallery
        document.querySelector('.gallery-section').scrollIntoView({ behavior: 'smooth' });
    });

    // User ID Management
    let userId = getCookie('vm_user_id');
    if (!userId) {
        userId = 'user_' + Math.random().toString(36).substr(2, 9);
        setCookie('vm_user_id', userId, 365);
    }

    // Check for remix param
    const urlParams = new URLSearchParams(window.location.search);
    const remixPrompt = urlParams.get('remix');
    if (remixPrompt) {
        promptInput.value = remixPrompt;
        promptInput.focus();
    }

    // Load initial gallery
    loadGallery();

    async function loadGallery() {
        try {
            const response = await fetch('api/get_apps.php?user_id=' + userId);
            const data = await response.json();

            // Only show skeletons if gallery is empty (initial load)
            if (galleryGrid.children.length === 0 || galleryGrid.querySelector('.skeleton')) {
                const skeletons = galleryGrid.querySelectorAll('.skeleton');
                if (skeletons.length > 0) {
                    skeletons.forEach(skeleton => {
                        skeleton.style.opacity = '0';
                        skeleton.style.transition = 'opacity 0.3s ease';
                    });

                    // Wait for fade out, then replace content
                    setTimeout(() => {
                        updateGalleryContent(data);
                    }, 300);
                } else {
                    updateGalleryContent(data);
                }
            } else {
                // Background update - no skeletons, just prepend new items or replace if needed
                // For simplicity, we'll just replace content to ensure order, but without the skeleton flash
                updateGalleryContent(data);
            }
        } catch (error) {
            console.error('Error loading gallery:', error);
            if (galleryGrid.children.length === 0 || galleryGrid.querySelector('.skeleton')) {
                galleryGrid.innerHTML = '<p style="text-align:center; grid-column: 1/-1; opacity: 0.7; color: #ff6b6b;">Failed to load gallery. Please refresh.</p>';
            }
        }
    }

    function updateGalleryContent(data) {
        if (data.success && data.apps.length > 0) {
            galleryGrid.innerHTML = ''; // Clear existing content
            data.apps.forEach(app => addGalleryItem(app, false)); // Append for initial load
        } else {
            galleryGrid.innerHTML = '<p style="text-align:center; grid-column: 1/-1; opacity: 0.7;">No magic created yet. Be the first!</p>';
        }
    }

    function addGalleryItem(app, prepend = false) {
        const item = document.createElement('div');
        item.className = 'gallery-item glass-panel';
        const shareUrl = app.share_url ? app.share_url : (app.short_code ? `app/${app.short_code}` : '#');

        // Use sponsor_name if available, fallback to sponsor code
        const sponsorDisplay = app.sponsor_name || app.sponsor;

        item.innerHTML = `
            <a href="${shareUrl}" style="text-decoration:none; color:inherit; display:block; height:100%;">
                <img src="${app.image_url}" alt="${app.prompt}" loading="lazy">
                <div class="gallery-overlay">
                    <p style="font-weight:bold; margin-bottom:0.5rem;">${truncate(app.prompt, 50)}</p>
                    <p style="font-size:0.8rem; opacity:0.8;">Sponsored by ${sponsorDisplay}</p>
                </div>
            </a>
        `;
        if (prepend && galleryGrid.firstChild) {
            galleryGrid.insertBefore(item, galleryGrid.firstChild);
        } else {
            galleryGrid.appendChild(item);
        }
    }

    function truncate(str, n) {
        return (str.length > n) ? str.substr(0, n - 1) + '&hellip;' : str;
    }

    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
});
