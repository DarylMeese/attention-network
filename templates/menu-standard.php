<?php
/**
 * Standard Restaurant Menu Template
 *
 * Variables provided:
 * @var array $menu_data
 * @var array $display_settings
 * @var string $theme
 * @var bool $show_images
 * @var bool $show_prices
 * @var bool $show_calories
 * @var bool $show_dietary
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_class = 'attn-theme-' . esc_attr($theme);
$sections = $menu_data['sections'] ?? array();
?>

<div class="attn-menu-root attn-standard-layout <?php echo $theme_class; ?>">
    <div class="attn-menu-header">
        <h2 class="attn-menu-title"><?php echo esc_html($menu_data['name'] ?? ''); ?></h2>
        <?php if (!empty($menu_data['description'])) : ?>
            <p class="attn-menu-subtitle"><?php echo esc_html($menu_data['description']); ?></p>
        <?php endif; ?>
    </div>

    <?php if (count($sections) > 1) : ?>
        <nav class="attn-section-nav" aria-label="Menu Sections">
            <?php foreach ($sections as $s) : ?>
                <a href="#attn-sec-<?php echo esc_attr($s['id']); ?>" class="attn-sec-link">
                    <?php echo esc_html($s['name']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <div class="attn-menu-sections">
        <?php foreach ($sections as $section) : ?>
            <section class="attn-menu-section" id="attn-sec-<?php echo esc_attr($section['id']); ?>">
                <div class="attn-section-banner">
                    <h3 class="attn-section-heading"><?php echo esc_html($section['name']); ?></h3>
                    <?php if (!empty($section['description'])) : ?>
                        <p class="attn-section-subheading"><?php echo esc_html($section['description']); ?></p>
                    <?php endif; ?>
                </div>

                <div class="attn-items-collection">
                    <?php
                    $items = $section['items'] ?? array();
                    foreach ($items as $item) :
                        if (isset($item['is_available']) && !$item['is_available']) {
                            continue;
                        }
                    ?>
                        <article class="attn-item-entry <?php echo (!empty($item['photo_url']) && $show_images) ? 'has-media' : 'no-media'; ?>">
                            <?php if (!empty($item['photo_url']) && $show_images) : ?>
                                <div class="attn-item-media">
                                    <img src="<?php echo esc_url($item['photo_url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy" />
                                </div>
                            <?php endif; ?>

                            <div class="attn-item-body">
                                <div class="attn-item-headline">
                                    <h4 class="attn-item-title"><?php echo esc_html($item['name']); ?></h4>
                                    <div class="attn-item-dots"></div>
                                    <?php if ($show_prices) : ?>
                                        <span class="attn-item-cost">
                                            $<?php echo number_format($item['price_amount'] ?? 0, 2); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['description'])) : ?>
                                    <p class="attn-item-description"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>

                                <div class="attn-item-tags">
                                    <?php if ($show_calories && !empty($item['calories'])) : ?>
                                        <span class="attn-badge-calorie"><?php echo intval($item['calories']); ?> cal</span>
                                    <?php endif; ?>

                                    <?php
                                    if ($show_dietary && !empty($item['dietary_tags']) && is_array($item['dietary_tags'])) :
                                        foreach ($item['dietary_tags'] as $tag) :
                                    ?>
                                            <span class="attn-badge-dietary"><?php echo esc_html($tag); ?></span>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                                <?php
                                $options = $item['options'] ?? array();
                                if (!empty($options)) :
                                ?>
                                    <div class="attn-item-options">
                                        <span class="attn-options-label"><?php esc_html_e('Customizations:', 'attention-network'); ?></span>
                                        <?php foreach ($options as $opt) : ?>
                                            <span class="attn-opt-chip">
                                                <?php echo esc_html($opt['name']); ?>
                                                <?php if (!empty($opt['price_modifier'])) : ?>
                                                    <em>(<?php echo ($opt['price_modifier'] > 0 ? '+' : '') . '$' . number_format($opt['price_modifier'], 2); ?>)</em>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </div>
</div>
