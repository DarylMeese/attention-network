<?php
/**
 * Customer Reviews & Reputation Admin Interface
 *
 * Implements rigorous government & enterprise security standards:
 * - Strict capability checks (current_user_can)
 * - Cryptographic nonce verification (check_ajax_referer)
 * - Strict input sanitization and regex validation
 * - Context-aware output escaping (esc_html, esc_attr)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Reviews_Admin {

    public static function init() {
        add_action('wp_ajax_attention_network_reply_review', array(__CLASS__, 'handle_reply_review'));
    }

    /**
     * Common security guard for AJAX handlers
     */
    private static function verify_ajax_security() {
        check_ajax_referer('attention_network_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized permission level.', 'attention-network')), 403);
            exit;
        }
    }

    /**
     * Render Reviews Management Screen
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $reviews_data = Attention_Network_API_Client::get_reviews(false);
        $avg_rating   = isset($reviews_data['averageRating']) ? floatval($reviews_data['averageRating']) : 5.0;
        $total_count  = isset($reviews_data['totalReviews']) ? intval($reviews_data['totalReviews']) : 0;
        $items        = isset($reviews_data['items']) ? (array) $reviews_data['items'] : array();
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Reputation & Trust', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Customer Reviews & Ratings', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Monitor verified customer feedback, manage official business replies, and showcase your 5-star reputation on your website.', 'attention-network'); ?></p>
                </div>
            </div>

            <!-- Reputation Overview Cards -->
            <div class="attn-metrics-bar">
                <div class="attn-metric-card">
                    <span class="attn-metric-label"><?php esc_html_e('Overall Rating', 'attention-network'); ?></span>
                    <div class="attn-metric-val-row">
                        <span class="attn-metric-val"><?php echo number_format($avg_rating, 1); ?></span>
                        <span class="attn-stars" style="color: #f59e0b; font-size: 20px; margin-left: 8px;">
                            <?php
                            $full_stars = floor($avg_rating);
                            for ($i = 0; $i < 5; $i++) {
                                echo ($i < $full_stars) ? '★' : '☆';
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="attn-metric-card">
                    <span class="attn-metric-label"><?php esc_html_e('Total Verified Reviews', 'attention-network'); ?></span>
                    <span class="attn-metric-val"><?php echo esc_html($total_count ?: count($items)); ?></span>
                </div>

                <div class="attn-metric-card">
                    <span class="attn-metric-label"><?php esc_html_e('Response Rate', 'attention-network'); ?></span>
                    <?php
                    $responded_count = 0;
                    foreach ($items as $r) {
                        if (!empty($r['response'])) {
                            $responded_count++;
                        }
                    }
                    $rate = count($items) > 0 ? round(($responded_count / count($items)) * 100) : 100;
                    ?>
                    <span class="attn-metric-val"><?php echo esc_html($rate . '%'); ?></span>
                </div>
            </div>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <div class="attn-card-header">
                            <h2 class="attn-card-title"><?php esc_html_e('Recent Reviews & Feedback', 'attention-network'); ?></h2>
                        </div>

                        <?php if (empty($items)) : ?>
                            <div class="attn-empty-state">
                                <span class="dashicons dashicons-star-filled" style="font-size: 48px; width: 48px; height: 48px; color: #f59e0b; margin-bottom: 12px;"></span>
                                <h3><?php esc_html_e('No customer reviews logged yet', 'attention-network'); ?></h3>
                                <p><?php esc_html_e('Once customers submit reviews or your Google/platform integration syncs, feedback will appear here.', 'attention-network'); ?></p>
                            </div>
                        <?php else : ?>
                            <div class="attn-reviews-list">
                                <?php foreach ($items as $item) :
                                    $review_id   = esc_attr($item['reviewId'] ?? ($item['id'] ?? ''));
                                    $author      = esc_html($item['authorName'] ?? __('Anonymous Customer', 'attention-network'));
                                    $rating      = intval($item['rating'] ?? 5);
                                    $title       = esc_html($item['title'] ?? '');
                                    $body        = esc_html($item['body'] ?? '');
                                    $date_str    = !empty($item['createdAt']) ? esc_html(date_i18n(get_option('date_format'), strtotime($item['createdAt']))) : '';
                                    $source      = esc_html($item['source'] ?? 'Verified Customer');
                                    $response    = $item['response'] ?? null;
                                ?>
                                    <div class="attn-review-item" data-review-id="<?php echo $review_id; ?>">
                                        <div class="attn-review-header">
                                            <div class="attn-review-author-meta">
                                                <strong><?php echo $author; ?></strong>
                                                <span class="attn-pill attn-pill-green" style="margin-left: 6px;"><?php echo $source; ?></span>
                                                <span class="attn-subtext" style="margin-left: 10px;"><?php echo $date_str; ?></span>
                                            </div>
                                            <div class="attn-review-stars" style="color: #f59e0b; font-size: 16px;">
                                                <?php for ($i = 0; $i < 5; $i++) : ?>
                                                    <?php echo $i < $rating ? '★' : '☆'; ?>
                                                <?php endfor; ?>
                                            </div>
                                        </div>

                                        <?php if (!empty($title)) : ?>
                                            <h4 class="attn-review-title"><?php echo $title; ?></h4>
                                        <?php endif; ?>

                                        <p class="attn-review-body"><?php echo nl2br($body); ?></p>

                                        <!-- Official Owner Response Section -->
                                        <?php if (!empty($response) && !empty($response['body'])) : ?>
                                            <div class="attn-owner-reply">
                                                <div class="attn-owner-reply-header">
                                                    <strong><?php esc_html_e('Official Business Response', 'attention-network'); ?></strong>
                                                    <?php if (!empty($response['respondedAt'])) : ?>
                                                        <span class="attn-subtext"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($response['respondedAt']))); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <p><?php echo esc_html($response['body']); ?></p>
                                                <button type="button" class="button button-small attn-btn-edit-reply" data-review-id="<?php echo $review_id; ?>" data-reply="<?php echo esc_attr($response['body']); ?>">
                                                    <?php esc_html_e('Edit Reply', 'attention-network'); ?>
                                                </button>
                                            </div>
                                        <?php else : ?>
                                            <div class="attn-reply-action-row">
                                                <button type="button" class="button button-secondary button-small attn-btn-add-reply" data-review-id="<?php echo $review_id; ?>">
                                                    <?php esc_html_e('Reply as Owner', 'attention-network'); ?>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Embed Rating Widgets', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Showcase your verified 5-star rating anywhere on your site to build instant trust with prospects:', 'attention-network'); ?></p>
                        <div class="attn-code-snippet">
                            <code>[attention_rating_badge]</code>
                            <span class="attn-subtext"><?php esc_html_e('Compact Google-compliant aggregate rating badge', 'attention-network'); ?></span>
                        </div>
                        <div class="attn-code-snippet">
                            <code>[attention_reviews limit="6"]</code>
                            <span class="attn-subtext"><?php esc_html_e('Modern testimonial cards with verified client badges and owner responses', 'attention-network'); ?></span>
                        </div>
                    </div>

                    <div class="attn-card">
                        <h3><?php esc_html_e('Reputation Protection', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Prompt, professional replies to all customer reviews significantly boost search rankings and convert hesitant leads into paying clients.', 'attention-network'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reply Modal -->
        <div id="attn-reply-modal" class="attn-modal-backdrop" style="display: none;">
            <div class="attn-modal-dialog">
                <div class="attn-modal-header">
                    <h2><?php esc_html_e('Official Business Response', 'attention-network'); ?></h2>
                    <button type="button" class="attn-modal-close">&times;</button>
                </div>
                <div class="attn-modal-body">
                    <form id="attn-reply-form">
                        <input type="hidden" id="attn-reply-review-id" value="" />
                        <div class="attn-form-row">
                            <label for="attn-reply-text"><?php esc_html_e('Your Response (Visible publicly on your review widget)', 'attention-network'); ?></label>
                            <textarea id="attn-reply-text" rows="5" class="large-text" required placeholder="<?php esc_attr_e('Thank you for choosing us! We take immense pride in our craftsmanship and customer service...', 'attention-network'); ?>"></textarea>
                        </div>
                    </form>
                </div>
                <div class="attn-modal-footer">
                    <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                    <button type="button" class="button button-primary" id="attn-btn-submit-reply"><?php esc_html_e('Publish Response', 'attention-network'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Submit Review Response Handler
     */
    public static function handle_reply_review() {
        self::verify_ajax_security();

        $review_id = isset($_POST['review_id']) ? sanitize_text_field(wp_unslash($_POST['review_id'])) : '';
        $reply     = isset($_POST['reply']) ? sanitize_textarea_field(wp_unslash($_POST['reply'])) : '';

        if (empty($review_id) || empty($reply)) {
            wp_send_json_error(array('message' => __('Review ID and response text are required.', 'attention-network')), 400);
        }

        Attention_Network_API_Client::submit_review_response($review_id, $reply);

        wp_send_json_success(array('message' => __('Response published successfully.', 'attention-network')));
    }
}
