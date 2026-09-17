// customizer.js
// Interactive 3D Rotatable Customizer Script for T-Shirt Studio

document.addEventListener('DOMContentLoaded', () => {
    // DOM Elements
    const tshirtWrapper = document.getElementById('tshirt-wrapper');
    const tshirt3dContainer = document.getElementById('tshirt-3d-container');
    const viewIndicator = document.getElementById('view-indicator');
    
    // Front Face Elements
    const printableAreaFront = document.getElementById('printable-area-front');
    const draggableDesignFront = document.getElementById('design-front');
    const designImgFront = document.getElementById('design-img-front');
    const handleResizeFront = document.getElementById('handle-resize-front');
    const handleDeleteFront = document.getElementById('handle-delete-front');
    
    // Back Face Elements
    const printableAreaBack = document.getElementById('printable-area-back');
    const draggableDesignBack = document.getElementById('design-back');
    const designImgBack = document.getElementById('design-img-back');
    const handleResizeBack = document.getElementById('handle-resize-back');
    const handleDeleteBack = document.getElementById('handle-delete-back');

    // Controls Elements
    const snapFrontBtn = document.getElementById('snap-front-btn');
    const snapBackBtn = document.getElementById('snap-back-btn');
    const rotationSlider = document.getElementById('rotation-slider');
    const autoRotateBtn = document.getElementById('auto-rotate-btn');
    const playIcon = document.getElementById('play-icon');
    const pauseIcon = document.getElementById('pause-icon');

    const colorSwatches = document.querySelectorAll('.color-swatch');
    const customColorPicker = document.getElementById('custom-color-picker');
    const customColorWrapper = document.getElementById('custom-color-wrapper');
    const colorNameLabel = document.getElementById('color-name-label');
    const shirtColorInput = document.getElementById('shirt_color_input');
    const sizeBtns = document.querySelectorAll('.size-btn');
    const shirtSizeInput = document.getElementById('shirt_size_input');
    const designThumbs = document.querySelectorAll('.design-thumb');
    const customDesignInput = document.getElementById('custom-design-input');
    
    // Hidden inputs for form submission
    const previewImageInput = document.getElementById('preview_image_input');
    const submitBtn = document.getElementById('submit-order-btn');
    const btnSpinner = document.getElementById('btn-spinner');

    // Customizer State
    let state = {
        color: '#ffffff',
        size: 'M',
        rotation: 0,
        front: {
            designType: 'none', // 'none', 'preset', 'custom'
            designSrc: '', // path or base64
            x: 50,
            y: 50,
            scale: 0.5,
            aspectRatio: 1.0
        },
        back: {
            designType: 'none', // 'none', 'preset', 'custom'
            designSrc: '', // path or base64
            x: 50,
            y: 50,
            scale: 0.5,
            aspectRatio: 1.0
        }
    };

    // Initialize customizer
    initColors();
    initSizes();
    initDesigns();
    
    // Setup Drag-n-Drop & Controls for Front Design
    setupDraggable('front');
    setupResizable('front');
    setupDelete('front');
    
    // Setup Drag-n-Drop & Controls for Back Design
    setupDraggable('back');
    setupResizable('back');
    setupDelete('back');
    
    // Setup Rotation Controls
    initRotationControls();
    initRotationDrag();

    loadState(); // Restore design state if saved locally

    // Save and Load State Helpers
    function saveState() {
        localStorage.setItem('zeng_customizer_state_v2', JSON.stringify(state));
    }

    function loadState() {
        const saved = localStorage.getItem('zeng_customizer_state_v2');
        if (saved) {
            try {
                const loaded = JSON.parse(saved);
                state = { ...state, ...loaded };
                
                // Restore color
                const baseFront = document.getElementById('tshirt-base');
                const baseBack = document.getElementById('tshirt-base-back');
                if (baseFront) baseFront.setAttribute('fill', state.color);
                if (baseBack) baseBack.setAttribute('fill', state.color);
                shirtColorInput.value = state.color;
                
                // Update color swatch UI
                colorSwatches.forEach(s => {
                    if (s.dataset.color.toLowerCase() === state.color.toLowerCase()) {
                        s.classList.add('active');
                        colorNameLabel.textContent = s.dataset.name;
                    } else {
                        s.classList.remove('active');
                    }
                });
                
                // Custom color picker check
                const isPresetColor = Array.from(colorSwatches).some(s => s.dataset.color.toLowerCase() === state.color.toLowerCase());
                if (!isPresetColor) {
                    customColorWrapper.classList.add('active');
                    customColorPicker.value = state.color;
                    colorNameLabel.textContent = `Custom (${state.color.toUpperCase()})`;
                }
                
                // Restore size
                shirtSizeInput.value = state.size;
                sizeBtns.forEach(b => {
                    if (b.dataset.size === state.size) {
                        b.classList.add('active');
                    } else {
                        b.classList.remove('active');
                    }
                });
                
                // Restore rotation
                updateRotation(state.rotation || 0);
                
                // Restore front and back designs
                if (state.front.designSrc && state.front.designType !== 'none') {
                    applyDesign('front', state.front.designSrc, state.front.designType, false);
                }
                if (state.back.designSrc && state.back.designType !== 'none') {
                    applyDesign('back', state.back.designSrc, state.back.designType, false);
                }
            } catch (e) {
                console.error("Failed to load saved state:", e);
            }
        } else {
            // Clean up any old single-side state
            localStorage.removeItem('zeng_customizer_state');
        }
    }

    // 1. Color Customization
    function initColors() {
        colorSwatches.forEach(swatch => {
            swatch.addEventListener('click', () => {
                colorSwatches.forEach(s => s.classList.remove('active'));
                customColorWrapper.classList.remove('active');
                
                swatch.classList.add('active');
                const color = swatch.dataset.color;
                const name = swatch.dataset.name;
                
                updateShirtColor(color, name);
            });
        });

        customColorPicker.addEventListener('input', (e) => {
            colorSwatches.forEach(s => s.classList.remove('active'));
            customColorWrapper.classList.add('active');
            
            const color = e.target.value;
            updateShirtColor(color, `Custom (${color.toUpperCase()})`);
        });
    }

    function updateShirtColor(color, name) {
        state.color = color;
        colorNameLabel.textContent = name;
        shirtColorInput.value = color;
        
        const baseFront = document.getElementById('tshirt-base');
        const baseBack = document.getElementById('tshirt-base-back');
        if (baseFront) baseFront.setAttribute('fill', color);
        if (baseBack) baseBack.setAttribute('fill', color);
        
        saveState();
    }

    // 2. Size Customization
    function initSizes() {
        sizeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                sizeBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                state.size = btn.dataset.size;
                shirtSizeInput.value = state.size;
                showToast(`Size changed to ${state.size}`);
                saveState();
            });
        });
    }

    // Determine active face ('front' or 'back') based on current 3D rotation
    function getActiveFaceName() {
        const normRot = ((state.rotation % 360) + 360) % 360;
        return (normRot > 90 && normRot < 270) ? 'back' : 'front';
    }

    // 3. Design Selection & Drag-n-Drop Catalog
    function initDesigns() {
        // Apply on click
        designThumbs.forEach(thumb => {
            thumb.addEventListener('click', () => {
                designThumbs.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
                
                const src = thumb.dataset.designSrc;
                const activeFace = getActiveFaceName();
                applyDesign(activeFace, src, 'preset');
            });

            // Drag Start
            const img = thumb.querySelector('img');
            img.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', thumb.dataset.designSrc);
                e.dataTransfer.setData('type', 'preset');
            });
        });

        // Setup drop-zones for both printable areas
        [
            { name: 'front', area: printableAreaFront },
            { name: 'back', area: printableAreaBack }
        ].forEach(({ name, area }) => {
            if (!area) return;

            area.addEventListener('dragover', (e) => {
                e.preventDefault();
                area.classList.add('active');
            });

            area.addEventListener('dragleave', () => {
                area.classList.remove('active');
            });

            area.addEventListener('drop', (e) => {
                e.preventDefault();
                area.classList.remove('active');
                
                const src = e.dataTransfer.getData('text/plain');
                const type = e.dataTransfer.getData('type') || 'preset';
                
                if (src) {
                    designThumbs.forEach(t => {
                        if (t.dataset.designSrc === src) {
                            t.classList.add('active');
                        } else {
                            t.classList.remove('active');
                        }
                    });
                    
                    const rect = area.getBoundingClientRect();
                    const dropX = e.clientX - rect.left;
                    const dropY = e.clientY - rect.top;
                    
                    // Map horizontal coordinate based on face projection
                    let xPercent = (dropX / rect.width) * 100;
                    if (name === 'back') {
                        xPercent = 100 - xPercent; // Invert coordinates because the face is rotated 180 degrees
                    }
                    
                    state[name].x = Math.max(0, Math.min(100, xPercent));
                    state[name].y = Math.max(0, Math.min(100, (dropY / rect.height) * 100));
                    
                    applyDesign(name, src, type);
                }
            });
        });
    }

    // Apply design image to the canvas layout
    function applyDesign(faceName, src, type, shouldSaveState = true) {
        const designEl = faceName === 'front' ? draggableDesignFront : draggableDesignBack;
        const imgEl = faceName === 'front' ? designImgFront : designImgBack;
        const designTypeInput = faceName === 'front' ? document.getElementById('design_type_input') : document.getElementById('back_design_type_input');
        const designSrcInput = faceName === 'front' ? document.getElementById('design_src_input') : document.getElementById('back_design_src_input');
        
        state[faceName].designType = type;
        state[faceName].designSrc = src;
        
        designTypeInput.value = type;
        designSrcInput.value = src;
        
        imgEl.src = src;
        designEl.style.display = 'flex';
        designEl.classList.add('selected');
        
        // Remove selection outline from the other side
        const otherEl = faceName === 'front' ? draggableDesignBack : draggableDesignFront;
        otherEl.classList.remove('selected');
        
        imgEl.onload = () => {
            state[faceName].aspectRatio = imgEl.naturalWidth / imgEl.naturalHeight;
            updateDesignTransform(faceName);
            if (shouldSaveState) {
                saveState();
                showToast(`${faceName.charAt(0).toUpperCase() + faceName.slice(1)} design applied!`);
            }
        };
    }

    // Custom File Upload
    window.handleCustomFileUpload = function(event) {
        const file = event.target.files[0];
        if (!file) return;

        if (!file.type.match('image.*')) {
            showToast("Please upload an image file", "error");
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const base64Data = e.target.result;
            
            designThumbs.forEach(t => t.classList.remove('active'));
            
            const activeFace = getActiveFaceName();
            applyDesign(activeFace, base64Data, 'custom');
            
            const inputSrc = activeFace === 'front' ? document.getElementById('design_src_input') : document.getElementById('back_design_src_input');
            inputSrc.value = file.name;
        };
        reader.readAsDataURL(file);
    };

    // Update design position and size on screen
    function updateDesignTransform(faceName) {
        const designEl = faceName === 'front' ? draggableDesignFront : draggableDesignBack;
        const widthPercent = state[faceName].scale * 100;
        const heightPercent = widthPercent / state[faceName].aspectRatio;
        
        designEl.style.width = `${widthPercent}%`;
        designEl.style.height = `${heightPercent}%`;
        
        designEl.style.left = `${state[faceName].x - (widthPercent / 2)}%`;
        designEl.style.top = `${state[faceName].y - (heightPercent / 2)}%`;
        
        // Write to hidden inputs
        if (faceName === 'front') {
            document.getElementById('design_x_input').value = state.front.x.toFixed(2);
            document.getElementById('design_y_input').value = state.front.y.toFixed(2);
            document.getElementById('design_scale_input').value = state.front.scale.toFixed(2);
        } else {
            document.getElementById('back_design_x_input').value = state.back.x.toFixed(2);
            document.getElementById('back_design_y_input').value = state.back.y.toFixed(2);
            document.getElementById('back_design_scale_input').value = state.back.scale.toFixed(2);
        }
        saveState();
    }

    // 4. Draggable Logic
    function setupDraggable(faceName) {
        const designEl = faceName === 'front' ? draggableDesignFront : draggableDesignBack;
        const areaEl = faceName === 'front' ? printableAreaFront : printableAreaBack;
        
        let isDragging = false;
        let startMouseX = 0;
        let startMouseY = 0;
        let startX = 0;
        let startY = 0;

        designEl.addEventListener('mousedown', dragStart);
        designEl.addEventListener('touchstart', dragStart, { passive: false });

        function dragStart(e) {
            if (e.target.classList.contains('design-control-handle')) return;
            
            isDragging = true;
            designEl.classList.add('selected');
            
            // Unselect other side
            const otherEl = faceName === 'front' ? draggableDesignBack : draggableDesignFront;
            otherEl.classList.remove('selected');
            
            const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.startsWith('touch') ? e.touches[0].clientY : e.clientY;
            
            startMouseX = clientX;
            startMouseY = clientY;
            startX = state[faceName].x;
            startY = state[faceName].y;
            
            document.addEventListener('mousemove', dragMove);
            document.addEventListener('mouseup', dragEnd);
            document.addEventListener('touchmove', dragMove, { passive: false });
            document.addEventListener('touchend', dragEnd);
            
            e.preventDefault();
        }

        function dragMove(e) {
            if (!isDragging) return;
            
            const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
            const clientY = e.type.startsWith('touch') ? e.touches[0].clientY : e.clientY;
            
            const rect = areaEl.getBoundingClientRect();
            let deltaX = clientX - startMouseX;
            const deltaY = clientY - startMouseY;
            
            // Invert drag direction horizontally for the back face
            if (faceName === 'back') {
                deltaX = -deltaX;
            }
            
            const deltaXPercent = (deltaX / rect.width) * 100;
            const deltaYPercent = (deltaY / rect.height) * 100;
            
            state[faceName].x = Math.max(0, Math.min(100, startX + deltaXPercent));
            state[faceName].y = Math.max(0, Math.min(100, startY + deltaYPercent));
            
            updateDesignTransform(faceName);
            e.preventDefault();
        }

        function dragEnd() {
            isDragging = false;
            document.removeEventListener('mousemove', dragMove);
            document.removeEventListener('mouseup', dragEnd);
            document.removeEventListener('touchmove', dragMove);
            document.removeEventListener('touchend', dragEnd);
        }
    }

    // 5. Resizable Logic
    function setupResizable(faceName) {
        const handleResize = faceName === 'front' ? handleResizeFront : handleResizeBack;
        const areaEl = faceName === 'front' ? printableAreaFront : printableAreaBack;
        
        let isResizing = false;
        let startMouseX = 0;
        let startScale = 0;

        handleResize.addEventListener('mousedown', resizeStart);
        handleResize.addEventListener('touchstart', resizeStart, { passive: false });

        function resizeStart(e) {
            e.stopPropagation();
            isResizing = true;
            
            const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
            startMouseX = clientX;
            startScale = state[faceName].scale;
            
            document.addEventListener('mousemove', resizeMove);
            document.addEventListener('mouseup', resizeEnd);
            document.addEventListener('touchmove', resizeMove, { passive: false });
            document.addEventListener('touchend', resizeEnd);
            
            e.preventDefault();
        }

        function resizeMove(e) {
            if (!isResizing) return;
            
            const clientX = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
            const rect = areaEl.getBoundingClientRect();
            let deltaX = clientX - startMouseX;
            
            // Invert resize drag direction for the back face
            if (faceName === 'back') {
                deltaX = -deltaX;
            }
            
            const deltaScale = (deltaX / rect.width) * 2;
            state[faceName].scale = Math.max(0.1, Math.min(1.0, startScale + deltaScale));
            
            updateDesignTransform(faceName);
            e.preventDefault();
        }

        function resizeEnd() {
            isResizing = false;
            document.removeEventListener('mousemove', resizeMove);
            document.removeEventListener('mouseup', resizeEnd);
            document.removeEventListener('touchmove', resizeMove);
            document.removeEventListener('touchend', resizeEnd);
        }
    }

    // Delete Logic
    function setupDelete(faceName) {
        const handleDelete = faceName === 'front' ? handleDeleteFront : handleDeleteBack;
        const designEl = faceName === 'front' ? draggableDesignFront : draggableDesignBack;
        const imgEl = faceName === 'front' ? designImgFront : designImgBack;
        const designTypeInput = faceName === 'front' ? document.getElementById('design_type_input') : document.getElementById('back_design_type_input');
        const designSrcInput = faceName === 'front' ? document.getElementById('design_src_input') : document.getElementById('back_design_src_input');
        
        handleDelete.addEventListener('click', (e) => {
            e.stopPropagation();
            designEl.style.display = 'none';
            designEl.classList.remove('selected');
            imgEl.src = '';
            
            state[faceName].designSrc = '';
            state[faceName].designType = 'none';
            
            designTypeInput.value = 'none';
            designSrcInput.value = '';
            
            designThumbs.forEach(t => t.classList.remove('active'));
            saveState();
            showToast(`${faceName.charAt(0).toUpperCase() + faceName.slice(1)} design removed`);
        });
    }

    // Click outside to deselect
    document.addEventListener('click', (e) => {
        if (
            !e.target.closest('.design-element') && 
            !e.target.closest('.designs-catalog') && 
            !e.target.closest('.upload-wrapper') &&
            !e.target.closest('.rotation-controls-wrapper')
        ) {
            draggableDesignFront.classList.remove('selected');
            draggableDesignBack.classList.remove('selected');
        }
    });

    // 6. 3D Rotation Controls & Drag Setup
    function initRotationControls() {
        // snap buttons
        snapFrontBtn.addEventListener('click', () => {
            stopAutoRotation();
            // Smoothly rotate back to 0 (or nearest 360 multiple)
            const target = Math.round(state.rotation / 360) * 360;
            animateRotation(target);
        });

        snapBackBtn.addEventListener('click', () => {
            stopAutoRotation();
            // Smoothly rotate to 180 (or nearest 180 odd multiple)
            const target = Math.round((state.rotation - 180) / 360) * 360 + 180;
            animateRotation(target);
        });

        // slider input
        rotationSlider.addEventListener('input', (e) => {
            stopAutoRotation();
            const angle = parseInt(e.target.value);
            // Translate slider 0-360 directly into state rotation (preserving loops if dragged)
            const baseRot = Math.floor(state.rotation / 360) * 360;
            updateRotation(baseRot + angle);
        });

        // Auto rotate logic
        let autoRotateFrame = null;
        let lastTime = 0;

        function autoRotateStep(time) {
            if (!lastTime) lastTime = time;
            const delta = time - lastTime;
            lastTime = time;
            
            // Spin 40 degrees per second
            updateRotation(state.rotation + (40 * delta) / 1000);
            autoRotateFrame = requestAnimationFrame(autoRotateStep);
        }

        window.startAutoRotation = function() {
            if (autoRotateFrame) return;
            playIcon.style.display = 'none';
            pauseIcon.style.display = 'block';
            autoRotateBtn.classList.add('active');
            lastTime = 0;
            autoRotateFrame = requestAnimationFrame(autoRotateStep);
        };

        window.stopAutoRotation = function() {
            if (!autoRotateFrame) return;
            cancelAnimationFrame(autoRotateFrame);
            autoRotateFrame = null;
            playIcon.style.display = 'block';
            pauseIcon.style.display = 'none';
            autoRotateBtn.classList.remove('active');
        };

        autoRotateBtn.addEventListener('click', () => {
            if (autoRotateFrame) {
                stopAutoRotation();
            } else {
                startAutoRotation();
            }
        });
    }

    // Animate rotation to a target angle
    function animateRotation(targetDeg) {
        tshirt3dContainer.classList.remove('dragging');
        updateRotation(targetDeg);
    }

    function updateRotation(deg) {
        state.rotation = deg;
        tshirt3dContainer.style.transform = `rotateY(${deg}deg)`;
        
        const normRot = ((deg % 360) + 360) % 360;
        rotationSlider.value = Math.round(normRot);
        
        if (normRot > 90 && normRot < 270) {
            snapBackBtn.classList.add('active');
            snapFrontBtn.classList.remove('active');
            viewIndicator.textContent = "Back View";
            viewIndicator.style.borderLeftColor = "var(--accent-glow)";
        } else {
            snapFrontBtn.classList.add('active');
            snapBackBtn.classList.remove('active');
            viewIndicator.textContent = "Front View";
            viewIndicator.style.borderLeftColor = "var(--accent)";
        }
    }

    // Touch/Mouse Swipe to Rotate
    function initRotationDrag() {
        let isDragging = false;
        let startMouseX = 0;
        let startRotation = 0;
        
        tshirtWrapper.addEventListener('mousedown', (e) => {
            if (e.target.closest('.design-element') || e.target.closest('.rotation-controls-wrapper')) return;
            
            isDragging = true;
            tshirt3dContainer.classList.add('dragging');
            startMouseX = e.clientX;
            startRotation = state.rotation;
            
            stopAutoRotation();
            
            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        });

        tshirtWrapper.addEventListener('touchstart', (e) => {
            if (e.target.closest('.design-element') || e.target.closest('.rotation-controls-wrapper')) return;
            
            isDragging = true;
            tshirt3dContainer.classList.add('dragging');
            startMouseX = e.touches[0].clientX;
            startRotation = state.rotation;
            
            stopAutoRotation();
            
            document.addEventListener('touchmove', onTouchMove, { passive: false });
            document.addEventListener('touchend', onTouchEnd);
        });

        function onMouseMove(e) {
            if (!isDragging) return;
            const deltaX = e.clientX - startMouseX;
            // Scale drag to degrees
            updateRotation(startRotation + deltaX * 0.75);
        }

        function onMouseUp() {
            isDragging = false;
            tshirt3dContainer.classList.remove('dragging');
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        }

        function onTouchMove(e) {
            if (!isDragging) return;
            const deltaX = e.touches[0].clientX - startMouseX;
            updateRotation(startRotation + deltaX * 0.75);
            e.preventDefault();
        }

        function onTouchEnd() {
            isDragging = false;
            tshirt3dContainer.classList.remove('dragging');
            document.removeEventListener('touchmove', onTouchMove);
            document.removeEventListener('touchend', onTouchEnd);
        }
    }

    // 7. Generate Mockup Preview (Side-by-side combined image)
    function generateMockupPreview() {
        return new Promise((resolve, reject) => {
            const canvas = document.createElement('canvas');
            canvas.width = 800;
            canvas.height = 400;
            const ctx = canvas.getContext('2d');

            // Draw slate-dark background
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(0, 0, 800, 400);

            // Fetch SVGs
            const svgFront = document.getElementById('tshirt-svg-container-front').querySelector('svg');
            const svgBack = document.getElementById('tshirt-svg-container-back').querySelector('svg');

            if (!svgFront || !svgBack) {
                reject("T-Shirt SVGs not found");
                return;
            }

            // Prep front SVG
            const svgFrontClone = svgFront.cloneNode(true);
            svgFrontClone.setAttribute('width', '380');
            svgFrontClone.setAttribute('height', '380');
            const frontBase = svgFrontClone.querySelector('#tshirt-base');
            if (frontBase) frontBase.setAttribute('fill', state.color);

            // Prep back SVG
            const svgBackClone = svgBack.cloneNode(true);
            svgBackClone.setAttribute('width', '380');
            svgBackClone.setAttribute('height', '380');
            const backBase = svgBackClone.querySelector('#tshirt-base-back');
            if (backBase) backBase.setAttribute('fill', state.color);

            // Serialize and create URLs
            const frontString = new XMLSerializer().serializeToString(svgFrontClone);
            const frontBlob = new Blob([frontString], { type: 'image/svg+xml;charset=utf-8' });
            const frontURL = (window.URL || window.webkitURL || window).createObjectURL(frontBlob);

            const backString = new XMLSerializer().serializeToString(svgBackClone);
            const backBlob = new Blob([backString], { type: 'image/svg+xml;charset=utf-8' });
            const backURL = (window.URL || window.webkitURL || window).createObjectURL(backBlob);

            const imgFront = new Image();
            const imgBack = new Image();
            
            let loadedCount = 0;
            const onShirtLoaded = () => {
                loadedCount++;
                if (loadedCount === 2) {
                    // Draw front (Left) and back (Right)
                    ctx.drawImage(imgFront, 10, 10, 380, 380);
                    ctx.drawImage(imgBack, 410, 10, 380, 380);
                    
                    (window.URL || window.webkitURL || window).revokeObjectURL(frontURL);
                    (window.URL || window.webkitURL || window).revokeObjectURL(backURL);

                    // Add front/back text labels
                    ctx.font = "bold 13px system-ui, sans-serif";
                    ctx.fillStyle = "rgba(255,255,255,0.4)";
                    ctx.textAlign = "center";
                    ctx.fillText("FRONT VIEW", 200, 380);
                    ctx.fillText("BACK VIEW", 600, 380);

                    // Overlay designs
                    drawDesignsOnCanvas(ctx).then(() => {
                        resolve(canvas.toDataURL('image/jpeg', 0.85));
                    }).catch(err => {
                        console.error("Design overlay failed:", err);
                        resolve(canvas.toDataURL('image/jpeg', 0.85));
                    });
                }
            };

            imgFront.onload = onShirtLoaded;
            imgFront.onerror = () => reject("Failed to load Front T-shirt SVG");
            imgFront.src = frontURL;

            imgBack.onload = onShirtLoaded;
            imgBack.onerror = () => reject("Failed to load Back T-shirt SVG");
            imgBack.src = backURL;
        });
    }

    function drawDesignsOnCanvas(ctx) {
        const promises = [];

        const drawSingleDesign = (faceName, leftOffset) => {
            return new Promise((resolve) => {
                if (state[faceName].designType === 'none' || !state[faceName].designSrc) {
                    resolve();
                    return;
                }

                const designImg = new Image();
                designImg.crossOrigin = 'anonymous';
                designImg.onload = () => {
                    const printX = leftOffset + 380 * 0.31;
                    const printY = 10 + 380 * 0.24;
                    const printW = 380 * 0.38;
                    const printH = 380 * 0.48;

                    const designW = printW * state[faceName].scale;
                    const designH = designW / state[faceName].aspectRatio;

                    const designCenterX = printX + (printW * (state[faceName].x / 100));
                    const designCenterY = printY + (printH * (state[faceName].y / 100));

                    const drawX = designCenterX - (designW / 2);
                    const drawY = designCenterY - (designH / 2);

                    ctx.drawImage(designImg, drawX, drawY, designW, designH);
                    resolve();
                };
                designImg.onerror = () => resolve();
                designImg.src = state[faceName].designSrc;
            });
        };

        promises.push(drawSingleDesign('front', 10));
        promises.push(drawSingleDesign('back', 410));

        return Promise.all(promises);
    }

    // 8. Handle Order Submission
    window.submitOrder = async function(event) {
        event.preventDefault();
        
        const hasFrontDesign = state.front.designType !== 'none' && state.front.designSrc;
        const hasBackDesign = state.back.designType !== 'none' && state.back.designSrc;

        if (!hasFrontDesign && !hasBackDesign) {
            showToast("Please apply a design to either the front or the back of the shirt before ordering!", "error");
            return;
        }

        // Disable button & show spinner
        submitBtn.disabled = true;
        btnSpinner.style.display = 'block';
        showToast("Generating custom 360° mockup...", "success");

        try {
            // Generate combined mockup preview
            const base64Preview = await generateMockupPreview();
            previewImageInput.value = base64Preview;
            
            const formData = new FormData(document.getElementById('order-form'));
            
            // Append front custom file if present
            if (state.front.designType === 'custom') {
                formData.set('custom_design_data', state.front.designSrc);
            }
            
            // Append back custom file if present
            if (state.back.designType === 'custom') {
                formData.set('back_custom_design_data', state.back.designSrc);
            }

            showToast("Submitting order details...", "success");

            const response = await fetch('save_order.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                localStorage.removeItem('zeng_customizer_state_v2'); // Clean saved state on success
                showToast("Order placed successfully!", "success");
                setTimeout(() => {
                    window.location.href = `order_success.php?id=${result.order_id}`;
                }, 1000);
            } else {
                showToast(result.message || "Failed to submit order.", "error");
                submitBtn.disabled = false;
                btnSpinner.style.display = 'none';
            }

        } catch (error) {
            console.error("Order error:", error);
            showToast("An error occurred during order submission.", "error");
            submitBtn.disabled = false;
            btnSpinner.style.display = 'none';
        }
    };

    // 9. Toast Helper
    let toastTimeout;
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast-notification');
        const msg = document.getElementById('toast-message');
        
        toast.className = 'toast';
        toast.classList.add(type);
        msg.textContent = message;
        
        toast.classList.add('show');
        
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});
