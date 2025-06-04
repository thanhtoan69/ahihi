<?php
/**
 * Receipt Generator Class
 * 
 * Handles tax receipt generation, PDF creation, and email delivery
 * for the Environmental Donation System plugin.
 * 
 * @package EnvironmentalDonationSystem
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class EDS_Receipt_Generator {
    
    /**
     * PDF library instance
     */
    private $pdf_lib;
    
    /**
     * Initialize receipt generator
     */
    public function __construct() {
        add_action('wp_ajax_eds_generate_receipt', array($this, 'ajax_generate_receipt'));
        add_action('wp_ajax_eds_email_receipt', array($this, 'ajax_email_receipt'));
        add_action('wp_ajax_eds_download_receipt', array($this, 'ajax_download_receipt'));
        add_action('wp_ajax_nopriv_eds_download_receipt', array($this, 'ajax_download_receipt'));
        
        // Handle receipt viewing
        add_action('init', array($this, 'handle_receipt_view'));
        
        // Scheduled tasks
        add_action('eds_generate_annual_receipts', array($this, 'generate_annual_receipts'));
        
        $this->init_pdf_library();
    }
    
    /**
     * Initialize PDF library
     */
    private function init_pdf_library() {
        // Check if TCPDF is available
        if (class_exists('TCPDF')) {
            $this->pdf_lib = 'tcpdf';
        } elseif (class_exists('FPDF')) {
            $this->pdf_lib = 'fpdf';
        } else {
            // Include our basic PDF library
            require_once EDS_PLUGIN_PATH . 'includes/lib/class-simple-pdf.php';
            $this->pdf_lib = 'simple';
        }
    }
    
    /**
     * Generate tax receipt
     */
    public function generate_receipt($donation_id) {
        global $wpdb;
        
        // Get donation details
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, c.campaign_title, c.organization_id, o.organization_name, o.organization_address, o.tax_exempt_status
            FROM {$wpdb->prefix}donations d
            LEFT JOIN {$wpdb->prefix}donation_campaigns c ON d.campaign_id = c.campaign_id
            LEFT JOIN {$wpdb->prefix}donation_organizations o ON c.organization_id = o.organization_id
            WHERE d.donation_id = %d",
            $donation_id
        ));
        
        if (!$donation) {
            return new WP_Error('donation_not_found', 'Donation not found');
        }
        
        // Check if donation is eligible for tax receipt
        if (!$this->is_eligible_for_receipt($donation)) {
            return new WP_Error('not_eligible', 'Donation is not eligible for tax receipt');
        }
        
        // Check if receipt already exists
        $existing_receipt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donation_tax_receipts WHERE donation_id = %d",
            $donation_id
        ));
        
        if ($existing_receipt) {
            return $existing_receipt->receipt_id;
        }
        
        // Generate receipt number
        $receipt_number = $this->generate_receipt_number($donation);
        
        // Prepare receipt data
        $receipt_data = array(
            'donation_id' => $donation_id,
            'receipt_number' => $receipt_number,
            'tax_year' => date('Y', strtotime($donation->payment_date)),
            'donor_name' => $donation->donor_name,
            'donor_email' => $donation->donor_email,
            'donor_address' => $this->get_donor_address($donation),
            'organization_name' => $donation->organization_name,
            'organization_address' => $donation->organization_address,
            'organization_tax_id' => $this->get_organization_tax_id($donation->organization_id),
            'donation_amount' => $donation->donation_amount,
            'deductible_amount' => $this->calculate_deductible_amount($donation),
            'currency_code' => $donation->currency_code,
            'donation_date' => $donation->payment_date,
            'receipt_date' => current_time('mysql'),
            'receipt_status' => 'generated',
            'created_by' => get_current_user_id(),
        );
        
        // Insert receipt record
        $result = $wpdb->insert(
            $wpdb->prefix . 'donation_tax_receipts',
            $receipt_data
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create receipt record');
        }
        
        $receipt_id = $wpdb->insert_id;
        
        // Generate PDF
        $pdf_result = $this->generate_pdf_receipt($receipt_id, $receipt_data, $donation);
        
        if (is_wp_error($pdf_result)) {
            return $pdf_result;
        }
        
        // Update receipt with PDF path
        $wpdb->update(
            $wpdb->prefix . 'donation_tax_receipts',
            array('pdf_file_path' => $pdf_result),
            array('receipt_id' => $receipt_id),
            array('%s'),
            array('%d')
        );
        
        // Send email if requested
        if ($donation->tax_receipt_required) {
            $this->email_receipt($receipt_id);
        }
        
        do_action('eds_receipt_generated', $receipt_id, $donation_id);
        
        return $receipt_id;
    }
    
    /**
     * Generate PDF receipt
     */
    private function generate_pdf_receipt($receipt_id, $receipt_data, $donation) {
        $upload_dir = wp_upload_dir();
        $receipts_dir = $upload_dir['basedir'] . '/donation-receipts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($receipts_dir)) {
            wp_mkdir_p($receipts_dir);
        }
        
        $filename = 'receipt-' . $receipt_data['receipt_number'] . '.pdf';
        $filepath = $receipts_dir . $filename;
        
        switch ($this->pdf_lib) {
            case 'tcpdf':
                $result = $this->generate_tcpdf_receipt($filepath, $receipt_data, $donation);
                break;
            case 'fpdf':
                $result = $this->generate_fpdf_receipt($filepath, $receipt_data, $donation);
                break;
            default:
                $result = $this->generate_simple_pdf_receipt($filepath, $receipt_data, $donation);
        }
        
        if ($result) {
            return $upload_dir['baseurl'] . '/donation-receipts/' . $filename;
        }
        
        return new WP_Error('pdf_generation_failed', 'Failed to generate PDF receipt');
    }
    
    /**
     * Generate receipt using TCPDF
     */
    private function generate_tcpdf_receipt($filepath, $receipt_data, $donation) {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Environmental Donation System');
        $pdf->SetTitle('Tax Receipt #' . $receipt_data['receipt_number']);
        $pdf->SetSubject('Donation Tax Receipt');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 12);
        
        // Receipt content
        $html = $this->get_receipt_html($receipt_data, $donation);
        
        // Print the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Save the PDF
        return $pdf->Output($filepath, 'F');
    }
    
    /**
     * Generate receipt using FPDF
     */
    private function generate_fpdf_receipt($filepath, $receipt_data, $donation) {
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        
        // Title
        $pdf->Cell(0, 10, 'TAX RECEIPT', 0, 1, 'C');
        $pdf->Ln(10);
        
        // Receipt content
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Receipt #: ' . $receipt_data['receipt_number'], 0, 1);
        $pdf->Cell(0, 8, 'Date: ' . date('F j, Y', strtotime($receipt_data['receipt_date'])), 0, 1);
        $pdf->Ln(5);
        
        // Donor information
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Donor Information:', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Name: ' . $receipt_data['donor_name'], 0, 1);
        $pdf->Cell(0, 8, 'Email: ' . $receipt_data['donor_email'], 0, 1);
        $pdf->Ln(5);
        
        // Organization information
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Organization:', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, $receipt_data['organization_name'], 0, 1);
        $pdf->Ln(5);
        
        // Donation details
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 8, 'Donation Details:', 0, 1);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 8, 'Amount: ' . $receipt_data['currency_code'] . ' ' . number_format($receipt_data['donation_amount'], 2), 0, 1);
        $pdf->Cell(0, 8, 'Deductible Amount: ' . $receipt_data['currency_code'] . ' ' . number_format($receipt_data['deductible_amount'], 2), 0, 1);
        $pdf->Cell(0, 8, 'Date of Donation: ' . date('F j, Y', strtotime($receipt_data['donation_date'])), 0, 1);
        
        return $pdf->Output($filepath, 'F');
    }
    
    /**
     * Generate simple HTML receipt
     */
    private function generate_simple_pdf_receipt($filepath, $receipt_data, $donation) {
        $html = $this->get_receipt_html($receipt_data, $donation);
        
        // Use DomPDF or similar library here
        // For now, save as HTML file
        $html_filepath = str_replace('.pdf', '.html', $filepath);
        file_put_contents($html_filepath, $html);
        
        return true;
    }
    
    /**
     * Get receipt HTML template
     */
    private function get_receipt_html($receipt_data, $donation) {
        ob_start();
        ?>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .header { text-align: center; margin-bottom: 30px; }
            .receipt-number { font-size: 18px; font-weight: bold; }
            .section { margin-bottom: 20px; }
            .section-title { font-weight: bold; font-size: 14px; margin-bottom: 10px; color: #2c5530; }
            .info-row { margin-bottom: 5px; }
            .amount { font-size: 16px; font-weight: bold; color: #2c5530; }
            .footer { margin-top: 40px; font-size: 10px; color: #666; }
        </style>
        
        <div class="header">
            <h1>TAX RECEIPT</h1>
            <div class="receipt-number">Receipt #: <?php echo esc_html($receipt_data['receipt_number']); ?></div>
            <div>Date: <?php echo date('F j, Y', strtotime($receipt_data['receipt_date'])); ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Organization Information</div>
            <div class="info-row"><strong><?php echo esc_html($receipt_data['organization_name']); ?></strong></div>
            <?php if ($receipt_data['organization_address']): ?>
                <div class="info-row"><?php echo nl2br(esc_html($receipt_data['organization_address'])); ?></div>
            <?php endif; ?>
            <?php if ($receipt_data['organization_tax_id']): ?>
                <div class="info-row">Tax ID: <?php echo esc_html($receipt_data['organization_tax_id']); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <div class="section-title">Donor Information</div>
            <div class="info-row">Name: <?php echo esc_html($receipt_data['donor_name']); ?></div>
            <div class="info-row">Email: <?php echo esc_html($receipt_data['donor_email']); ?></div>
            <?php if ($receipt_data['donor_address']): ?>
                <div class="info-row">Address: <?php echo nl2br(esc_html($receipt_data['donor_address'])); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <div class="section-title">Donation Details</div>
            <div class="info-row">Campaign: <?php echo esc_html($donation->campaign_title); ?></div>
            <div class="info-row">Date of Donation: <?php echo date('F j, Y', strtotime($receipt_data['donation_date'])); ?></div>
            <div class="info-row">Transaction ID: <?php echo esc_html($donation->transaction_id); ?></div>
            <div class="info-row amount">Total Donation: <?php echo esc_html($receipt_data['currency_code']); ?> <?php echo number_format($receipt_data['donation_amount'], 2); ?></div>
            <div class="info-row amount">Tax Deductible Amount: <?php echo esc_html($receipt_data['currency_code']); ?> <?php echo number_format($receipt_data['deductible_amount'], 2); ?></div>
        </div>
        
        <div class="section">
            <div class="section-title">Important Tax Information</div>
            <div class="info-row">This receipt serves as official documentation for tax purposes.</div>
            <div class="info-row">No goods or services were provided in exchange for this donation.</div>
            <div class="info-row">Please consult your tax advisor for deductibility guidelines.</div>
        </div>
        
        <div class="footer">
            <p>Generated by Environmental Donation System on <?php echo date('F j, Y \a\t g:i A'); ?></p>
            <p>This is an official tax receipt. Please retain for your tax records.</p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Email receipt to donor
     */
    public function email_receipt($receipt_id) {
        global $wpdb;
        
        $receipt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donation_tax_receipts WHERE receipt_id = %d",
            $receipt_id
        ));
        
        if (!$receipt) {
            return new WP_Error('receipt_not_found', 'Receipt not found');
        }
        
        // Get email template
        $subject = sprintf('Tax Receipt #%s for Your Donation', $receipt->receipt_number);
        $message = $this->get_receipt_email_template($receipt);
        
        // Prepare attachments
        $attachments = array();
        if ($receipt->pdf_file_path && file_exists(str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $receipt->pdf_file_path))) {
            $attachments[] = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $receipt->pdf_file_path);
        }
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $email_sent = wp_mail($receipt->donor_email, $subject, $message, $headers, $attachments);
        
        if ($email_sent) {
            // Update receipt record
            $wpdb->update(
                $wpdb->prefix . 'donation_tax_receipts',
                array(
                    'email_sent' => 1,
                    'email_sent_date' => current_time('mysql')
                ),
                array('receipt_id' => $receipt_id),
                array('%d', '%s'),
                array('%d')
            );
            
            return true;
        }
        
        return new WP_Error('email_failed', 'Failed to send receipt email');
    }
    
    /**
     * Get receipt email template
     */
    private function get_receipt_email_template($receipt) {
        $template = get_option('eds_receipt_email_template', $this->get_default_email_template());
        
        // Replace placeholders
        $placeholders = array(
            '{donor_name}' => $receipt->donor_name,
            '{receipt_number}' => $receipt->receipt_number,
            '{organization_name}' => $receipt->organization_name,
            '{donation_amount}' => $receipt->currency_code . ' ' . number_format($receipt->donation_amount, 2),
            '{deductible_amount}' => $receipt->currency_code . ' ' . number_format($receipt->deductible_amount, 2),
            '{donation_date}' => date('F j, Y', strtotime($receipt->donation_date)),
            '{receipt_date}' => date('F j, Y', strtotime($receipt->receipt_date)),
            '{tax_year}' => $receipt->tax_year,
        );
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
    
    /**
     * Get default email template
     */
    private function get_default_email_template() {
        return '
        <h2>Thank You for Your Donation!</h2>
        
        <p>Dear {donor_name},</p>
        
        <p>Thank you for your generous donation to {organization_name}. This email contains your official tax receipt.</p>
        
        <h3>Receipt Details:</h3>
        <ul>
            <li><strong>Receipt Number:</strong> {receipt_number}</li>
            <li><strong>Donation Amount:</strong> {donation_amount}</li>
            <li><strong>Tax Deductible Amount:</strong> {deductible_amount}</li>
            <li><strong>Date of Donation:</strong> {donation_date}</li>
            <li><strong>Tax Year:</strong> {tax_year}</li>
        </ul>
        
        <p>Please retain this receipt for your tax records. If you have any questions, please contact us.</p>
        
        <p>Thank you for supporting environmental causes!</p>
        
        <p>Best regards,<br>
        {organization_name}</p>
        ';
    }
    
    /**
     * Generate annual receipts for all donors
     */
    public function generate_annual_receipts($year = null) {
        if (!$year) {
            $year = date('Y') - 1; // Previous year
        }
        
        global $wpdb;
        
        // Get all donors with donations in the specified year
        $donors = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                donor_email,
                donor_name,
                SUM(net_amount) as total_amount,
                COUNT(*) as donation_count
            FROM {$wpdb->prefix}donations 
            WHERE payment_status = 'completed' 
            AND YEAR(payment_date) = %d
            AND tax_receipt_required = 1
            GROUP BY donor_email, donor_name
            HAVING total_amount > 0",
            $year
        ));
        
        $generated_count = 0;
        
        foreach ($donors as $donor) {
            $annual_receipt_id = $this->generate_annual_receipt($donor, $year);
            if (!is_wp_error($annual_receipt_id)) {
                $generated_count++;
            }
        }
        
        return $generated_count;
    }
    
    /**
     * Generate annual summary receipt
     */
    private function generate_annual_receipt($donor, $year) {
        global $wpdb;
        
        // Get all donations for this donor in the year
        $donations = $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, c.campaign_title, o.organization_name
            FROM {$wpdb->prefix}donations d
            LEFT JOIN {$wpdb->prefix}donation_campaigns c ON d.campaign_id = c.campaign_id
            LEFT JOIN {$wpdb->prefix}donation_organizations o ON c.organization_id = o.organization_id
            WHERE d.donor_email = %s 
            AND YEAR(d.payment_date) = %d
            AND d.payment_status = 'completed'
            AND d.tax_receipt_required = 1
            ORDER BY d.payment_date",
            $donor->donor_email,
            $year
        ));
        
        if (empty($donations)) {
            return new WP_Error('no_donations', 'No eligible donations found');
        }
        
        // Create annual receipt data
        $receipt_data = array(
            'donation_id' => null, // Annual receipt
            'receipt_number' => $this->generate_annual_receipt_number($donor, $year),
            'tax_year' => $year,
            'donor_name' => $donor->donor_name,
            'donor_email' => $donor->donor_email,
            'donor_address' => $this->get_donor_address($donations[0]),
            'organization_name' => $donations[0]->organization_name,
            'organization_address' => null, // Will be filled from settings
            'organization_tax_id' => null, // Will be filled from settings
            'donation_amount' => $donor->total_amount,
            'deductible_amount' => $donor->total_amount,
            'currency_code' => $donations[0]->currency_code,
            'donation_date' => null, // Range of dates
            'receipt_date' => current_time('mysql'),
            'receipt_status' => 'generated',
            'created_by' => null,
        );
        
        // Insert annual receipt
        $result = $wpdb->insert(
            $wpdb->prefix . 'donation_tax_receipts',
            $receipt_data
        );
        
        if ($result === false) {
            return new WP_Error('database_error', 'Failed to create annual receipt');
        }
        
        $receipt_id = $wpdb->insert_id;
        
        // Generate PDF for annual receipt
        $pdf_result = $this->generate_annual_pdf_receipt($receipt_id, $receipt_data, $donations);
        
        if (!is_wp_error($pdf_result)) {
            $wpdb->update(
                $wpdb->prefix . 'donation_tax_receipts',
                array('pdf_file_path' => $pdf_result),
                array('receipt_id' => $receipt_id),
                array('%s'),
                array('%d')
            );
        }
        
        return $receipt_id;
    }
    
    /**
     * Handle receipt viewing
     */
    public function handle_receipt_view() {
        if (isset($_GET['eds_action']) && $_GET['eds_action'] === 'view_receipt') {
            $receipt_id = intval($_GET['receipt_id']);
            $this->display_receipt($receipt_id);
            exit;
        }
    }
    
    /**
     * Display receipt in browser
     */
    private function display_receipt($receipt_id) {
        global $wpdb;
        
        $receipt = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donation_tax_receipts WHERE receipt_id = %d",
            $receipt_id
        ));
        
        if (!$receipt) {
            wp_die('Receipt not found');
        }
        
        // Check if user has permission to view this receipt
        if (!$this->can_view_receipt($receipt)) {
            wp_die('Access denied');
        }
        
        // Update download count
        $wpdb->update(
            $wpdb->prefix . 'donation_tax_receipts',
            array(
                'download_count' => $receipt->download_count + 1,
                'last_downloaded' => current_time('mysql')
            ),
            array('receipt_id' => $receipt_id),
            array('%d', '%s'),
            array('%d')
        );
        
        // Serve PDF file if exists
        if ($receipt->pdf_file_path && file_exists(str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $receipt->pdf_file_path))) {
            $filepath = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $receipt->pdf_file_path);
            
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="receipt-' . $receipt->receipt_number . '.pdf"');
            header('Content-Length: ' . filesize($filepath));
            
            readfile($filepath);
        } else {
            // Generate receipt on the fly
            $donation = null;
            if ($receipt->donation_id) {
                $donation = $wpdb->get_row($wpdb->prepare(
                    "SELECT d.*, c.campaign_title FROM {$wpdb->prefix}donations d
                    LEFT JOIN {$wpdb->prefix}donation_campaigns c ON d.campaign_id = c.campaign_id
                    WHERE d.donation_id = %d",
                    $receipt->donation_id
                ));
            }
            
            $html = $this->get_receipt_html((array)$receipt, $donation);
            
            header('Content-Type: text/html; charset=utf-8');
            echo $html;
        }
    }
    
    /**
     * Check if user can view receipt
     */
    private function can_view_receipt($receipt) {
        // Admin can view all receipts
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Logged in user can view their own receipts
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            if ($current_user->user_email === $receipt->donor_email) {
                return true;
            }
        }
        
        // Check if receipt is being accessed with valid token
        if (isset($_GET['token'])) {
            $token = sanitize_text_field($_GET['token']);
            $expected_token = $this->generate_receipt_token($receipt);
            if (hash_equals($expected_token, $token)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate secure token for receipt access
     */
    private function generate_receipt_token($receipt) {
        return wp_hash($receipt->receipt_id . $receipt->donor_email . $receipt->receipt_number);
    }
    
    /**
     * Utility functions
     */
    
    private function is_eligible_for_receipt($donation) {
        return $donation->payment_status === 'completed' && 
               $donation->tax_receipt_required && 
               !empty($donation->donor_name) && 
               !empty($donation->donor_email);
    }
    
    private function generate_receipt_number($donation) {
        $year = date('Y', strtotime($donation->payment_date));
        $sequence = $this->get_next_receipt_sequence($year);
        return sprintf('%d-%04d', $year, $sequence);
    }
    
    private function generate_annual_receipt_number($donor, $year) {
        return sprintf('%d-ANNUAL-%s', $year, substr(md5($donor->donor_email), 0, 8));
    }
    
    private function get_next_receipt_sequence($year) {
        global $wpdb;
        
        $last_sequence = $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(CAST(SUBSTRING_INDEX(receipt_number, '-', -1) AS UNSIGNED)) 
            FROM {$wpdb->prefix}donation_tax_receipts 
            WHERE tax_year = %d AND receipt_number NOT LIKE '%-ANNUAL-%'",
            $year
        ));
        
        return ($last_sequence ?: 0) + 1;
    }
    
    private function get_donor_address($donation) {
        // Try to get address from user profile if logged in
        if ($donation->donor_user_id) {
            $address_parts = array();
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_address_1', true);
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_address_2', true);
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_city', true);
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_state', true);
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_postcode', true);
            $address_parts[] = get_user_meta($donation->donor_user_id, 'billing_country', true);
            
            $address_parts = array_filter($address_parts);
            if (!empty($address_parts)) {
                return implode("\n", $address_parts);
            }
        }
        
        return null;
    }
    
    private function get_organization_tax_id($organization_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT registration_number FROM {$wpdb->prefix}donation_organizations WHERE organization_id = %d",
            $organization_id
        ));
    }
    
    private function calculate_deductible_amount($donation) {
        // For now, assume full amount is deductible
        // This could be customized based on tax regulations
        return $donation->donation_amount;
    }
    
    /**
     * AJAX Handlers
     */
    
    public function ajax_generate_receipt() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $donation_id = intval($_POST['donation_id']);
        $result = $this->generate_receipt($donation_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array(
            'receipt_id' => $result,
            'message' => 'Receipt generated successfully'
        ));
    }
    
    public function ajax_email_receipt() {
        check_ajax_referer('eds_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $receipt_id = intval($_POST['receipt_id']);
        $result = $this->email_receipt($receipt_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success('Receipt emailed successfully');
    }
    
    public function ajax_download_receipt() {
        $receipt_id = intval($_GET['receipt_id']);
        $this->display_receipt($receipt_id);
    }
}
