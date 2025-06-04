<?php
/**
 * Sample Data Inserter for Quiz & Challenge System
 * Environmental Data Dashboard Plugin
 */

class Sample_Data_Inserter {
    
    public static function insert_quiz_sample_data() {
        global $wpdb;
        
        // Insert quiz categories
        $categories_table = $wpdb->prefix . 'quiz_categories';
        $questions_table = $wpdb->prefix . 'quiz_questions';
        
        // Check if categories already exist
        $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM $categories_table");
        
        if ($existing_categories == 0) {
            $categories = array(
                array(
                    'category_name' => 'Waste Management',
                    'description' => 'Learn about proper waste sorting and recycling',
                    'icon' => '♻️',
                    'color' => '#4CAF50'
                ),
                array(
                    'category_name' => 'Carbon Footprint',
                    'description' => 'Understanding carbon emissions and reduction',
                    'icon' => '🌱',
                    'color' => '#2196F3'
                ),
                array(
                    'category_name' => 'Energy Conservation',
                    'description' => 'Tips and knowledge about saving energy',
                    'icon' => '⚡',
                    'color' => '#FF9800'
                ),
                array(
                    'category_name' => 'Water Conservation',
                    'description' => 'Water saving techniques and awareness',
                    'icon' => '💧',
                    'color' => '#00BCD4'
                ),
                array(
                    'category_name' => 'Sustainable Living',
                    'description' => 'General sustainability practices',
                    'icon' => '🌍',
                    'color' => '#8BC34A'
                )
            );
            
            foreach ($categories as $category) {
                $wpdb->insert($categories_table, $category);
            }
        }
        
        // Insert sample quiz questions
        $existing_questions = $wpdb->get_var("SELECT COUNT(*) FROM $questions_table");
        
        if ($existing_questions == 0) {
            $questions = array(
                // Waste Management Questions
                array(
                    'category_id' => 1,
                    'question_text' => 'Which of the following items belongs in the recycling bin?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('Pizza box with grease', 'Clean plastic bottle', 'Broken mirror', 'Paper napkins')),
                    'correct_answer' => 'Clean plastic bottle',
                    'explanation' => 'Clean plastic bottles are recyclable. Pizza boxes with grease contaminate recycling, broken mirrors are hazardous waste, and paper napkins are too contaminated.',
                    'difficulty_level' => 'easy',
                    'points' => 10
                ),
                array(
                    'category_id' => 1,
                    'question_text' => 'What does the recycling symbol with number 5 inside mean?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('PET plastic', 'HDPE plastic', 'Polypropylene (PP)', 'Polystyrene')),
                    'correct_answer' => 'Polypropylene (PP)',
                    'explanation' => 'The number 5 inside the recycling symbol indicates Polypropylene (PP) plastic, commonly used for yogurt containers and bottle caps.',
                    'difficulty_level' => 'medium',
                    'points' => 15
                ),
                array(
                    'category_id' => 1,
                    'question_text' => 'How long does it take for a plastic bottle to decompose in nature?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('1-5 years', '10-50 years', '100-450 years', '1000+ years')),
                    'correct_answer' => '100-450 years',
                    'explanation' => 'Plastic bottles take 100-450 years to decompose completely, which is why recycling is so important.',
                    'difficulty_level' => 'medium',
                    'points' => 15
                ),
                
                // Carbon Footprint Questions
                array(
                    'category_id' => 2,
                    'question_text' => 'Which transportation method has the lowest carbon footprint per kilometer?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('Car (gasoline)', 'Bus', 'Bicycle', 'Airplane')),
                    'correct_answer' => 'Bicycle',
                    'explanation' => 'Bicycles produce zero direct emissions and have the lowest overall carbon footprint per kilometer traveled.',
                    'difficulty_level' => 'easy',
                    'points' => 10
                ),
                array(
                    'category_id' => 2,
                    'question_text' => 'What is the average carbon footprint of one kilogram of beef production?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('2-5 kg CO2', '10-15 kg CO2', '25-35 kg CO2', '50-60 kg CO2')),
                    'correct_answer' => '25-35 kg CO2',
                    'explanation' => 'Beef production generates approximately 25-35 kg of CO2 equivalent per kilogram, making it one of the highest carbon footprint foods.',
                    'difficulty_level' => 'hard',
                    'points' => 20
                ),
                
                // Energy Conservation Questions
                array(
                    'category_id' => 3,
                    'question_text' => 'Which appliance typically uses the most electricity in a home?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('Refrigerator', 'Air conditioning', 'Television', 'Washing machine')),
                    'correct_answer' => 'Air conditioning',
                    'explanation' => 'Air conditioning systems typically consume the most electricity in homes, especially during hot weather.',
                    'difficulty_level' => 'easy',
                    'points' => 10
                ),
                array(
                    'category_id' => 3,
                    'question_text' => 'How much energy can you save by switching from incandescent to LED bulbs?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('10-20%', '30-50%', '60-80%', '90-95%')),
                    'correct_answer' => '60-80%',
                    'explanation' => 'LED bulbs use 60-80% less energy than traditional incandescent bulbs and last much longer.',
                    'difficulty_level' => 'medium',
                    'points' => 15
                ),
                
                // Water Conservation Questions
                array(
                    'category_id' => 4,
                    'question_text' => 'How much water does a typical shower use per minute?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('2-5 liters', '8-12 liters', '15-25 liters', '30-40 liters')),
                    'correct_answer' => '8-12 liters',
                    'explanation' => 'A typical shower uses 8-12 liters of water per minute. Installing low-flow showerheads can reduce this significantly.',
                    'difficulty_level' => 'medium',
                    'points' => 15
                ),
                array(
                    'category_id' => 4,
                    'question_text' => 'Which household activity uses the most water daily?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('Cooking and drinking', 'Toilet flushing', 'Showering and bathing', 'Laundry')),
                    'correct_answer' => 'Showering and bathing',
                    'explanation' => 'Showering and bathing typically account for the largest portion of household water use, around 17-25% of total consumption.',
                    'difficulty_level' => 'easy',
                    'points' => 10
                ),
                
                // Sustainable Living Questions
                array(
                    'category_id' => 5,
                    'question_text' => 'What does "upcycling" mean?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('Throwing items away', 'Breaking down materials for recycling', 'Creating new items of higher value from waste', 'Buying more products')),
                    'correct_answer' => 'Creating new items of higher value from waste',
                    'explanation' => 'Upcycling is the process of transforming waste materials into new products of better quality or higher environmental value.',
                    'difficulty_level' => 'easy',
                    'points' => 10
                ),
                array(
                    'category_id' => 5,
                    'question_text' => 'Which certification indicates environmentally responsible forest management?',
                    'question_type' => 'multiple_choice',
                    'options' => json_encode(array('ENERGY STAR', 'FSC (Forest Stewardship Council)', 'USDA Organic', 'Fair Trade')),
                    'correct_answer' => 'FSC (Forest Stewardship Council)',
                    'explanation' => 'The FSC certification ensures that forest products come from responsibly managed forests that provide environmental, social, and economic benefits.',
                    'difficulty_level' => 'medium',
                    'points' => 15
                )
            );
            
            foreach ($questions as $question) {
                $wpdb->insert($questions_table, $question);
            }
        }
    }
    
