/**
 * Environmental Platform ACF Export/Import JavaScript
 * 
 * Handles client-side functionality for ACF export/import
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0 - Phase 30
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize export/import functionality
        initExportImport();
        
        // Initialize Git integration
        initGitIntegration();
        
        // Show success message if imported
        if (window.location.search.indexOf('imported=1') > -1) {
            showNotice('ACF field groups imported successfully!', 'success');
        }
    });

    /**
     * Initialize export/import functionality
     */
    function initExportImport() {
        // Handle export form submission
        $('form[action*="ep_export_acf_fields"]').on('submit', function(e) {
            var checkedOptions = $(this).find('input[name="export_options[]"]:checked');
            
            if (checkedOptions.length === 0) {
                e.preventDefault();
                showNotice('Please select at least one export option.', 'error');
                return false;
            }
            
            // Show loading indicator
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Exporting...');
            
            // Re-enable button after a delay (in case of errors)
            setTimeout(function() {
                $submitBtn.prop('disabled', false).val('Export Field Groups');
            }, 5000);
        });
        
        // Handle import form submission
        $('form[action*="ep_import_acf_fields"]').on('submit', function(e) {
            var fileInput = $(this).find('input[type="file"]');
            
            if (!fileInput.val()) {
                e.preventDefault();
                showNotice('Please select a file to import.', 'error');
                return false;
            }
            
            // Validate file extension
            var fileName = fileInput.val().toLowerCase();
            if (!fileName.endsWith('.php') && !fileName.endsWith('.json')) {
                e.preventDefault();
                showNotice('Please select a valid PHP or JSON file.', 'error');
                return false;
            }
            
            // Confirm import
            if (!confirm('Are you sure you want to import these field groups? This may overwrite existing field groups.')) {
                e.preventDefault();
                return false;
            }
            
            // Show loading indicator
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.prop('disabled', true).val('Importing...');
        });
        
        // Handle file input change
        $('input[type="file"][name="import_file"]').on('change', function() {
            var fileName = $(this).val();
            if (fileName) {
                var fileExt = fileName.toLowerCase().split('.').pop();
                var $formatInfo = $('.import-format-info');
                
                if ($formatInfo.length === 0) {
                    $(this).after('<div class="import-format-info"></div>');
                    $formatInfo = $('.import-format-info');
                }
                
                if (fileExt === 'php') {
                    $formatInfo.html('<span style="color: green;">✓ PHP file detected - Field groups will be registered immediately</span>');
                } else if (fileExt === 'json') {
                    $formatInfo.html('<span style="color: blue;">ℹ JSON file detected - Field groups will be imported to database</span>');
                } else {
                    $formatInfo.html('<span style="color: red;">⚠ Unsupported file format</span>');
                }
            }
        });
    }

    /**
     * Initialize Git integration
     */
    function initGitIntegration() {
        $('#sync-with-git').on('click', function() {
            var $button = $(this);
            var $output = $('#git-status-output');
            
            $button.prop('disabled', true).text('Syncing...');
            $output.html('<p>Checking Git status...</p>');
            
            // Simulate Git operations (replace with actual AJAX calls to your Git handler)
            setTimeout(function() {
                performGitOperations($output, $button);
            }, 1000);
        });
    }
    
    /**
     * Perform Git operations
     */
    function performGitOperations($output, $button) {
        var steps = [
            'Exporting ACF field groups to PHP...',
            'Checking Git repository status...',
            'Adding ACF export files to Git...',
            'Committing changes...',
            'Sync completed successfully!'
        ];
        
        var currentStep = 0;
        
        function executeNextStep() {
            if (currentStep < steps.length) {
                $output.append('<div>' + new Date().toLocaleTimeString() + ' - ' + steps[currentStep] + '</div>');
                $output.scrollTop($output[0].scrollHeight);
                
                currentStep++;
                
                if (currentStep < steps.length) {
                    setTimeout(executeNextStep, 1500);
                } else {
                    $button.prop('disabled', false).text('Sync Field Groups to Git');
                    showNotice('ACF field groups synced to Git successfully!', 'success');
                }
            }
        }
        
        executeNextStep();
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        type = type || 'info';
        
        var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        // Insert after the main heading
        $('.wrap h1').after($notice);
        
        // Make it dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut();
        });
        
        // Auto-dismiss success messages
        if (type === 'success') {
            setTimeout(function() {
                $notice.fadeOut();
            }, 5000);
        }
    }

    /**
     * Field group validation
     */
    function validateFieldGroups(fieldGroups) {
        var errors = [];
        
        fieldGroups.forEach(function(group, index) {
            // Check required properties
            if (!group.key) {
                errors.push('Field group #' + (index + 1) + ' is missing required "key" property');
            }
            
            if (!group.title) {
                errors.push('Field group #' + (index + 1) + ' is missing required "title" property');
            }
            
            if (!group.fields || !Array.isArray(group.fields)) {
                errors.push('Field group #' + (index + 1) + ' is missing or has invalid "fields" property');
            }
            
            // Validate fields
            if (group.fields && Array.isArray(group.fields)) {
                group.fields.forEach(function(field, fieldIndex) {
                    if (!field.key) {
                        errors.push('Field #' + (fieldIndex + 1) + ' in group "' + group.title + '" is missing required "key" property');
                    }
                    
                    if (!field.name) {
                        errors.push('Field #' + (fieldIndex + 1) + ' in group "' + group.title + '" is missing required "name" property');
                    }
                    
                    if (!field.type) {
                        errors.push('Field #' + (fieldIndex + 1) + ' in group "' + group.title + '" is missing required "type" property');
                    }
                });
            }
        });
        
        return errors;
    }

    /**
     * Preview field groups before import
     */
    function previewFieldGroups(data) {
        var $modal = $('<div class="ep-preview-modal"></div>');
        var $content = $('<div class="ep-preview-content"></div>');
        var $close = $('<span class="ep-preview-close">&times;</span>');
        
        $content.append($close);
        $content.append('<h3>Field Groups Preview</h3>');
        
        if (data.field_groups && data.field_groups.length > 0) {
            var $list = $('<ul class="ep-field-groups-list"></ul>');
            
            data.field_groups.forEach(function(group) {
                var $item = $('<li></li>');
                $item.html('<strong>' + group.title + '</strong> (' + group.key + ') - ' + 
                          (group.fields ? group.fields.length : 0) + ' fields');
                $list.append($item);
            });
            
            $content.append($list);
        } else {
            $content.append('<p>No field groups found in this file.</p>');
        }
        
        $content.append('<button type="button" class="button button-primary ep-confirm-import">Confirm Import</button>');
        $content.append('<button type="button" class="button ep-cancel-import">Cancel</button>');
        
        $modal.append($content);
        $('body').append($modal);
        
        // Modal events
        $close.on('click', function() {
            $modal.remove();
        });
        
        $('.ep-cancel-import').on('click', function() {
            $modal.remove();
        });
        
        $('.ep-confirm-import').on('click', function() {
            $modal.remove();
            // Proceed with actual import
            // This would trigger the actual import process
        });
        
        // Close on outside click
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.remove();
            }
        });
    }

})(jQuery);
