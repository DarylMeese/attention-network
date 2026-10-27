<?php
/**
 * Mobile-First QR Menu Template
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

<div class="attn-menu-root attn-qr-layout <?php echo $theme_class; ?>">
    <header class="attn-qr-header">
        <div class="attn-qr-brand">
            <span class="attn-qr-tag">Digital Table Menu</span>
            <h2 class="attn-qr-title"><?php echo esc_html($menu_data['name'] ?? ''); ?></h2>
            <?php if (!empty($menu_data['description'])) : ?>
                <p class="attn-qr-desc"><?php echo esc_html($menu_data['description']); ?></p>
            <?php endif; ?>
        </div>
    </header>

    <?php if (count($sections) > 1) : ?>
        <div class="attn-qr-categories-scroll">
            <div class="attn-qr-categories-inner">
                <?php foreach ($sections as $idx => $s) : ?>
                    <a href="#attn-qr-sec-<?php echo esc_attr($s['id']); ?>" class="attn-qr-pill <?php echo $idx === 0 ? 'active' : ''; ?>">
                        <?php echo esc_html($s['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <main class="attn-qr-content">
        <?php foreach ($sections as $section) : ?>
            <section class="attn-qr-section" id="attn-qr-sec-<?php echo esc_attr($section['id']); ?>">
                <h3 class="attn-qr-section-title"><?php echo esc_html($section['name']); ?></h3>
                <?php if (!empty($section['description'])) : ?>
                    <p class="attn-qr-section-subtitle"><?php echo esc_html($section['description']); ?></p>
                <?php endif; ?>

                <div class="attn-qr-items">
                    <?php
                    $items = $section['items'] ?? array();
                    foreach ($items as $item) :
                        if (isset($item['is_available']) && !$item['is_available']) {
                            continue;
                        }
                    ?>
                        <div class="attn-qr-card">
                            <div class="attn-qr-card-info">
                                <div class="attn-qr-card-title-row">
                                    <h4 class="attn-qr-item-name"><?php echo esc_html($item['name']); ?></h4>
                                    <?php if ($show_prices) : ?>
                                        <span class="attn-qr-item-price">$<?php echo number_format($item['price_amount'] ?? 0, 2); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($item['description'])) : ?>
                                    <p class="attn-qr-item-desc"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>

                                <div class="attn-qr-badges">
                                    <?php if ($show_calories && !empty($item['calories'])) : ?>
                                        <span class="attn-qr-badge-cal"><?php echo intval($item['calories']); ?> cal</span>
                                    <?php endif; ?>

                                    <?php
                                    if ($show_dietary && !empty($item['dietary_tags']) && is_array($item['dietary_tags'])) :
                                        foreach ($item['dietary_tags'] as $tag) :
                                    ?>
                                            <span class="attn-qr-badge-dietary"><?php echo esc_html($tag); ?></span>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>

                                <?php
                                $options = $item['options'] ?? array();
                                if (!empty($options)) :
                                ?>
                                    <div class="attn-qr-options">
                                        <?php foreach ($options as $opt) : ?>
                                            <span class="attn-qr-option-pill">
                                                + <?php echo esc_html($opt['name']); ?>
                                                <?php if (!empty($opt['price_modifier'])) : ?>
                                                    ($<?php echo number_format($opt['price_modifier'], 2); ?>)
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($item['photo_url']) && $show_images) : ?>
                                <div class="attn-qr-card-media">
                                    <img src="<?php echo esc_url($item['photo_url']); ?>" alt="<?php echo esc_attr($item['name']); ?>" loading="lazy" />
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </main>
</div>
