/**
 * Environmental Platform ACF Admin JavaScript
 * 
 * Handles client-side functionality for ACF fields
 * 
 * @package Environmental_Platform_Core
 * @since 1.0.0 - Phase 30
 */

(function($) {
    'use strict';

    // Initialize when ACF is ready
    if (typeof acf !== 'undefined') {
        acf.add_action('ready', function() {
            initEnvironmentalACF();
        });
        
        acf.add_action('append', function($el) {
            initEnvironmentalACF($el);
        });
    }

    /**
     * Initialize Environmental ACF functionality
     */
    function initEnvironmentalACF($scope) {
        $scope = $scope || $('body');
        
        // Initialize carbon calculator
        initCarbonCalculator($scope);
        
        // Initialize sustainability scorer
        initSustainabilityScorer($scope);
        
        // Initialize conditional logic enhancements
        initConditionalLogic($scope);
        
        // Initialize environmental impact calculator
        initEnvironmentalImpactCalculator($scope);
        
        // Initialize exchange value estimator
        initExchangeValueEstimator($scope);
    }

    /**
     * Carbon Footprint Calculator
     */
    function initCarbonCalculator($scope) {
        $scope.find('[data-name="carbon_footprint_data"] .acf-repeater').each(function() {
            var $repeater = $(this);
            var $totalField = $scope.find('[data-name="total_carbon_impact"] input');
            
            // Calculate total when values change
            $repeater.on('change', 'input[data-name="carbon_amount"]', function() {
                calculateTotalCarbon($repeater, $totalField);
            });
            
            // Initial calculation
            calculateTotalCarbon($repeater, $totalField);
        });
    }
    
    function calculateTotalCarbon($repeater, $totalField) {
        var total = 0;
        
        $repeater.find('input[data-name="carbon_amount"]').each(function() {
            var value = parseFloat($(this).val()) || 0;
            total += value;
        });
        
        if ($totalField.length) {
            $totalField.val(total.toFixed(2));
        }
    }

    /**
     * Sustainability Scorer
     */
    function initSustainabilityScorer($scope) {
        $scope.find('[data-name="sustainability_score"]').each(function() {
            var $scoreField = $(this);
            var $rangeInput = $scoreField.find('input[type="range"]');
            var $numberInput = $scoreField.find('input[type="number"]');
            
            // Sync range and number inputs
            $rangeInput.on('input', function() {
                $numberInput.val($(this).val());
                updateSustainabilityLabel($scoreField, $(this).val());
            });
            
            $numberInput.on('input', function() {
                $rangeInput.val($(this).val());
                updateSustainabilityLabel($scoreField, $(this).val());
            });
            
            // Initial label update
            updateSustainabilityLabel($scoreField, $rangeInput.val());
        });
    }
    
    function updateSustainabilityLabel($field, score) {
        var label = '';
        score = parseInt(score);
        
        if (score >= 9) label = 'Excellent';
        else if (score >= 7) label = 'Good';
        else if (score >= 5) label = 'Average';
        else if (score >= 3) label = 'Poor';
        else label = 'Very Poor';
        
        $field.find('.sustainability-label').remove();
        $field.append('<span class="sustainability-label">' + label + '</span>');
    }

    /**
     * Enhanced Conditional Logic
     */
    function initConditionalLogic($scope) {
        // Environmental impact level conditional logic
        $scope.find('[data-name="environmental_impact_level"]').on('change', function() {
            var level = $(this).val();
            var $actionFields = $scope.find('[data-name="required_actions"]').closest('.acf-field');
            var $urgencyFields = $scope.find('[data-name="urgency_level"]').closest('.acf-field');
            
            if (level === 'critical' || level === 'severe') {
                $actionFields.addClass('acf-conditional-required');
                $urgencyFields.show();
            } else {
                $actionFields.removeClass('acf-conditional-required');
                if (level === 'minimal') {
                    $urgencyFields.hide();
                }
            }
        });
        
        // Exchange type conditional logic
        $scope.find('[data-name="exchange_type"]').on('change', function() {
            var type = $(this).val();
            var $priceField = $scope.find('[data-name="selling_price"]').closest('.acf-field');
            var $wantedField = $scope.find('[data-name="wanted_items"]').closest('.acf-field');
            var $durationField = $scope.find('[data-name="lending_duration"]').closest('.acf-field');
            
            // Hide all conditional fields first
            $priceField.hide();
            $wantedField.hide();
            $durationField.hide();
            
            // Show relevant fields based on type
            switch(type) {
                case 'sell':
                    $priceField.show().addClass('acf-conditional-required');
                    break;
                case 'trade':
                    $wantedField.show().addClass('acf-conditional-required');
                    break;
                case 'lend':
                    $durationField.show().addClass('acf-conditional-required');
                    break;
            }
        });
    }

    /**
     * Environmental Impact Calculator
     */
    function initEnvironmentalImpactCalculator($scope) {
        $scope.find('[data-name="environmental_impact_metrics"] .acf-repeater').each(function() {
            var $repeater = $(this);
            
            $repeater.on('change', 'input[data-name="baseline_value"], input[data-name="target_value"]', function() {
                var $row = $(this).closest('.acf-row');
                var baseline = parseFloat($row.find('input[data-name="baseline_value"]').val()) || 0;
                var target = parseFloat($row.find('input[data-name="target_value"]').val()) || 0;
                
                if (baseline > 0) {
                    var improvement = ((target - baseline) / baseline * 100).toFixed(1);
                    var $improvementField = $row.find('.improvement-percentage');
                    
                    if ($improvementField.length === 0) {
                        $row.append('<div class="improvement-percentage">Improvement: ' + improvement + '%</div>');
                    } else {
                        $improvementField.text('Improvement: ' + improvement + '%');
                    }
                }
            });
        });
    }

    /**
     * Exchange Value Estimator
     */
    function initExchangeValueEstimator($scope) {
        $scope.find('[data-name="item_category"], [data-name="item_condition"]').on('change', function() {
            var $container = $(this).closest('.acf-fields');
            var category = $container.find('[data-name="item_category"]').val();
            var condition = $container.find('[data-name="item_condition"]').val();
            var $valueField = $container.find('[data-name="item_estimated_value"] input');
            
            if (category && condition) {
                var estimatedValue = calculateItemValue(category, condition);
                
                // Show estimated value as suggestion
                var $suggestion = $container.find('.value-suggestion');
                if ($suggestion.length === 0) {
                    $valueField.after('<div class="value-suggestion">Suggested value: $' + estimatedValue + '</div>');
                } else {
                    $suggestion.text('Suggested value: $' + estimatedValue);
                }
            }
        });
    }
    
    function calculateItemValue(category, condition) {
        var baseValues = {
            'electronics': 200,
            'furniture': 150,
            'appliances': 300,
            'tools': 100,
            'clothing': 30,
            'books': 15,
            'sports': 80,
            'toys': 25,
            'other': 50
        };
        
        var conditionMultipliers = {
            'new': 1.0,
            'like_new': 0.8,
            'good': 0.6,
            'fair': 0.4,
            'needs_repair': 0.2
        };
        
        var baseValue = baseValues[category] || 50;
        var multiplier = conditionMultipliers[condition] || 0.5;
        
        return Math.round(baseValue * multiplier);
    }

    /**
     * Initialize field validation
     */
    function initFieldValidation() {
        // Environmental score validation
        $(document).on('change', '[data-name*="environmental_score"] input', function() {
            var value = parseFloat($(this).val());
            if (value < 0 || value > 10) {
                $(this).addClass('acf-error');
                showValidationMessage($(this), 'Environmental score must be between 0 and 10');
            } else {
                $(this).removeClass('acf-error');
                hideValidationMessage($(this));
            }
        });
        
        // Carbon footprint validation
        $(document).on('change', '[data-name*="carbon"] input[type="number"]', function() {
            var value = parseFloat($(this).val());
            if (value < 0) {
                $(this).addClass('acf-error');
                showValidationMessage($(this), 'Carbon values cannot be negative');
            } else {
                $(this).removeClass('acf-error');
                hideValidationMessage($(this));
            }
        });
    }
    
    function showValidationMessage($field, message) {
        hideValidationMessage($field);
        $field.after('<div class="acf-validation-message">' + message + '</div>');
    }
    
    function hideValidationMessage($field) {
        $field.siblings('.acf-validation-message').remove();
    }

    // Initialize on document ready
    $(document).ready(function() {
        initFieldValidation();
        
        // Add custom CSS classes for environmental fields
        $('.acf-field[data-name*="environmental"]').addClass('environmental-field');
        $('.acf-field[data-name*="carbon"]').addClass('carbon-field');
        $('.acf-field[data-name*="sustainability"]').addClass('sustainability-field');
    });

})(jQuery);
