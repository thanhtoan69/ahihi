/**
 * Waste Classification & AI Features JavaScript
 * Environmental Data Dashboard Plugin
 */

(function($) {
    'use strict';

    // Main waste classification object
    const WasteClassification = {
        
        // Configuration
        config: {
            maxFileSize: 10 * 1024 * 1024, // 10MB
            allowedTypes: ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
            apiEndpoint: envDashboard.ajaxUrl,
            nonce: envDashboard.nonce
        },

        // State management
        state: {
            isProcessing: false,
            currentImage: null,
            cameraStream: null,
            lastClassification: null
        },

        // Initialize the waste classification system
        init: function() {
            this.bindEvents();
            this.initializeElements();
            console.log('Waste Classification System initialized');
        },

        // Bind all event listeners
        bindEvents: function() {
            const self = this;

            // File upload events
            $(document).on('change', '.waste-file-input', function(e) {
                self.handleFileSelect(e);
            });

            // Drag and drop events
            $(document).on('dragover dragenter', '.upload-area', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });

            $(document).on('dragleave dragend', '.upload-area', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });

            $(document).on('drop', '.upload-area', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                self.handleFileDrop(e);
            });

            // Camera events
            $(document).on('click', '.start-camera-btn', function(e) {
                e.preventDefault();
                self.startCamera();
            });

            $(document).on('click', '.capture-btn', function(e) {
                e.preventDefault();
                self.capturePhoto();
            });

            $(document).on('click', '.stop-camera-btn', function(e) {
                e.preventDefault();
                self.stopCamera();
            });

            // Classification events
            $(document).on('click', '.classify-btn', function(e) {
                e.preventDefault();
                self.classifyImage();
            });

            $(document).on('click', '.retry-btn', function(e) {
                e.preventDefault();
                self.resetInterface();
            });

            // Feedback events
            $(document).on('click', '.feedback-btn', function(e) {
                e.preventDefault();
                self.handleFeedbackRating($(this));
            });

            $(document).on('click', '.submit-feedback-btn', function(e) {
                e.preventDefault();
                self.submitFeedback();
            });

            // Upload area click
            $(document).on('click', '.upload-area', function(e) {
                if (!$(e.target).is('button, .btn')) {
                    $(this).find('.waste-file-input').click();
                }
            });
        },

        // Initialize DOM elements
        initializeElements: function() {
            // Add any necessary DOM initialization here
            this.setupProgressElements();
        },

        // Setup progress and UI elements
        setupProgressElements: function() {
            $('.gamification-widget').each(function() {
                const $widget = $(this);
                const progress = $widget.data('progress') || 0;
                $widget.find('.progress-fill').css('width', progress + '%');
            });
        },

        // Handle file selection from input
        handleFileSelect: function(event) {
            const files = event.target.files;
            if (files && files.length > 0) {
                this.processFile(files[0]);
            }
        },

        // Handle file drop
        handleFileDrop: function(event) {
            const files = event.originalEvent.dataTransfer.files;
            if (files && files.length > 0) {
                this.processFile(files[0]);
            }
        },

        // Process selected/dropped file
        processFile: function(file) {
            // Validate file
            if (!this.validateFile(file)) {
                return;
            }

            const self = this;
            const reader = new FileReader();

            reader.onload = function(e) {
                self.displayImagePreview(e.target.result, file.name);
                self.state.currentImage = {
                    data: e.target.result,
                    file: file,
                    name: file.name
                };
            };

            reader.onerror = function() {
                self.showError('Error reading file. Please try again.');
            };

            reader.readAsDataURL(file);
        },

        // Validate uploaded file
        validateFile: function(file) {
            // Check file type
            if (!this.config.allowedTypes.includes(file.type)) {
                this.showError('Please upload a valid image file (JPEG, PNG, or WebP).');
                return false;
            }

            // Check file size
            if (file.size > this.config.maxFileSize) {
                this.showError('File size too large. Please choose an image under 10MB.');
                return false;
            }

            return true;
        },

        // Display image preview
        displayImagePreview: function(imageSrc, fileName) {
            const $container = $('.waste-classification-container');
            
            // Hide upload area, show preview
            $container.find('.upload-area').hide();
            
            let $preview = $container.find('.image-preview');
            if ($preview.length === 0) {
                $preview = $('<div class="image-preview"></div>');
                $container.find('.upload-area').after($preview);
            }

            $preview.html(`
                <img src="${imageSrc}" alt="Preview" class="preview-image">
                <div class="image-info">
                    <p><strong>File:</strong> ${fileName}</p>
                </div>
                <div class="preview-actions">
                    <button type="button" class="btn classify-btn">
                        <i class="fas fa-search"></i> Classify Waste
                    </button>
                    <button type="button" class="btn btn-secondary retry-btn">
                        <i class="fas fa-redo"></i> Choose Different Image
                    </button>
                </div>
            `).show();
        },

        // Start camera capture
        startCamera: function() {
            const self = this;
            
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'environment' // Use rear camera if available
                    } 
                })
                .then(function(stream) {
                    self.state.cameraStream = stream;
                    
                    const $container = $('.waste-classification-container');
                    let $cameraInterface = $container.find('.camera-interface');
                    
                    if ($cameraInterface.length === 0) {
                        $cameraInterface = $('<div class="camera-interface"></div>');
                        $container.find('.camera-controls').after($cameraInterface);
                    }

                    $cameraInterface.html(`
                        <video class="camera-video" autoplay playsinline></video>
                        <canvas class="camera-canvas"></canvas>
                        <div class="camera-actions" style="margin-top: 15px;">
                            <button type="button" class="btn capture-btn">
                                <i class="fas fa-camera"></i> Capture Photo
                            </button>
                            <button type="button" class="btn btn-secondary stop-camera-btn">
                                <i class="fas fa-stop"></i> Stop Camera
                            </button>
                        </div>
                    `).show();

                    const video = $cameraInterface.find('video')[0];
                    video.srcObject = stream;
                })
                .catch(function(error) {
                    console.error('Camera access error:', error);
                    self.showError('Unable to access camera. Please check permissions or use file upload instead.');
                });
            } else {
                this.showError('Camera not supported in this browser. Please use file upload instead.');
            }
        },

        // Capture photo from camera
        capturePhoto: function() {
            const $container = $('.waste-classification-container');
            const video = $container.find('.camera-video')[0];
            const canvas = $container.find('.camera-canvas')[0];

            if (!video || !canvas) {
                this.showError('Camera not ready. Please try again.');
                return;
            }

            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            context.drawImage(video, 0, 0);
            
            const imageData = canvas.toDataURL('image/jpeg', 0.8);
            const fileName = 'camera-capture-' + Date.now() + '.jpg';

            this.stopCamera();
            this.displayImagePreview(imageData, fileName);
            
            this.state.currentImage = {
                data: imageData,
                file: null,
                name: fileName
            };
        },

        // Stop camera stream
        stopCamera: function() {
            if (this.state.cameraStream) {
                this.state.cameraStream.getTracks().forEach(track => track.stop());
                this.state.cameraStream = null;
            }
            $('.camera-interface').hide();
        },

        // Classify the current image using AI
        classifyImage: function() {
            if (!this.state.currentImage) {
                this.showError('No image selected for classification.');
                return;
            }

            if (this.state.isProcessing) {
                return; // Already processing
            }

            this.state.isProcessing = true;
            this.showLoading('Analyzing image with AI...');

            const self = this;
            const imageData = this.state.currentImage.data;

            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'classify_waste_image',
                    nonce: this.config.nonce,
                    image_data: imageData,
                    image_name: this.state.currentImage.name
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    self.handleClassificationResponse(response);
                },
                error: function(xhr, status, error) {
                    console.error('Classification error:', error);
                    self.showError('Classification failed. Please try again.');
                },
                complete: function() {
                    self.state.isProcessing = false;
                    self.hideLoading();
                }
            });
        },

        // Handle classification response from server
        handleClassificationResponse: function(response) {
            if (response.success && response.data) {
                this.displayClassificationResults(response.data);
                this.state.lastClassification = response.data;
                this.updateGamificationData(response.data);
            } else {
                const message = response.data && response.data.message ? 
                    response.data.message : 'Classification failed. Please try again.';
                this.showError(message);
            }
        },

        // Display classification results
        displayClassificationResults: function(data) {
            const $container = $('.waste-classification-container');
            
            let $results = $container.find('.classification-results');
            if ($results.length === 0) {
                $results = $('<div class="classification-results"></div>');
                $container.append($results);
            }

            // Determine confidence level classes
            const confidenceClass = this.getConfidenceClass(data.confidence);
            
            // Format disposal recommendations
            let disposalHtml = '';
            if (data.disposal_recommendations && data.disposal_recommendations.length > 0) {
                disposalHtml = `
                    <div class="disposal-recommendations">
                        <h4 class="disposal-title">
                            <i class="fas fa-recycle"></i> Disposal Recommendations
                        </h4>
                        <ul class="disposal-list">
                            ${data.disposal_recommendations.map(rec => `<li>${rec}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            $results.html(`
                <div class="result-header">
                    <i class="fas fa-check-circle result-icon"></i>
                    <h3 class="result-title">Classification Complete</h3>
                    <span class="confidence-score ${confidenceClass}">
                        ${Math.round(data.confidence * 100)}% Confident
                    </span>
                </div>
                
                <div class="result-category">
                    <strong>Category:</strong> ${data.category}
                </div>
                
                <div class="result-description">
                    ${data.description}
                </div>
                
                ${disposalHtml}
                
                <div class="feedback-section">
                    <h4 class="feedback-title">Was this classification helpful?</h4>
                    <div class="feedback-buttons">
                        <button type="button" class="feedback-btn" data-rating="5">
                            <i class="fas fa-thumbs-up"></i> Very Helpful
                        </button>
                        <button type="button" class="feedback-btn" data-rating="4">
                            <i class="fas fa-smile"></i> Helpful
                        </button>
                        <button type="button" class="feedback-btn" data-rating="3">
                            <i class="fas fa-meh"></i> Okay
                        </button>
                        <button type="button" class="feedback-btn" data-rating="2">
                            <i class="fas fa-frown"></i> Not Helpful
                        </button>
                        <button type="button" class="feedback-btn" data-rating="1">
                            <i class="fas fa-thumbs-down"></i> Wrong
                        </button>
                    </div>
                    <textarea class="feedback-textarea" placeholder="Additional comments (optional)..."></textarea>
                    <button type="button" class="btn submit-feedback-btn">
                        <i class="fas fa-paper-plane"></i> Submit Feedback
                    </button>
                </div>
                
                <div class="result-actions" style="margin-top: 20px; text-align: center;">
                    <button type="button" class="btn retry-btn">
                        <i class="fas fa-redo"></i> Classify Another Image
                    </button>
                </div>
            `).show();

            // Scroll to results
            $results[0].scrollIntoView({ behavior: 'smooth' });
        },

        // Get CSS class for confidence level
        getConfidenceClass: function(confidence) {
            if (confidence >= 0.8) return '';
            if (confidence >= 0.6) return 'confidence-low';
            return 'confidence-very-low';
        },

        // Handle feedback rating selection
        handleFeedbackRating: function($button) {
            $('.feedback-btn').removeClass('active');
            $button.addClass('active');
        },

        // Submit user feedback
        submitFeedback: function() {
            if (!this.state.lastClassification) {
                return;
            }

            const rating = $('.feedback-btn.active').data('rating');
            const comments = $('.feedback-textarea').val().trim();

            if (!rating) {
                this.showError('Please select a rating before submitting feedback.');
                return;
            }

            const self = this;
            
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'submit_classification_feedback',
                    nonce: this.config.nonce,
                    classification_id: this.state.lastClassification.id,
                    rating: rating,
                    comments: comments
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess('Thank you for your feedback!');
                        $('.feedback-section').fadeOut();
                    } else {
                        self.showError('Failed to submit feedback. Please try again.');
                    }
                },
                error: function() {
                    self.showError('Failed to submit feedback. Please try again.');
                }
            });
        },

        // Update gamification data after classification
        updateGamificationData: function(classificationData) {
            $.ajax({
                url: this.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'update_user_gamification',
                    nonce: this.config.nonce,
                    classification_data: JSON.stringify(classificationData)
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Update gamification display
                        WasteClassification.updateGamificationDisplay(response.data);
                    }
                }
            });
        },

        // Update gamification display elements
        updateGamificationDisplay: function(data) {
            const $gamificationWidget = $('.gamification-widget');
            
            if ($gamificationWidget.length > 0 && data) {
                // Update points
                $gamificationWidget.find('.points-display').text(data.total_points + ' points');
                
                // Update level
                $gamificationWidget.find('.level-badge').text('Level ' + data.level);
                
                // Update progress bar
                const progressPercent = (data.level_progress || 0) * 100;
                $gamificationWidget.find('.progress-fill').css('width', progressPercent + '%');
                
                // Update achievements if any new ones
                if (data.new_achievements && data.new_achievements.length > 0) {
                    this.showNewAchievements(data.new_achievements);
                }
            }
        },

        // Show new achievements notification
        showNewAchievements: function(achievements) {
            achievements.forEach(achievement => {
                this.showSuccess(`🏆 New Achievement: ${achievement.name}!`, 5000);
            });
        },

        // Reset interface to initial state
        resetInterface: function() {
            $('.image-preview').hide();
            $('.classification-results').hide();
            $('.camera-interface').hide();
            $('.upload-area').show();
            $('.waste-file-input').val('');
            
            this.state.currentImage = null;
            this.state.lastClassification = null;
            this.stopCamera();
        },

        // Show loading state
        showLoading: function(message) {
            const $container = $('.waste-classification-container');
            
            let $loading = $container.find('.loading-container');
            if ($loading.length === 0) {
                $loading = $('<div class="loading-container" style="text-align: center; margin: 20px 0;"></div>');
                $container.append($loading);
            }

            $loading.html(`
                <div class="loading-spinner"></div>
                <div class="loading-text">${message || 'Processing...'}</div>
            `).show();
        },

        // Hide loading state
        hideLoading: function() {
            $('.loading-container').hide();
        },

        // Show error message
        showError: function(message) {
            this.showNotification(message, 'error');
        },

        // Show success message
        showSuccess: function(message, duration) {
            this.showNotification(message, 'success', duration);
        },

        // Show notification
        showNotification: function(message, type, duration) {
            const $notification = $(`
                <div class="waste-notification ${type}" style="
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: ${type === 'error' ? '#e74c3c' : '#27ae60'};
                    color: white;
                    padding: 15px 20px;
                    border-radius: 8px;
                    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                    z-index: 10000;
                    max-width: 400px;
                    font-weight: 500;
                ">
                    ${message}
                    <button style="
                        background: none;
                        border: none;
                        color: white;
                        float: right;
                        margin-left: 10px;
                        cursor: pointer;
                        font-size: 16px;
                    ">&times;</button>
                </div>
            `);

            $('body').append($notification);

            // Auto-hide notification
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, duration || 4000);

            // Manual close
            $notification.find('button').click(() => {
                $notification.fadeOut(() => $notification.remove());
            });
        }
    };

    // History management
    const HistoryManager = {
        init: function() {
            this.bindEvents();
            this.loadHistory();
        },

        bindEvents: function() {
            $(document).on('click', '.load-more-history', this.loadMoreHistory.bind(this));
            $(document).on('click', '.clear-history-btn', this.clearHistory.bind(this));
        },

        loadHistory: function() {
            const $container = $('.history-list');
            if ($container.length === 0) return;

            $.ajax({
                url: WasteClassification.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'get_classification_history',
                    nonce: WasteClassification.config.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data) {
                        HistoryManager.displayHistory(response.data);
                    }
                }
            });
        },

        displayHistory: function(data) {
            const $container = $('.history-list');
            let html = '';

            if (data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    html += `
                        <div class="history-item">
                            ${item.image_url ? `<img src="${item.image_url}" alt="Classified item" class="history-item-image">` : ''}
                            <div class="history-item-info">
                                <div class="history-item-category">${item.category}</div>
                                <div class="history-item-confidence">Confidence: ${Math.round(item.confidence * 100)}%</div>
                                <div class="history-item-date">${item.date}</div>
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="history-empty">No classification history found.</div>';
            }

            $container.html(html);
        },

        clearHistory: function() {
            if (!confirm('Are you sure you want to clear your classification history?')) {
                return;
            }

            $.ajax({
                url: WasteClassification.config.apiEndpoint,
                type: 'POST',
                data: {
                    action: 'clear_classification_history',
                    nonce: WasteClassification.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('.history-list').html('<div class="history-empty">History cleared.</div>');
                        WasteClassification.showSuccess('History cleared successfully.');
                    }
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on a page with waste classification elements
        if ($('.waste-classification-container').length > 0) {
            WasteClassification.init();
        }
        
        if ($('.history-container').length > 0) {
            HistoryManager.init();
        }

        // Initialize tooltips and other UI enhancements
        $('[data-toggle="tooltip"]').tooltip();
        
        // Animate achievement cards on scroll
        if (typeof IntersectionObserver !== 'undefined') {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            $('.achievement-card').each(function() {
                $(this).css({
                    'opacity': '0',
                    'transform': 'translateY(20px)',
                    'transition': 'opacity 0.6s ease, transform 0.6s ease'
                });
                observer.observe(this);
            });
        }
    });

    // Export for global access
    window.WasteClassification = WasteClassification;
    window.HistoryManager = HistoryManager;

})(jQuery);
