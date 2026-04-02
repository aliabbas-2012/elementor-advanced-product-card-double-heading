<?php
/**
 * Plugin Name: Advanced Product Card Elementor Widget
 */

if (!defined('ABSPATH')) exit;

/**
 * Build Image resolution dropdown labels (WordPress registered sizes + Full).
 *
 * @return array<string, string>
 */
function advanced_product_card_get_image_size_options() {
    $subsizes = function_exists('wp_get_registered_image_subsizes') ? wp_get_registered_image_subsizes() : [];
    $intermediate = function_exists('get_intermediate_image_sizes') ? get_intermediate_image_sizes() : [];
    $labels = [];
    $preferred = ['thumbnail', 'medium', 'medium_large', 'large'];
    foreach ($preferred as $slug) {
        if (in_array($slug, $intermediate, true)) {
            $labels[ $slug ] = advanced_product_card_format_image_size_label($slug, $subsizes);
        }
    }
    foreach ($intermediate as $slug) {
        if (!isset($labels[ $slug ])) {
            $labels[ $slug ] = advanced_product_card_format_image_size_label($slug, $subsizes);
        }
    }
    $labels['full'] = 'Full — Original';
    return $labels;
}

/**
 * @param array<string, array{width:int,height:int}> $subsizes
 */
function advanced_product_card_format_image_size_label($slug, $subsizes) {
    $label = ucwords(str_replace('_', ' ', $slug));
    if (isset($subsizes[ $slug ])) {
        $w = (int) $subsizes[ $slug ]['width'];
        $h = (int) $subsizes[ $slug ]['height'];
        if ($w || $h) {
            $label .= sprintf(' — %d × %d', $w, $h);
        }
    }
    return $label;
}

/**
 * @param mixed $size Setting from Elementor select.
 */
function advanced_product_card_resolve_image_size($size) {
    $allowed = array_merge(get_intermediate_image_sizes(), ['full']);
    if (is_string($size) && in_array($size, $allowed, true)) {
        return $size;
    }
    if (in_array('large', $allowed, true)) {
        return 'large';
    }
    if (in_array('full', $allowed, true)) {
        return 'full';
    }
    return ! empty($allowed[0]) ? $allowed[0] : 'full';
}