    public static function insert_challenge_sample_data() {
        global $wpdb;
        
        $challenges_table = $wpdb->prefix . 'env_challenges';
        
        // Check if challenges already exist
        $existing_challenges = $wpdb->get_var("SELECT COUNT(*) FROM $challenges_table");
        
        if ($existing_challenges == 0) {
            $challenges = array(
                array(
                    'challenge_name' => 'Zero Waste Week',
                    'challenge_description' => 'Reduce your household waste to near zero for one week. Track everything you throw away and find alternatives.',
                    'challenge_type' => 'weekly',
                    'difficulty_level' => 'medium',
                    'category' => 'waste',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Reduce daily waste by 80%',
                            'Compost organic waste',
                            'Find reusable alternatives for 5 disposable items',
                            'Document waste reduction strategies'
                        ),
                        'tracking' => array(
                            'daily_waste_weight' => 'number',
                            'compost_started' => 'boolean',
                            'reusable_alternatives' => 'number',
                            'strategies_documented' => 'number'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 500,
                        'badge' => 'Zero Waste Warrior',
                        'achievements' => array('waste_reducer', 'eco_innovator')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    'max_participants' => 100,
                    'is_active' => true
                ),
                array(
                    'challenge_name' => 'Carbon Footprint Reduction',
                    'challenge_description' => 'Reduce your daily carbon footprint by making conscious choices about transportation, energy use, and consumption.',
                    'challenge_type' => 'monthly',
                    'difficulty_level' => 'medium',
                    'category' => 'carbon',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Use public transport or bike 5 days per week',
                            'Reduce energy consumption by 20%',
                            'Choose plant-based meals 3 times per week',
                            'Track daily carbon footprint'
                        ),
                        'tracking' => array(
                            'transport_eco_days' => 'number',
                            'energy_savings_percent' => 'number',
                            'plant_meals_count' => 'number',
                            'daily_carbon_logged' => 'number'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 1000,
                        'badge' => 'Carbon Conscious',
                        'achievements' => array('green_commuter', 'energy_saver', 'climate_champion')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'max_participants' => 50,
                    'is_active' => true
                ),
                array(
                    'challenge_name' => 'Energy Efficiency Master',
                    'challenge_description' => 'Implement energy-saving measures in your home and track the results over two weeks.',
                    'challenge_type' => 'weekly',
                    'difficulty_level' => 'easy',
                    'category' => 'energy',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Switch to LED bulbs in at least 5 fixtures',
                            'Unplug electronics when not in use',
                            'Use natural lighting during day',
                            'Set thermostat 2 degrees lower/higher seasonally'
                        ),
                        'tracking' => array(
                            'led_bulbs_installed' => 'number',
                            'electronics_unplugged_daily' => 'boolean',
                            'natural_light_usage' => 'boolean',
                            'thermostat_adjusted' => 'boolean'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 300,
                        'badge' => 'Energy Saver',
                        'achievements' => array('efficient_home', 'power_conscious')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+14 days')),
                    'max_participants' => 200,
                    'is_active' => true
                ),
                array(
                    'challenge_name' => 'Water Conservation Champion',
                    'challenge_description' => 'Implement water-saving techniques and reduce your household water consumption for one month.',
                    'challenge_type' => 'monthly',
                    'difficulty_level' => 'easy',
                    'category' => 'water',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Install water-saving showerheads or faucet aerators',
                            'Fix any leaky faucets or toilets',
                            'Collect rainwater for garden use',
                            'Reduce shower time by 2 minutes daily'
                        ),
                        'tracking' => array(
                            'water_saving_devices_installed' => 'number',
                            'leaks_fixed' => 'number',
                            'rainwater_collected' => 'boolean',
                            'shower_time_reduced' => 'boolean'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 400,
                        'badge' => 'Water Guardian',
                        'achievements' => array('water_wise', 'conservation_expert')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'max_participants' => 150,
                    'is_active' => true
                ),
                array(
                    'challenge_name' => 'Sustainable Shopping Spree',
                    'challenge_description' => 'Make conscious consumption choices by buying only sustainable, eco-friendly products for two weeks.',
                    'challenge_type' => 'weekly',
                    'difficulty_level' => 'hard',
                    'category' => 'consumption',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Buy only products with minimal packaging',
                            'Choose local and organic products when possible',
                            'Avoid single-use plastics completely',
                            'Research sustainability of brands before purchasing'
                        ),
                        'tracking' => array(
                            'minimal_packaging_purchases' => 'number',
                            'local_organic_purchases' => 'number',
                            'plastic_free_days' => 'number',
                            'brands_researched' => 'number'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 600,
                        'badge' => 'Conscious Consumer',
                        'achievements' => array('sustainable_shopper', 'packaging_warrior', 'local_supporter')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+14 days')),
                    'max_participants' => 75,
                    'is_active' => true
                ),
                array(
                    'challenge_name' => 'Green Commute Challenge',
                    'challenge_description' => 'Use eco-friendly transportation methods for all your trips during one week.',
                    'challenge_type' => 'weekly',
                    'difficulty_level' => 'medium',
                    'category' => 'transport',
                    'requirements' => json_encode(array(
                        'goals' => array(
                            'Walk or bike for trips under 2km',
                            'Use public transport for longer trips',
                            'Carpool when private vehicle is necessary',
                            'Plan efficient routes to combine errands'
                        ),
                        'tracking' => array(
                            'walk_bike_trips' => 'number',
                            'public_transport_uses' => 'number',
                            'carpool_instances' => 'number',
                            'efficient_routes_planned' => 'number'
                        )
                    )),
                    'rewards' => json_encode(array(
                        'points' => 350,
                        'badge' => 'Green Commuter',
                        'achievements' => array('active_traveler', 'public_transport_champion')
                    )),
                    'start_date' => date('Y-m-d H:i:s'),
                    'end_date' => date('Y-m-d H:i:s', strtotime('+7 days')),
                    'max_participants' => 120,
                    'is_active' => true
                )
            );
            
            foreach ($challenges as $challenge) {
                $wpdb->insert($challenges_table, $challenge);
            }
        }
    }
}
