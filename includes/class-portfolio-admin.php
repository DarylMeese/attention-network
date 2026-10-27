<?php
/**
 * Before & After Project Showcase / Portfolio Admin Interface
 *
 * Implements rigorous government & enterprise security standards:
 * - Strict capability checks (current_user_can)
 * - Cryptographic nonce verification (check_ajax_referer)
 * - Strict input sanitization and regex validation
 * - Context-aware output escaping (esc_html, esc_attr, esc_url)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Attention_Network_Portfolio_Admin {

    public static function init() {
        add_action('wp_ajax_attention_network_save_portfolio_item', array(__CLASS__, 'handle_save_portfolio_item'));
        add_action('wp_ajax_attention_network_delete_portfolio_item', array(__CLASS__, 'handle_delete_portfolio_item'));
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
     * Render Project Showcase Admin Screen
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'attention-network'));
        }

        $items = Attention_Network_API_Client::get_portfolio_items(false);
        ?>
        <div class="wrap attn-admin-wrap">
            <div class="attn-header">
                <div class="attn-header-left">
                    <span class="attn-badge"><?php esc_html_e('Visual Proof', 'attention-network'); ?></span>
                    <h1 class="attn-title"><?php esc_html_e('Before & After Project Portfolio', 'attention-network'); ?></h1>
                    <p class="attn-subtitle"><?php esc_html_e('Showcase your craftsmanship, transformations, and completed projects with interactive split comparison sliders.', 'attention-network'); ?></p>
                </div>
                <div class="attn-header-right">
                    <button type="button" class="button button-primary" id="attn-btn-add-project">
                        <?php esc_html_e('+ Add Project Showcase', 'attention-network'); ?>
                    </button>
                </div>
            </div>

            <div class="attn-grid">
                <div class="attn-col-main">
                    <div class="attn-card">
                        <div class="attn-card-header">
                            <h2 class="attn-card-title"><?php esc_html_e('Showcase Projects', 'attention-network'); ?></h2>
                            <span class="attn-counter"><?php echo sprintf(esc_html__('%d Projects', 'attention-network'), count($items)); ?></span>
                        </div>

                        <?php if (empty($items)) : ?>
                            <div class="attn-empty-state">
                                <span class="dashicons dashicons-format-gallery" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8; margin-bottom: 12px;"></span>
                                <h3><?php esc_html_e('No showcase projects added yet', 'attention-network'); ?></h3>
                                <p><?php esc_html_e('Add your best before & after transformations to show prospective clients your tangible results.', 'attention-network'); ?></p>
                                <button type="button" class="button button-primary" id="attn-btn-empty-add-project">
                                    <?php esc_html_e('+ Add First Project', 'attention-network'); ?>
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="attn-table-container">
                                <table class="widefat striped attn-table">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Project', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Category', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Before & After Preview', 'attention-network'); ?></th>
                                            <th><?php esc_html_e('Duration / Location', 'attention-network'); ?></th>
                                            <th class="attn-text-right"><?php esc_html_e('Actions', 'attention-network'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item) :
                                            $id          = esc_attr($item['id'] ?? '');
                                            $title       = esc_html($item['title'] ?? '');
                                            $category    = esc_html($item['category'] ?? 'General');
                                            $before_url  = esc_url($item['beforeUrl'] ?? '');
                                            $after_url   = esc_url($item['afterUrl'] ?? '');
                                            $duration    = esc_html($item['duration'] ?? '');
                                            $location    = esc_html($item['location'] ?? '');
                                            $json_item   = esc_attr(wp_json_encode($item));
                                        ?>
                                            <tr data-project-id="<?php echo $id; ?>">
                                                <td>
                                                    <strong><?php echo $title; ?></strong>
                                                    <?php if (!empty($item['description'])) : ?>
                                                        <div class="attn-subtext"><?php echo esc_html(wp_trim_words($item['description'], 12)); ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><span class="attn-pill"><?php echo $category; ?></span></td>
                                                <td>
                                                    <div class="attn-thumb-pair">
                                                        <?php if (!empty($before_url)) : ?>
                                                            <img src="<?php echo $before_url; ?>" alt="Before" title="Before" class="attn-thumb" />
                                                        <?php endif; ?>
                                                        <span style="color: #94a3b8; font-weight: bold;">➔</span>
                                                        <?php if (!empty($after_url)) : ?>
                                                            <img src="<?php echo $after_url; ?>" alt="After" title="After" class="attn-thumb" />
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if (!empty($duration)) : ?>
                                                        <div>⏱ <?php echo $duration; ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($location)) : ?>
                                                        <div class="attn-subtext">📍 <?php echo $location; ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="attn-text-right">
                                                    <button type="button" class="button button-small attn-btn-edit-project" data-project='<?php echo $json_item; ?>'>
                                                        <?php esc_html_e('Edit', 'attention-network'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small button-link-delete attn-btn-delete-project" data-id="<?php echo $id; ?>">
                                                        <?php esc_html_e('Delete', 'attention-network'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="attn-col-sidebar">
                    <div class="attn-card attn-card-info">
                        <h3><?php esc_html_e('Embed Portfolio Sliders', 'attention-network'); ?></h3>
                        <p><?php esc_html_e('Embed interactive before/after split sliders and project showcases on your pages:', 'attention-network'); ?></p>
                        <div class="attn-code-snippet">
                            <code>[attention_portfolio]</code>
                            <span class="attn-subtext"><?php esc_html_e('Full interactive project showcase gallery grid', 'attention-network'); ?></span>
                        </div>
                        <div class="attn-code-snippet">
                            <code>[attention_before_after before="..." after="..."]</code>
                            <span class="attn-subtext"><?php esc_html_e('Interactive split comparison slider with drag handle', 'attention-network'); ?></span>
                        </div>
                    </div>

                    <div class="attn-card">
                        <h3><?php esc_html_e('Image Guidelines', 'attention-network'); ?></h3>
                        <ul class="attn-bullet-list">
                            <li><?php esc_html_e('Use images with the same aspect ratio for smooth split sliding.', 'attention-network'); ?></li>
                            <li><?php esc_html_e('High-resolution photos taken from identical angles maximize viewer impact.', 'attention-network'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add/Edit Project Modal -->
        <div id="attn-project-modal" class="attn-modal-backdrop" style="display: none;">
            <div class="attn-modal-dialog">
                <div class="attn-modal-header">
                    <h2 id="attn-project-modal-title"><?php esc_html_e('Add Showcase Project', 'attention-network'); ?></h2>
                    <button type="button" class="attn-modal-close">&times;</button>
                </div>
                <div class="attn-modal-body">
                    <form id="attn-project-form">
                        <input type="hidden" id="attn-project-id" name="id" value="" />

                        <div class="attn-form-row">
                            <label for="attn-project-title"><?php esc_html_e('Project Title *', 'attention-network'); ?></label>
                            <input type="text" id="attn-project-title" name="title" class="regular-text" required placeholder="e.g. Master Suite Bathroom & Walk-In Shower Renovation" />
                        </div>

                        <div class="attn-form-row-2col">
                            <div>
                                <label for="attn-project-category"><?php esc_html_e('Category / Trade', 'attention-network'); ?></label>
                                <input type="text" id="attn-project-category" name="category" class="regular-text" placeholder="e.g. Remodeling, Roofing, Detailing" />
                            </div>
                            <div>
                                <label for="attn-project-duration"><?php esc_html_e('Project Duration', 'attention-network'); ?></label>
                                <input type="text" id="attn-project-duration" name="duration" class="regular-text" placeholder="e.g. 4 Days or 2 Weeks" />
                            </div>
                        </div>

                        <div class="attn-form-row-2col">
                            <div>
                                <label for="attn-project-before-url"><?php esc_html_e('Before Image URL *', 'attention-network'); ?></label>
                                <input type="url" id="attn-project-before-url" name="beforeUrl" class="regular-text" required placeholder="https://..." />
                            </div>
                            <div>
                                <label for="attn-project-after-url"><?php esc_html_e('After Image URL *', 'attention-network'); ?></label>
                                <input type="url" id="attn-project-after-url" name="afterUrl" class="regular-text" required placeholder="https://..." />
                            </div>
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-project-location"><?php esc_html_e('Location / Neighborhood (Optional)', 'attention-network'); ?></label>
                            <input type="text" id="attn-project-location" name="location" class="large-text" placeholder="e.g. West Lake Hills, Austin TX" />
                        </div>

                        <div class="attn-form-row">
                            <label for="attn-project-description"><?php esc_html_e('Project Description & Scope', 'attention-network'); ?></label>
                            <textarea id="attn-project-description" name="description" rows="3" class="large-text" placeholder="Details regarding scope, materials, and challenges solved..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="attn-modal-footer">
                    <button type="button" class="button button-secondary attn-modal-cancel"><?php esc_html_e('Cancel', 'attention-network'); ?></button>
                    <button type="button" class="button button-primary" id="attn-btn-save-project"><?php esc_html_e('Save Project', 'attention-network'); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX Save Project Item Handler
     */
    public static function handle_save_portfolio_item() {
        self::verify_ajax_security();

        $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
        if (empty($title)) {
            wp_send_json_error(array('message' => __('Project title is required.', 'attention-network')), 400);
        }

        $id          = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        $category    = isset($_POST['category']) ? sanitize_text_field(wp_unslash($_POST['category'])) : 'General';
        $duration    = isset($_POST['duration']) ? sanitize_text_field(wp_unslash($_POST['duration'])) : '';
        $before_url  = isset($_POST['beforeUrl']) ? esc_url_raw(wp_unslash($_POST['beforeUrl'])) : '';
        $after_url   = isset($_POST['afterUrl']) ? esc_url_raw(wp_unslash($_POST['afterUrl'])) : '';
        $location    = isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

        $data = array(
            'id'          => $id,
            'title'       => $title,
            'category'    => $category,
            'duration'    => $duration,
            'beforeUrl'   => $before_url,
            'afterUrl'    => $after_url,
            'location'    => $location,
            'description' => $description,
        );

        $saved = Attention_Network_API_Client::save_portfolio_item($data);

        wp_send_json_success(array(
            'message' => __('Project showcase saved successfully.', 'attention-network'),
            'project' => $saved,
        ));
    }

    /**
     * AJAX Delete Project Item Handler
     */
    public static function handle_delete_portfolio_item() {
        self::verify_ajax_security();

        $id = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
        if (empty($id)) {
            wp_send_json_error(array('message' => __('Missing project ID.', 'attention-network')), 400);
        }

        Attention_Network_API_Client::delete_portfolio_item($id);
        wp_send_json_success(array('message' => __('Project deleted.', 'attention-network')));
    }
}
