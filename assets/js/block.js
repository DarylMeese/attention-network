/* eslint-env browser */
/**
 * Gutenberg Block: Attention Network Food & Drink Menu
 */

(function (wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ToggleControl = wp.components.ToggleControl;

    var blockData = window.attnBlockData || { menus: [], i18n: {} };
    var menus = blockData.menus || [];

    // Format menu options for dropdown
    var menuOptions = [
        { label: '— Select a Menu (or Default Active) —', value: '' }
    ];
    menus.forEach(function (m) {
        menuOptions.push({
            label: m.name + (m.is_active ? ' (Active)' : ' (Draft)'),
            value: m.id
        });
    });

    registerBlockType('attention-network/food-menu', {
        title: blockData.i18n.title || 'Attention Food Menu',
        description: blockData.i18n.description || 'Display a live synced food or drink menu from Attention Network.',
        icon: 'food',
        category: 'widgets',
        attributes: {
            menuId: { type: 'string', default: '' },
            layout: { type: 'string', default: '' },
            theme: { type: 'string', default: '' },
            showImages: { type: 'boolean', default: true },
            showPrices: { type: 'boolean', default: true },
        },

        edit: function (props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            var selectedMenuName = 'Default Active Menu';
            menus.forEach(function (m) {
                if (m.id === attributes.menuId) {
                    selectedMenuName = m.name;
                }
            });

            return [
                el(InspectorControls, { key: 'inspector' },
                    el(PanelBody, { title: 'Menu Configuration', initialOpen: true },
                        el(SelectControl, {
                            label: 'Menu',
                            value: attributes.menuId,
                            options: menuOptions,
                            onChange: function (val) { setAttributes({ menuId: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Display Layout',
                            value: attributes.layout,
                            options: [
                                { label: 'Use Menu Default Setting', value: '' },
                                { label: 'Standard Restaurant Cards', value: 'standard' },
                                { label: 'Brewery Taplist Board', value: 'taplist' },
                                { label: 'QR Mobile Quick Menu', value: 'qr' }
                            ],
                            onChange: function (val) { setAttributes({ layout: val }); }
                        }),
                        el(SelectControl, {
                            label: 'Theme Preset',
                            value: attributes.theme,
                            options: [
                                { label: 'Use Menu Default Preset', value: '' },
                                { label: 'Clean Crisp (Light)', value: 'light' },
                                { label: 'Midnight Slate (Dark)', value: 'dark' },
                                { label: 'Warm Amber / Bistro', value: 'amber' },
                                { label: 'Emerald Fresh / Cafe', value: 'emerald' },
                                { label: 'Craft Taproom (High Contrast)', value: 'brewery' }
                            ],
                            onChange: function (val) { setAttributes({ theme: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Dish Photos',
                            checked: attributes.showImages,
                            onChange: function (val) { setAttributes({ showImages: val }); }
                        }),
                        el(ToggleControl, {
                            label: 'Show Prices',
                            checked: attributes.showPrices,
                            onChange: function (val) { setAttributes({ showPrices: val }); }
                        })
                    )
                ),
                el('div', {
                    key: 'preview-card',
                    style: {
                        padding: '24px',
                        background: '#f8fafc',
                        border: '2px dashed #cbd5e1',
                        borderRadius: '12px',
                        textAlign: 'center',
                        color: '#334155'
                    }
                },
                    el('div', { style: { fontSize: '28px', marginBottom: '8px' } }, '🍽️'),
                    el('h4', { style: { margin: '0 0 6px 0', fontSize: '16px', fontWeight: '700' } }, 'Attention Food Menu'),
                    el('p', { style: { margin: 0, fontSize: '13px', color: '#64748b' } },
                        'Selected: ' + selectedMenuName + ' | Layout: ' + (attributes.layout || 'Default') + ' | Theme: ' + (attributes.theme || 'Default')
                    ),
                    el('small', { style: { display: 'block', marginTop: '10px', color: '#94a3b8' } },
                        'This menu block will dynamically render on the live website from Attention Network.'
                    )
                )
            ];
        },

        save: function () {
            // Dynamic block rendered via PHP render_callback
            return null;
        }
    });
})(window.wp);
