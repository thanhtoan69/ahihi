<?php
/**
 * Tests for EEM_Template_Engine class
 */

class Test_EEM_Template_Engine extends EEM_Test_Case {
    
    public function test_render_template() {
        $template_data = [
            'subject' => 'Test Subject',
            'content' => '<p>Hello {{first_name}}!</p>',
            'environmental_theme' => 'eco_friendly'
        ];
        
        $subscriber_data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ];
        
        $rendered = $this->template_engine->render_template('default', $template_data, $subscriber_data);
        
        $this->assertIsString($rendered);
        $this->assertStringContainsString('Hello John!', $rendered);
        $this->assertStringContainsString('Test Subject', $rendered);
        $this->assertStringContainsString('eco-friendly', $rendered); // Theme class
    }
    
    public function test_load_template() {
        $template = $this->template_engine->load_template('default');
        
        $this->assertIsString($template);
        $this->assertStringContainsString('<!DOCTYPE html', $template);
        $this->assertStringContainsString('{{content}}', $template);
        $this->assertStringContainsString('{{unsubscribe_url}}', $template);
    }
    
    public function test_replace_variables() {
        $content = 'Hello {{first_name}} {{last_name}}, your score is {{environmental_score}}!';
        $variables = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'environmental_score' => 92
        ];
        
        $replaced = $this->template_engine->replace_variables($content, $variables);
        
        $this->assertEquals('Hello Jane Smith, your score is 92!', $replaced);
    }
    
    public function test_replace_system_variables() {
        $content = 'Visit {{site_url}} or {{unsubscribe_url}}';
        $subscriber_id = $this->create_test_subscriber('system@example.com');
        
        $replaced = $this->template_engine->replace_system_variables($content, $subscriber_id);
        
        $this->assertStringContainsString(get_site_url(), $replaced);
        $this->assertStringContainsString('unsubscribe', $replaced);
        $this->assertStringNotContainsString('{{site_url}}', $replaced);
        $this->assertStringNotContainsString('{{unsubscribe_url}}', $replaced);
    }
    
    public function test_apply_environmental_theme() {
        $base_styles = 'body { color: black; }';
        
        $eco_styles = $this->template_engine->apply_environmental_theme($base_styles, 'eco_friendly');
        $this->assertStringContainsString('--primary-color: #2e7d32', $eco_styles);
        
        $ocean_styles = $this->template_engine->apply_environmental_theme($base_styles, 'ocean_conservation');
        $this->assertStringContainsString('--primary-color: #1976d2', $ocean_styles);
        
        $forest_styles = $this->template_engine->apply_environmental_theme($base_styles, 'forest_protection');
        $this->assertStringContainsString('--primary-color: #388e3c', $forest_styles);
    }
    
    public function test_inline_css() {
        $html = '<style>p { color: red; }</style><p>Test content</p>';
        
        $inlined = $this->template_engine->inline_css($html);
        
        $this->assertStringContainsString('style="color: red"', $inlined);
        $this->assertStringNotContainsString('<style>', $inlined);
    }
    
    public function test_process_conditional_blocks() {
        $content = '
            {{#if environmental_score > 80}}
            <p>You are an eco champion!</p>
            {{/if}}
            {{#if environmental_score <= 50}}
            <p>You can do better for the environment.</p>
            {{/if}}
        ';
        
        // Test high score
        $high_score_result = $this->template_engine->process_conditional_blocks($content, ['environmental_score' => 85]);
        $this->assertStringContainsString('eco champion', $high_score_result);
        $this->assertStringNotContainsString('can do better', $high_score_result);
        
        // Test low score
        $low_score_result = $this->template_engine->process_conditional_blocks($content, ['environmental_score' => 40]);
        $this->assertStringContainsString('can do better', $low_score_result);
        $this->assertStringNotContainsString('eco champion', $low_score_result);
    }
    
    public function test_add_tracking_pixels() {
        $html = '<html><body><p>Email content</p></body></html>';
        $campaign_id = 123;
        $subscriber_id = 456;
        
        $tracked = $this->template_engine->add_tracking_pixels($html, $campaign_id, $subscriber_id);
        
        $this->assertStringContainsString('eem-tracking-pixel', $tracked);
        $this->assertStringContainsString("campaign_id=$campaign_id", $tracked);
        $this->assertStringContainsString("subscriber_id=$subscriber_id", $tracked);
    }
    
    public function test_add_click_tracking() {
        $html = '<a href="https://example.com">Test Link</a>';
        $campaign_id = 123;
        $subscriber_id = 456;
        
        $tracked = $this->template_engine->add_click_tracking($html, $campaign_id, $subscriber_id);
        
        $this->assertStringContainsString('eem-track-click', $tracked);
        $this->assertStringContainsString(urlencode('https://example.com'), $tracked);
        $this->assertStringContainsString("campaign_id=$campaign_id", $tracked);
    }
    
    public function test_create_template() {
        $template_data = [
            'name' => 'Test Template',
            'content' => '<p>Template content with {{first_name}}</p>',
            'environmental_theme' => 'eco_friendly',
            'type' => 'newsletter'
        ];
        
        $template_id = $this->template_engine->create_template($template_data);
        
        $this->assertIsInt($template_id);
        $this->assertGreaterThan(0, $template_id);
        
        // Verify template creation
        $this->assert_database_has_record('eem_templates', [
            'name' => 'Test Template',
            'type' => 'newsletter'
        ]);
    }
    
    public function test_get_template() {
        // Create test template first
        $template_id = $this->template_engine->create_template([
            'name' => 'Get Test Template',
            'content' => '<p>Get test content</p>',
            'environmental_theme' => 'ocean_conservation'
        ]);
        
        $template = $this->template_engine->get_template($template_id);
        
        $this->assertIsArray($template);
        $this->assertEquals('Get Test Template', $template['name']);
        $this->assertEquals('<p>Get test content</p>', $template['content']);
        $this->assertEquals('ocean_conservation', $template['environmental_theme']);
    }
    
    public function test_update_template() {
        $template_id = $this->template_engine->create_template([
            'name' => 'Update Test',
            'content' => '<p>Original content</p>'
        ]);
        
        $update_data = [
            'name' => 'Updated Template',
            'content' => '<p>Updated content</p>',
            'environmental_theme' => 'forest_protection'
        ];
        
        $result = $this->template_engine->update_template($template_id, $update_data);
        $this->assertTrue($result);
        
        // Verify update
        $updated = $this->template_engine->get_template($template_id);
        $this->assertEquals('Updated Template', $updated['name']);
        $this->assertEquals('<p>Updated content</p>', $updated['content']);
        $this->assertEquals('forest_protection', $updated['environmental_theme']);
    }
    
    public function test_delete_template() {
        $template_id = $this->template_engine->create_template([
            'name' => 'Delete Test',
            'content' => '<p>Will be deleted</p>'
        ]);
        
        $result = $this->template_engine->delete_template($template_id);
        $this->assertTrue($result);
        
        // Verify deletion
        $deleted = $this->template_engine->get_template($template_id);
        $this->assertFalse($deleted);
    }
    
    public function test_get_templates() {
        // Create test templates
        $this->template_engine->create_template(['name' => 'Template 1', 'type' => 'newsletter']);
        $this->template_engine->create_template(['name' => 'Template 2', 'type' => 'promotional']);
        $this->template_engine->create_template(['name' => 'Template 3', 'type' => 'newsletter']);
        
        $templates = $this->template_engine->get_templates();
        $this->assertIsArray($templates);
        $this->assertGreaterThanOrEqual(3, count($templates));
        
        // Test filtering by type
        $newsletters = $this->template_engine->get_templates(['type' => 'newsletter']);
        $this->assertCount(2, $newsletters);
    }
    
    public function test_generate_preview() {
        $template_data = [
            'content' => '<p>Hello {{first_name}}, your eco score is {{environmental_score}}!</p>',
            'environmental_theme' => 'eco_friendly'
        ];
        
        $sample_data = [
            'first_name' => 'Preview',
            'environmental_score' => 88
        ];
        
        $preview = $this->template_engine->generate_preview($template_data, $sample_data);
        
        $this->assertIsString($preview);
        $this->assertStringContainsString('Hello Preview', $preview);
        $this->assertStringContainsString('score is 88', $preview);
        $this->assertStringContainsString('eco-friendly', $preview);
    }
    
    public function test_validate_template() {
        $valid_template = [
            'name' => 'Valid Template',
            'content' => '<p>Valid content</p>',
            'type' => 'newsletter'
        ];
        
        $validation = $this->template_engine->validate_template($valid_template);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Test invalid template
        $invalid_template = [
            'name' => '', // Empty name
            'content' => '', // Empty content
            'type' => 'invalid_type'
        ];
        
        $invalid_validation = $this->template_engine->validate_template($invalid_template);
        $this->assertFalse($invalid_validation['valid']);
        $this->assertNotEmpty($invalid_validation['errors']);
    }
    
    public function test_get_available_variables() {
        $variables = $this->template_engine->get_available_variables();
        
        $this->assertIsArray($variables);
        $this->assertArrayHasKey('subscriber', $variables);
        $this->assertArrayHasKey('system', $variables);
        $this->assertArrayHasKey('environmental', $variables);
        
        // Check subscriber variables
        $subscriber_vars = $variables['subscriber'];
        $this->assertContains('first_name', $subscriber_vars);
        $this->assertContains('last_name', $subscriber_vars);
        $this->assertContains('email', $subscriber_vars);
        
        // Check environmental variables
        $env_vars = $variables['environmental'];
        $this->assertContains('environmental_score', $env_vars);
        $this->assertContains('eco_actions_count', $env_vars);
    }
    
    public function test_get_environmental_themes() {
        $themes = $this->template_engine->get_environmental_themes();
        
        $this->assertIsArray($themes);
        $this->assertArrayHasKey('eco_friendly', $themes);
        $this->assertArrayHasKey('ocean_conservation', $themes);
        $this->assertArrayHasKey('forest_protection', $themes);
        $this->assertArrayHasKey('renewable_energy', $themes);
        $this->assertArrayHasKey('waste_reduction', $themes);
        
        // Check theme structure
        $eco_theme = $themes['eco_friendly'];
        $this->assertArrayHasKey('name', $eco_theme);
        $this->assertArrayHasKey('colors', $eco_theme);
        $this->assertArrayHasKey('description', $eco_theme);
    }
}
