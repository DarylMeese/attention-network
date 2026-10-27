<?php
/**
 * Taplist / Brewery Board Template
 *
 * Variables provided:
 * @var array $menu_data
 * @var array $display_settings
 * @var string $theme
 * @var bool $show_images
 * @var bool $show_prices
 * @var bool $show_abv
 * @var bool $show_ibu
 */

if (!defined('ABSPATH')) {
    exit;
}

$theme_class = 'attn-theme-' . esc_attr($theme);
$sections = $menu_data['sections'] ?? array();
$tap_index = 1;
?>

<div class="attn-menu-root attn-taplist-layout <?php echo $theme_class; ?>">
    <div class="attn-taplist-header">
        <div class="attn-taplist-title-wrap">
            <span class="attn-taplist-badge">ON TAP & DRAFT</span>
            <h2 class="attn-taplist-title"><?php echo esc_html($menu_data['name'] ?? ''); ?></h2>
            <?php if (!empty($menu_data['description'])) : ?>
                <p class="attn-taplist-subtitle"><?php echo esc_html($menu_data['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="attn-taplist-sections">
        <?php foreach ($sections as $section) : ?>
            <div class="attn-taplist-section">
                <h3 class="attn-taplist-section-title"><?php echo esc_html($section['name']); ?></h3>

                <div class="attn-taplist-table">
                    <div class="attn-taplist-row attn-taplist-row-head">
                        <div class="attn-col-tap">#</div>
                        <div class="attn-col-info">BEER & BREWERY</div>
                        <div class="attn-col-stats">STYLE / ABV</div>
                        <?php if ($show_prices) : ?>
                            <div class="attn-col-pours">POURS / PRICING</div>
                        <?php endif; ?>
                    </div>

                    <?php
                    $items = $section['items'] ?? array();
                    foreach ($items as $item) :
                        if (isset($item['is_available']) && !$item['is_available']) {
                            continue;
                        }
                    ?>
                        <div class="attn-taplist-row attn-taplist-row-item">
                            <div class="attn-col-tap">
                                <span class="attn-tap-num"><?php echo esc_html(str_pad((string)$tap_index++, 2, '0', STR_PAD_LEFT)); ?></span>
                            </div>

                            <div class="attn-col-info">
                                <strong class="attn-beer-name"><?php echo esc_html($item['name']); ?></strong>
                                <?php if (!empty($item['description'])) : ?>
                                    <p class="attn-beer-desc"><?php echo esc_html($item['description']); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="attn-col-stats">
                                <?php
                                $dietary = $item['dietary_tags'] ?? array();
                                if (!empty($dietary) && is_array($dietary)) :
                                    foreach ($dietary as $tag) :
                                ?>
                                        <span class="attn-badge-style"><?php echo esc_html($tag); ?></span>
                                <?php
                                    endforeach;
                                endif;
                                ?>

                                <?php if ($show_abv && !empty($item['calories'])) : ?>
                                    <!-- Using calories or extra metadata for ABV display -->
                                    <span class="attn-badge-abv"><?php echo esc_html(number_format($item['calories'] / 100, 1)); ?>% ABV</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($show_prices) : ?>
                                <div class="attn-col-pours">
                                    <span class="attn-base-price">$<?php echo number_format($item['price_amount'] ?? 0, 2); ?></span>
                                    <?php
                                    $options = $item['options'] ?? array();
                                    if (!empty($options)) :
                                    ?>
                                        <div class="attn-pours-breakdown">
                                            <?php foreach ($options as $opt) : ?>
                                                <span class="attn-pour-size">
                                                    <?php echo esc_html($opt['name']); ?>:
                                                    <strong>$<?php echo number_format(($item['price_amount'] ?? 0) + ($opt['price_modifier'] ?? 0), 2); ?></strong>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