function register_advanced_product_card($widgets_manager){

    class Advanced_Product_Card extends \Elementor\Widget_Base {

        public function get_name(){ return 'advanced_product_card'; }
        public function get_title(){ return 'Product Card Advanced'; }
        public function get_icon(){ return 'eicon-product'; }
        public function get_categories(){ return ['general']; }

        protected function register_controls(){

            $this->start_controls_section('content_section',[
                'label'=>'Content',
                'tab'=>\Elementor\Controls_Manager::TAB_CONTENT
            ]);

            $this->add_control('image',['label'=>'Image','type'=>\Elementor\Controls_Manager::MEDIA]);
            $this->add_control('image_size',[
                'label' => 'Image Resolution',
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => advanced_product_card_get_image_size_options(),
                'default' => 'large',
                'description' => 'Which WordPress image size to use for src and srcset (media library images only).',
                'condition' => [
                    'image[id]!' => '',
                ],
            ]);
            $this->add_control('banner_text',['label'=>'Corner Banner Text','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'SALE']);
            $this->add_control('title',['label'=>'Title','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Product Title']);
            $this->add_control('show_divider',['label'=>'Show Divider','type'=>\Elementor\Controls_Manager::SWITCHER,'default'=>'yes']);
            $this->add_control('caption',['label'=>'Caption','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'Short description']);
            $this->add_control('price',['label'=>'Price','type'=>\Elementor\Controls_Manager::TEXT,'default'=>'$49']);
            $this->add_control('bottom_caption',[
                'label'=>'Bottom Caption (Optional)',
                'type'=>\Elementor\Controls_Manager::TEXTAREA,
                'default'=>''
            ]);
            $this->add_control('card_link',[
                'label'=>'Card Link',
                'type'=>\Elementor\Controls_Manager::URL,
                'placeholder'=>'https://example.com',
                'show_external'=>false
            ]);
            $this->add_control('card_link_new_tab',[
                'label'=>'Open In New Tab',
                'type'=>\Elementor\Controls_Manager::SWITCHER,
                'default'=>'yes',
                'condition'=>['card_link[url]!'=>'']
            ]);
            $this->add_control('layout',['label'=>'Caption / Price Layout','type'=>\Elementor\Controls_Manager::CHOOSE,
                'options'=>[
                    'row'=>['title'=>'Left - Right','icon'=>'eicon-h-align-stretch'],
                    'column'=>['title'=>'Stack','icon'=>'eicon-v-align-stretch']
                ],
                'default'=>'row'
            ]);
            $this->add_control('stack_caption_price',[
                'label'=>'Stack Caption / Price (Top-Bottom)',
                'type'=>\Elementor\Controls_Manager::SWITCHER,
                'default'=>'no',
                'return_value'=>'yes'
            ]);
            $this->end_controls_section();

            // CARD STYLE
            $this->start_controls_section('card_style',['label'=>'Card','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->start_controls_tabs('card_tabs');

            $this->start_controls_tab('card_normal',['label'=>'Normal']);
            $this->add_control('card_bg',['label'=>'Background','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-card'=>'background:{{VALUE}}']]);
            $this->end_controls_tab();

            $this->start_controls_tab('card_hover',['label'=>'Hover']);
            $this->add_control('card_bg_hover',['label'=>'Hover Background','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-card:hover'=>'background:{{VALUE}}']]);
            $this->end_controls_tab();

            $this->end_controls_tabs();
            $this->add_group_control(\Elementor\Group_Control_Border::get_type(),['name'=>'card_border','selector'=>'{{WRAPPER}} .pc-card']);
            $this->add_responsive_control('card_padding',['label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-card'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->end_controls_section();

            // IMAGE BOX STYLE
            $this->start_controls_section('image_box_style',['label'=>'Image Box','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_control('img_box_bg',['label'=>'Background','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-image-box'=>'background:{{VALUE}}']]);
            $this->add_group_control(\Elementor\Group_Control_Border::get_type(),['name'=>'img_box_border','selector'=>'{{WRAPPER}} .pc-image-box']);
            $this->add_responsive_control('img_box_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-image-box'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->add_responsive_control('img_box_padding',['label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-image-box'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->add_responsive_control('img_box_height',['label'=>'Height','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>['px','%','vh'],'range'=>['px'=>['min'=>50,'max'=>1000]],'selectors'=>['{{WRAPPER}} .pc-image-box'=>'height:{{SIZE}}{{UNIT}};']]);
            $this->end_controls_section();

            // IMAGE STYLE + SHAPES
            $this->start_controls_section('image_style',['label'=>'Image','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_control('img_fit',['label'=>'Fit','type'=>\Elementor\Controls_Manager::SELECT,'options'=>['cover'=>'Cover','contain'=>'Contain','auto'=>'Auto'],'default'=>'cover']);
            $this->add_control('img_align',['label'=>'Align','type'=>\Elementor\Controls_Manager::SELECT,'options'=>['center'=>'Center','top'=>'Top','bottom'=>'Bottom','left'=>'Left','right'=>'Right'],'default'=>'center']);

            // Shape select including parallelogram
            $this->add_control(
                'img_geom_shape',
                [
                    'label' => 'Shape',
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'none' => 'None',
                        'circle' => 'Circle',
                        'ellipse' => 'Ellipse',
                        'triangle' => 'Triangle',
                        'diamond' => 'Diamond',
                        'hexagon' => 'Hexagon',
                        'pentagon' => 'Pentagon',
                        'star' => 'Star',
                        'parallelogram' => 'Parallelogram',
                        'custom' => 'Custom'
                    ],
                    'default' => 'none'
                ]
            );

            // Custom clip-path
            $this->add_control('img_custom_clip',['label'=>'Custom Clip-Path','type'=>\Elementor\Controls_Manager::TEXT,'placeholder'=>'polygon(...)','condition'=>['img_geom_shape'=>'custom']]);

            // Parallelogram direction + lean amount
            $this->add_control(
                'para_direction',
                [
                    'label' => 'Parallelogram Direction',
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'right-top' => [
                            'title' => 'Right Top (+)',
                            'icon' => 'eicon-arrow-right'
                        ],
                        'left-top' => [
                            'title' => 'Left Top (-)',
                            'icon' => 'eicon-arrow-left'
                        ],
                    ],
                    'default' => 'right-top',
                    'toggle' => false,
                    'condition' => ['img_geom_shape' => 'parallelogram']
                ]
            );
            $this->add_control(
                'para_lean',
                [
                    'label' => 'Parallelogram Lean',
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units'=>['%'],
                    'range'=>['%'=>['min'=>0,'max'=>50]],
                    'default'=>['size'=>20],
                    'condition'=>['img_geom_shape'=>'parallelogram']
                ]
            );

            $this->end_controls_section();

            // BANNER STYLE
            $this->start_controls_section('banner_style',['label'=>'Corner Banner','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'banner_typo','selector'=>'{{WRAPPER}} .pc-banner']);
            $this->add_control('banner_color',['label'=>'Text Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-banner'=>'color:{{VALUE}}']]);
            $this->add_control('banner_bg',['label'=>'Background Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-banner'=>'background-color:{{VALUE}};']]);
            $this->add_responsive_control('banner_padding',['label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>['{{WRAPPER}} .pc-banner'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $this->add_responsive_control('banner_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>['px','em','%'],'selectors'=>['{{WRAPPER}} .pc-banner'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $this->add_group_control(\Elementor\Group_Control_Border::get_type(),['name'=>'banner_border','selector'=>'{{WRAPPER}} .pc-banner']);
            $this->add_responsive_control('banner_radius',['label'=>'Border Radius','type'=>\Elementor\Controls_Manager::DIMENSIONS,'size_units'=>['px','%','em'],'selectors'=>['{{WRAPPER}} .pc-banner'=>'border-radius:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};']]);
            $this->add_control('banner_position',['label'=>'Position','type'=>\Elementor\Controls_Manager::SELECT,'options'=>['top-right'=>'Top Right','top-left'=>'Top Left','bottom-right'=>'Bottom Right','bottom-left'=>'Bottom Left'],'default'=>'top-right']);
            $this->end_controls_section();

            // TITLE STYLE
            $this->start_controls_section('title_style',['label'=>'Title','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'title_typo','selector'=>'{{WRAPPER}} .pc-title']);
            $this->add_control('title_color',['label'=>'Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-title'=>'color:{{VALUE}}']]);
            $this->add_responsive_control('title_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-title'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->end_controls_section();

            // DIVIDER STYLE
            $this->start_controls_section('divider_style',['label'=>'Divider','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_control('divider_style',[
                'label'=>'Style',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[
                    'none'=>'None',
                    'solid'=>'Solid',
                    'dashed'=>'Dashed',
                    'dotted'=>'Dotted',
                    'double'=>'Double',
                    'groove'=>'Groove',
                    'ridge'=>'Ridge',
                    'inset'=>'Inset',
                    'outset'=>'Outset',
                ],
                'default'=>'solid',
                'selectors'=>['{{WRAPPER}} .pc-divider'=>'border-top-style:{{VALUE}}']
            ]);
            $this->add_control('divider_color',['label'=>'Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-divider'=>'border-color:{{VALUE}}']]);
            $this->add_responsive_control('divider_thickness',[
                'label'=>'Thickness',
                'type'=>\Elementor\Controls_Manager::SLIDER,
                'size_units'=>['px'],
                'range'=>['px'=>['min'=>1,'max'=>20]],
                'default'=>['size'=>2],
                'selectors'=>['{{WRAPPER}} .pc-divider'=>'border-top-width:{{SIZE}}px;']
            ]);
            $this->add_responsive_control('divider_width',['label'=>'Width','type'=>\Elementor\Controls_Manager::SLIDER,'default'=>['size'=>100],'selectors'=>['{{WRAPPER}} .pc-divider'=>'width:{{SIZE}}%']]);
            $this->add_control('divider_align',['label'=>'Alignment','type'=>\Elementor\Controls_Manager::CHOOSE,'options'=>['left'=>['title'=>'Left','icon'=>'eicon-text-align-left'],'center'=>['title'=>'Center','icon'=>'eicon-text-align-center'],'right'=>['title'=>'Right','icon'=>'eicon-text-align-right']],'default'=>'center','selectors'=>['{{WRAPPER}} .pc-divider'=>'margin-left:auto;margin-right:auto']]);
            $this->end_controls_section();

            // CAPTION & PRICE STYLE
            $this->start_controls_section('text_style',['label'=>'Caption & Price','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'caption_typo','selector'=>'{{WRAPPER}} .pc-caption']);
            $this->add_control('caption_color',['label'=>'Caption Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-caption'=>'color:{{VALUE}};']]);
            $this->add_responsive_control('caption_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-caption'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'price_typo','selector'=>'{{WRAPPER}} .pc-price']);
            $this->add_control('price_color',['label'=>'Price Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-price'=>'color:{{VALUE}};']]);
            $this->add_responsive_control('price_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-price'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->end_controls_section();

            // BOTTOM CAPTION STYLE
            $this->start_controls_section('bottom_caption_style',['label'=>'Bottom Caption','tab'=>\Elementor\Controls_Manager::TAB_STYLE]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),['name'=>'bottom_caption_typo','selector'=>'{{WRAPPER}} .pc-bottom-caption']);
            $this->add_control('bottom_caption_color',['label'=>'Text Color','type'=>\Elementor\Controls_Manager::COLOR,'selectors'=>['{{WRAPPER}} .pc-bottom-caption'=>'color:{{VALUE}};']]);
            $this->add_responsive_control('bottom_caption_margin',['label'=>'Margin','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-bottom-caption'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
            $this->add_responsive_control('bottom_caption_padding',['label'=>'Padding','type'=>\Elementor\Controls_Manager::DIMENSIONS,'selectors'=>['{{WRAPPER}} .pc-bottom-caption'=>'padding:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);

            $this->add_control('bottom_before_heading',['label'=>'Before Border','type'=>\Elementor\Controls_Manager::HEADING,'separator'=>'before']);
            $this->add_control('bottom_before_enable',['label'=>'Show Before Border','type'=>\Elementor\Controls_Manager::SWITCHER,'default'=>'no']);
            $this->add_control('bottom_before_style',['label'=>'Before Border Style','type'=>\Elementor\Controls_Manager::SELECT,'options'=>['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double'],'default'=>'solid','condition'=>['bottom_before_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::before'=>'border-top-style:{{VALUE}};']]);
            $this->add_control('bottom_before_color',['label'=>'Before Border Color','type'=>\Elementor\Controls_Manager::COLOR,'condition'=>['bottom_before_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::before'=>'border-top-color:{{VALUE}};']]);
            $this->add_responsive_control('bottom_before_width',['label'=>'Before Border Width (%)','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>['%'],'range'=>['%'=>['min'=>10,'max'=>100]],'default'=>['size'=>100], 'condition'=>['bottom_before_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::before'=>'width:{{SIZE}}%;']]);
            $this->add_responsive_control('bottom_before_thickness',['label'=>'Before Border Thickness','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>1,'max'=>12]],'default'=>['size'=>1], 'condition'=>['bottom_before_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::before'=>'border-top-width:{{SIZE}}px;']]);

            $this->add_control('bottom_after_heading',['label'=>'After Border','type'=>\Elementor\Controls_Manager::HEADING,'separator'=>'before']);
            $this->add_control('bottom_after_enable',['label'=>'Show After Border','type'=>\Elementor\Controls_Manager::SWITCHER,'default'=>'no']);
            $this->add_control('bottom_after_style',['label'=>'After Border Style','type'=>\Elementor\Controls_Manager::SELECT,'options'=>['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted','double'=>'Double'],'default'=>'solid','condition'=>['bottom_after_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::after'=>'border-top-style:{{VALUE}};']]);
            $this->add_control('bottom_after_color',['label'=>'After Border Color','type'=>\Elementor\Controls_Manager::COLOR,'condition'=>['bottom_after_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::after'=>'border-top-color:{{VALUE}};']]);
            $this->add_responsive_control('bottom_after_width',['label'=>'After Border Width (%)','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>['%'],'range'=>['%'=>['min'=>10,'max'=>100]],'default'=>['size'=>100], 'condition'=>['bottom_after_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::after'=>'width:{{SIZE}}%;']]);
            $this->add_responsive_control('bottom_after_thickness',['label'=>'After Border Thickness','type'=>\Elementor\Controls_Manager::SLIDER,'size_units'=>['px'],'range'=>['px'=>['min'=>1,'max'=>12]],'default'=>['size'=>1], 'condition'=>['bottom_after_enable'=>'yes'],'selectors'=>['{{WRAPPER}} .pc-bottom-caption::after'=>'border-top-width:{{SIZE}}px;']]);
            $this->end_controls_section();
        }

        protected function render(){
            $s = $this->get_settings_for_display();

            // IMAGE CLIP-PATH LOGIC
            $clip = '';
            switch($s['img_geom_shape']){
                case 'circle': $clip='circle(50% at 50% 50%)'; break;
                case 'ellipse': $clip='ellipse(50% 40% at 50% 50%)'; break;
                case 'triangle': $clip='polygon(50% 0%,0% 100%,100% 100%)'; break;
                case 'diamond': $clip='polygon(50% 0%,100% 50%,50% 100%,0% 50%)'; break;
                case 'hexagon': $clip='polygon(25% 0%,75% 0%,100% 50%,75% 100%,25% 100%,0% 50%)'; break;
                case 'pentagon': $clip='polygon(50% 0%,100% 38%,82% 100%,18% 100%,0% 38%)'; break;
                case 'star': $clip='polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%)'; break;
                case 'parallelogram':
                    $x = isset($s['para_lean']['size']) ? intval($s['para_lean']['size']) : 20;
                    // Backward compatibility for older saved data that still stores signed para_offset.
                    if (isset($s['para_offset']['size'])) {
                        $legacy = intval($s['para_offset']['size']);
                        if ($legacy < 0) {
                            $s['para_direction'] = 'left-top';
                            $x = abs($legacy);
                        } else {
                            $s['para_direction'] = 'right-top';
                            $x = $legacy;
                        }
                    }
                    if (($s['para_direction'] ?? 'right-top') === 'left-top') {
                        $clip="polygon(0% 0%,".(100-$x)."% 0%,100% 100%,{$x}% 100%)";
                    } else {
                        $clip="polygon({$x}% 0%,100% 0%,".(100-$x)."% 100%,0% 100%)";
                    }
                    break;
                case 'custom': $clip=$s['img_custom_clip']; break;
                default: $clip='none';
            }

            $img_style = sprintf(
                'width:100%%;height:100%%;display:block;object-fit:%s;object-position:%s;clip-path:%s;',
                $s['img_fit'],
                $s['img_align'],
                $clip
            );
            $image_id = !empty($s['image']['id']) ? intval($s['image']['id']) : 0;
            $image_alt = '';
            if ($image_id) {
                $image_alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
            }
            if ($image_alt === '') {
                $image_alt = trim((string) ($s['title'] ?? ''));
            }
            $layout_class = (($s['stack_caption_price'] ?? '') === 'yes') ? 'column' : ($s['layout'] ?? 'row');
            $card_link = isset($s['card_link']['url']) ? trim($s['card_link']['url']) : '';
            $open_in_new_tab = (($s['card_link_new_tab'] ?? '') === 'yes');

            $image_size = advanced_product_card_resolve_image_size($s['image_size'] ?? 'large');

            // Responsive srcset/sizes via core when we have a media library attachment ID.
            $responsive_img_html = '';
            if ($image_id && wp_attachment_is_image($image_id)) {
                $responsive_img_html = wp_get_attachment_image(
                    $image_id,
                    $image_size,
                    false,
                    [
                        'style'         => $img_style,
                        'loading'       => 'lazy',
                        'fetchpriority' => 'high',
                        'decoding'      => 'async',
                        'class'         => 'pc-card-img',
                        'alt'           => $image_alt,
                    ]
                );
            }
            ?>
            <?php if ($card_link !== '') : ?>
                <a class="pc-card-link" href="<?php echo esc_url($card_link); ?>"<?php echo $open_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
            <?php endif; ?>
            <div class="pc-card">

                <div class="pc-image-box">
                    <div class="pc-image">
                        <?php
                        if ($responsive_img_html !== '') {
                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped HTML.
                            echo $responsive_img_html;
                        } elseif (!empty($s['image']['url'])) {
                            ?>
                        <img src="<?php echo esc_url($s['image']['url']); ?>" alt="<?php echo esc_attr($image_alt); ?>" loading="lazy" fetchpriority="high" decoding="async" style="<?php echo esc_attr($img_style); ?>">
                            <?php
                        }
                        ?>
                    </div>
                    <?php if ($s['banner_text']) : ?>
                        <div class="pc-banner <?php echo esc_attr($s['banner_position']); ?>"><?php echo $s['banner_text']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="pc-title"><?php echo $s['title']; ?></div>

                <?php if($s['show_divider']=='yes'): ?><hr class="pc-divider"><?php endif; ?>

                <div class="pc-row <?php echo esc_attr($layout_class); ?>">
                    <div class="pc-caption"><?php echo $s['caption']; ?></div>
                    <div class="pc-price"><?php echo $s['price']; ?></div>
                </div>
                <?php if (!empty($s['bottom_caption'])) : ?>
                    <div class="pc-bottom-caption"><?php echo $s['bottom_caption']; ?></div>
                <?php endif; ?>
            </div>
            <?php if ($card_link !== '') : ?>
                </a>
            <?php endif; ?>

            <style>
                .pc-card-link{display:block; text-decoration:none; color:inherit;}
                .pc-card{position:relative; transition:.3s;}
                .pc-image-box{position:relative; overflow:visible;}
                .pc-image{position:relative; height:100%; width:100%;}
                .pc-image img{vertical-align:top;}
                .pc-banner{z-index:2; position:absolute; transition:.3s; padding:5px 12px; font-size:12px;}
                .pc-banner.top-right{top:10px; right:10px;}
                .pc-banner.top-left{top:10px; left:10px;}
                .pc-banner.bottom-right{bottom:10px; right:10px;}
                .pc-banner.bottom-left{bottom:10px; left:10px;}
                .pc-divider{border-top-width:2px; border-top-style:solid; border-bottom:0;}
                .pc-row.row{flex-direction:row; justify-content:space-between; display:flex;}
                .pc-row.column{flex-direction:column; display:flex;}
                .pc-bottom-caption{position:relative;}
                .pc-bottom-caption::before,
                .pc-bottom-caption::after{
                    content:'';
                    display:block;
                    border-top-style:solid;
                    border-top-width:1px;
                    border-top-color:transparent;
                    width:100%;
                }
            </style>
            <?php
        }
    }

    class Double_Heading_Widget extends \Elementor\Widget_Base {

        public function get_name(){ return 'double_heading_widget'; }
        public function get_title(){ return 'Double Heading'; }
        public function get_icon(){ return 'eicon-t-letter'; }
        public function get_categories(){ return ['general']; }

        protected function register_controls(){
            $this->start_controls_section('content_section',[
                'label'=>'Content',
                'tab'=>\Elementor\Controls_Manager::TAB_CONTENT
            ]);

            $this->add_control('heading_main',[
                'label'=>'Heading Part 1',
                'type'=>\Elementor\Controls_Manager::TEXT,
                'default'=>'Heading'
            ]);
            $this->add_control('heading_span',[
                'label'=>'Heading Part 2 (Span)',
                'type'=>\Elementor\Controls_Manager::TEXT,
                'default'=>'Part 2'
            ]);
            $this->add_control('html_tag',[
                'label'=>'HTML Tag',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[
                    'h1'=>'H1','h2'=>'H2','h3'=>'H3','h4'=>'H4','h5'=>'H5','h6'=>'H6',
                    'div'=>'div','span'=>'span','p'=>'p'
                ],
                'default'=>'h2'
            ]);
            $this->add_control('span_direction',[
                'label'=>'Span Direction',
                'type'=>\Elementor\Controls_Manager::SELECT,
                'options'=>[
                    'inline'=>'Same Line',
                    'down'=>'Down (Next Line)'
                ],
                'default'=>'inline'
            ]);
            $this->add_responsive_control('align',[
                'label'=>'Alignment',
                'type'=>\Elementor\Controls_Manager::CHOOSE,
                'options'=>[
                    'left'=>['title'=>'Left','icon'=>'eicon-text-align-left'],
                    'center'=>['title'=>'Center','icon'=>'eicon-text-align-center'],
                    'right'=>['title'=>'Right','icon'=>'eicon-text-align-right'],
                    'justify'=>['title'=>'Justified','icon'=>'eicon-text-align-justify'],
                ],
                'default'=>'left',
                'selectors'=>['{{WRAPPER}} .dh-heading-wrap'=>'text-align:{{VALUE}};']
            ]);
            $this->end_controls_section();

            $this->start_controls_section('heading_style',[
                'label'=>'Heading',
                'tab'=>\Elementor\Controls_Manager::TAB_STYLE
            ]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),[
                'name'=>'heading_typography',
                'selector'=>'{{WRAPPER}} .dh-heading'
            ]);
            $this->add_control('heading_color',[
                'label'=>'Text Color',
                'type'=>\Elementor\Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} .dh-heading'=>'color:{{VALUE}};']
            ]);
            $this->end_controls_section();

            $this->start_controls_section('highlight_style',[
                'label'=>'Highlight (Span)',
                'tab'=>\Elementor\Controls_Manager::TAB_STYLE
            ]);
            $this->add_control('highlight_color',[
                'label'=>'Text Color',
                'type'=>\Elementor\Controls_Manager::COLOR,
                'selectors'=>['{{WRAPPER}} .dh-heading .dh-highlight'=>'color:{{VALUE}};']
            ]);
            $this->add_group_control(\Elementor\Group_Control_Typography::get_type(),[
                'name'=>'highlight_typography',
                'selector'=>'{{WRAPPER}} .dh-heading .dh-highlight'
            ]);
            $this->add_responsive_control('highlight_margin',[
                'label'=>'Span Margin',
                'type'=>\Elementor\Controls_Manager::DIMENSIONS,
                'selectors'=>[
                    '{{WRAPPER}} .dh-heading .dh-highlight'=>'margin:{{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
                ]
            ]);
            $this->end_controls_section();
        }

        protected function render(){
            $s = $this->get_settings_for_display();
            $tag = in_array($s['html_tag'], ['h1','h2','h3','h4','h5','h6','div','span','p'], true) ? $s['html_tag'] : 'h2';
            $heading_main = isset($s['heading_main']) ? $s['heading_main'] : '';
            $heading_span = isset($s['heading_span']) ? $s['heading_span'] : '';
            $span_style = (($s['span_direction'] ?? 'inline') === 'down') ? 'display:block;' : 'display:inline;';
            ?>
            <div class="dh-heading-wrap">
                <<?php echo esc_attr($tag); ?> class="dh-heading">
                    <?php echo esc_html($heading_main); ?>
                    <span class="dh-highlight" style="<?php echo esc_attr($span_style); ?>"><?php echo esc_html($heading_span); ?></span>
                </<?php echo esc_attr($tag); ?>>
            </div>
            <?php
        }
    }

    $widgets_manager->register(new Advanced_Product_Card());
    $widgets_manager->register(new Double_Heading_Widget());
}

add_action('elementor/widgets/register','register_advanced_product_card');