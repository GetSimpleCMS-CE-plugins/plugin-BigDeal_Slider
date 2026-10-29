<?php
/*
Plugin Name: BigDeal Image Slider
Description: Responsive image slider plugin for GetSimple CMS.
Version: 1.0
Author: Zeth
*/

$thisfile_bigdeal_slider = basename(__FILE__, ".php");

register_plugin(
	$thisfile_bigdeal_slider,
	'BigDeal Image Slider',
	'1.0',
	'Zeth',
	'http://getsimple-ce.ovh',
	'Responsive image slider with customizable captions, transitions, and native image picker modal.',
	'theme',
	'bigdeal_slider_admin'
);

add_action('theme-sidebar', 'createSideMenu', array($thisfile_bigdeal_slider, 'Image Slider Settings'));

function bds_get_site_url() {
	if (defined('SITEURL')) {
		return rtrim(SITEURL, '/') . '/';
	}
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	$host = $_SERVER['HTTP_HOST'];
	$script = dirname($_SERVER['PHP_SELF']);
	$base = rtrim(str_replace('\\', '/', $script), '/');
	$base = preg_replace('/\/admin$/', '', $base);
	return $protocol . $host . $base . '/';
}

// Frontend Display Function
function get_bigdeal_slider() {
	$data_file = GSDATAPATH . 'other/bigdeal_slider_data.json';
	if (!file_exists($data_file)) return;
	
	$data = json_decode(file_get_contents($data_file), true);
	if (empty($data['slides'])) return;

	$settings = isset($data['settings']) ? $data['settings'] : array();
	$effect = isset($settings['effect']) ? $settings['effect'] : 'fade';
	$interval = isset($settings['interval']) ? intval($settings['interval']) : 7000;
	$duration = isset($settings['transitionDuration']) ? intval($settings['transitionDuration']) : 1500;
	
	$height = (!empty($settings['sliderHeight'])) ? trim($settings['sliderHeight']) : '450px';
	if (is_numeric($height)) {
		$height .= 'px';
	}

	$title_size = isset($settings['titleSize']) ? $settings['titleSize'] : '28px';
	$caption_size = isset($settings['captionSize']) ? $settings['captionSize'] : '22px';
	$caption_color = isset($settings['captionColor']) ? $settings['captionColor'] : '#ffffff';
	$caption_bg = isset($settings['captionBg']) ? $settings['captionBg'] : 'rgba(100, 100, 100, 0.3)';
	$caption_align = isset($settings['captionAlign']) ? $settings['captionAlign'] : 'left';

	// Inline CSS with Alignment, Shadows, and Minimalist Arrows
	echo '<style>
		#gs_bigdeal_slider {
			width: 100% !important;
			max-width: 100% !important;
			height: ' . htmlspecialchars($height) . ' !important;
			min-height: ' . htmlspecialchars($height) . ' !important;
			position: relative !important;
			display: block !important;
			background: #000 !important;
			overflow: hidden;
			perspective: 1200px;
		}
		.bigdeal-slider { position: relative; width: 100%; height: 100%; overflow: hidden; }
		.bigdeal-slide { 
			position: absolute; 
			top: 0; 
			left: 0; 
			width: 100%; 
			height: 100%; 
			background-size: cover; 
			background-position: center; 
			display: none; 
			backface-visibility: hidden;
			opacity: 0;
		}
		.bds-caption { 
			position: absolute !important; 
			bottom: 0 !important; 
			left: 0 !important; 
			right: 0 !important; 
			z-index: 25 !important; 
			padding: 15px !important; 
			background: ' . htmlspecialchars($caption_bg) . ' !important; 
			box-sizing: border-box !important;
			text-align: ' . htmlspecialchars($caption_align) . ' !important;
			display: none;
		}
		.bds-caption .bds-caption-content {
			margin: 0 !important;
			padding: 0 !important;
			font-family: inherit !important;
		}
		.bds-caption .bds-title-text { 
			font-size: ' . htmlspecialchars($title_size) . ' !important; 
			color: ' . htmlspecialchars($caption_color) . ' !important; 
			font-weight: bold !important; 
			display: block !important;
			margin: 0 0 5px 0 !important; 
			padding: 0 !important; 
			line-height: 1.2 !important; 
			text-shadow: 0 2px 4px rgba(0,0,0,0.8) !important;
		}
		.bds-caption .bds-desc-text { 
			font-size: ' . htmlspecialchars($caption_size) . ' !important; 
			color: ' . htmlspecialchars($caption_color) . ' !important; 
			display: block !important;
			margin: 0 !important; 
			padding: 0 !important; 
			line-height: 1.4 !important; 
			text-shadow: 0 2px 4px rgba(0,0,0,0.8) !important;
		}
		.bds-caption .bds-caption-content a,
		.bds-caption .bds-caption-content a:hover,
		.bds-caption .bds-caption-content a:focus,
		.bds-caption .bds-title-text a,
		.bds-caption .bds-desc-text a { 
			color: ' . htmlspecialchars($caption_color) . ' !important; 
			text-decoration: none !important; 
			border-bottom: none !important;
			background: transparent !important;
		}

		/* Minimalist Clean Arrows with Focus Glow Removed */
		.bds-arrow {
			position: absolute !important;
			top: 50% !important;
			transform: translateY(-50%) !important;
			z-index: 30 !important;
			background: transparent !important;
			color: #ffffff !important;
			border: none !important;
			font-size: 45px !important;
			display: flex !important;
			align-items: center !important;
			justify-content: center !important;
			cursor: pointer !important;
			opacity: 0 !important;
			transition: opacity 0.3s ease, color 0.3s ease !important;
			user-select: none !important;
			padding: 10px !important;
			line-height: normal !important;
			text-shadow: 0 2px 6px rgba(0,0,0,0.7) !important;
			outline: none !important;
			box-shadow: none !important;
		}
		.bds-arrow:hover,
		.bds-arrow:focus,
		.bds-arrow:active {
			color: #dddddd !important;
			outline: none !important;
			box-shadow: none !important;
			background: transparent !important;
		}
		.bds-prev { left: 10px !important; }
		.bds-next { right: 10px !important; }
		#gs_bigdeal_slider:hover .bds-arrow {
			opacity: 1 !important;
		}
	</style>';

	// Render Slider HTML
	echo '<div id="gs_bigdeal_slider">';
	$caption_counter = 1;
	$external_captions = '';

	foreach ($data['slides'] as $slide) {
		$img_url = trim($slide['image']);
		if (!empty($img_url) && !preg_match('/^http/', $img_url) && !str_starts_with($img_url, '/')) {
			$img_url = bds_get_site_url() . ltrim($img_url, '/');
		}
		if (empty($img_url)) continue;
		
		$caption = trim($slide['caption']);
		$title = trim($slide['title']);
		$link = trim($slide['link']);
		
		$attr = 'data-src="' . htmlspecialchars($img_url) . '"';
		if (!empty($title)) {
			$attr .= ' title="' . htmlspecialchars($title) . '"';
		}
		if (!empty($caption) || !empty($title)) {
			$attr .= ' data-caption="#gs-caption-' . $caption_counter . '"';
			
			$title_span = !empty($title) ? '<span class="bds-title-text">' . htmlspecialchars($title) . '</span>' : '';
			$caption_span = !empty($caption) ? '<span class="bds-desc-text">' . htmlspecialchars($caption) . '</span>' : '';
			
			if (!empty($link)) {
				if (!empty($title) && !empty($caption)) {
					$title_span = '<span class="bds-title-text"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($title) . '</a></span>';
					$caption_span = '<span class="bds-desc-text"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($caption) . '</a></span>';
				} elseif (!empty($title)) {
					$title_span = '<span class="bds-title-text"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($title) . '</a></span>';
				} else {
					$caption_span = '<span class="bds-desc-text"><a href="' . htmlspecialchars($link) . '">' . htmlspecialchars($caption) . '</a></span>';
				}
			}
			
			$caption_html = $title_span . $caption_span;
			$external_captions .= '<div id="gs-caption-' . $caption_counter . '" style="display:none;">' . $caption_html . '</div>';
			$caption_counter++;
		}
		
		if (!empty($link) && empty($caption) && empty($title)) {
			echo '<a href="' . htmlspecialchars($link) . '"><img ' . $attr . ' /></a>';
		} else {
			echo '<img ' . $attr . ' />';
		}
	}
	
	echo '<button class="bds-arrow bds-prev" aria-label="Previous Slide">&#10094;</button>';
	echo '<button class="bds-arrow bds-next" aria-label="Next Slide">&#10095;</button>';

	echo '</div>';
	echo $external_captions;

	// Animation Engine with Synchronized Captions & Expanded Effect Support
	echo '<script>
		(function() {
			window.addEventListener("load", function() {
				var container = document.querySelector("#gs_bigdeal_slider");
				if (!container) return;
				
				var imgs = container.querySelectorAll("img");
				if (imgs.length === 0) return;

				var slides = [];
				var wrapper = document.createElement("div");
				wrapper.className = "bigdeal-slider";

				imgs.forEach(function(img, index) {
					var slide = document.createElement("div");
					slide.className = "bigdeal-slide";
					var src = img.getAttribute("data-src") || img.src;
					slide.style.backgroundImage = "url(" + src + ")";
					
					var capSelector = img.getAttribute("data-caption");
					if (capSelector) {
						var capSource = document.querySelector(capSelector);
						if (capSource) {
							var slideCap = document.createElement("div");
							slideCap.className = "bds-caption";
							slideCap.style.display = "block";
							slideCap.innerHTML = \'<div class="bds-caption-content">\' + capSource.innerHTML + \'</div>\';
							slide.appendChild(slideCap);
						}
					}

					if (index === 0) {
						slide.style.display = "block";
						slide.style.opacity = "1";
						slide.style.zIndex = "2";
					}
					wrapper.appendChild(slide);
					slides.push(slide);
				});

				var prevBtn = container.querySelector(".bds-prev");
				var nextBtn = container.querySelector(".bds-next");

				container.innerHTML = "";
				container.appendChild(wrapper);

				if (prevBtn) container.appendChild(prevBtn);
				if (nextBtn) container.appendChild(nextBtn);

				var current = 0;
				var baseEffect = "' . htmlspecialchars($effect) . '";
				var intervalTime = ' . $interval . ';
				var duration = ' . $duration . ';
				var isAnimating = false;
				var slideTimer = null;

				var availableEffects = [
					"fade", "slide_left", "slide_right", "slide_up", "slide_down", 
					"blur", "flip", "cube", "wave_zoom", "curtain", "elastic_zoom", 
					"carousel", "iris", "stack", "zoom_in", 
					"flip_v", "book_fold", "blinds", "bounce_slide"
				];

				function resetSlideStyles(slide) {
					slide.style.transform = "none";
					slide.style.transformOrigin = "center center";
					slide.style.boxShadow = "none";
					slide.style.clipPath = "none";
					slide.style.webkitClipPath = "none";
					slide.style.filter = "none";
				}

				function showSlide(nextIndex) {
					if (isAnimating || nextIndex === current) return;
					isAnimating = true;

					var currSlide = slides[current];
					var nextSlide = slides[nextIndex];
					var startTime = null;

					// Resolve effect if set to random
					var effect = baseEffect;
					if (effect === "random") {
						var randomIndex = Math.floor(Math.random() * availableEffects.length);
						effect = availableEffects[randomIndex];
					}

					resetSlideStyles(currSlide);
					resetSlideStyles(nextSlide);
					
					nextSlide.style.display = "block";
					nextSlide.style.opacity = "0";

					function animate(time) {
						if (!startTime) startTime = time;
						var progress = (time - startTime) / duration;
						if (progress > 1) progress = 1;

						if (effect === "slide_left") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.transform = "translateX(" + (-progress * 100) + "%)";
							nextSlide.style.transform = "translateX(" + ((1 - progress) * 100) + "%)";
						} else if (effect === "slide_right") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.transform = "translateX(" + (progress * 100) + "%)";
							nextSlide.style.transform = "translateX(" + (-(1 - progress) * 100) + "%)";
						} else if (effect === "slide_up") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.transform = "translateY(" + (-progress * 100) + "%)";
							nextSlide.style.transform = "translateY(" + ((1 - progress) * 100) + "%)";
						} else if (effect === "slide_down") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.transform = "translateY(" + (progress * 100) + "%)";
							nextSlide.style.transform = "translateY(" + (-(1 - progress) * 100) + "%)";
						} else if (effect === "blur") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = 1 - progress;
							var blurVal = progress * 10;
							nextSlide.style.opacity = "1";
							nextSlide.style.filter = "blur(" + (10 - blurVal) + "px)";
						} else if (effect === "flip") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							var rotCurr = progress * -90;
							var rotNext = (1 - progress) * 90;
							currSlide.style.transform = "perspective(1000px) rotateY(" + rotCurr + "deg)";
							nextSlide.style.transform = "perspective(1000px) rotateY(" + rotNext + "deg)";
						} else if (effect === "cube") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.transformOrigin = "center right";
							nextSlide.style.transformOrigin = "center left";
							currSlide.style.transform = "rotateY(" + (progress * -90) + "deg)";
							nextSlide.style.transform = "rotateY(" + ((1 - progress) * 90) + "deg)";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
						} else if (effect === "wave_zoom") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = 1 - progress;
							var scaleVal = 0.8 + (progress * 0.2);
							nextSlide.style.opacity = progress;
							nextSlide.style.transform = "scale(" + scaleVal + ") translateY(" + ((1 - progress) * 30) + "px)";
						} else if (effect === "curtain") {
							currSlide.style.zIndex = "3";
							nextSlide.style.zIndex = "2";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.clipPath = "inset(0 " + (progress * 50) + "% 0 " + (progress * 50) + "%)";
							currSlide.style.webkitClipPath = "inset(0 " + (progress * 50) + "% 0 " + (progress * 50) + "%)";
						} else if (effect === "elastic_zoom") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = 1 - progress;
							var easeElastic = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress) * Math.sin((progress - 0.075) * (2 * Math.PI) / 0.3);
							var scale = 0.7 + (easeElastic * 0.3);
							nextSlide.style.opacity = progress;
							nextSlide.style.transform = "scale(" + scale + ")";
						} else if (effect === "carousel") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.transform = "perspective(1000px) translateZ(" + (-progress * 300) + "px) rotateY(" + (progress * 45) + "deg)";
							currSlide.style.opacity = 1 - progress;
							nextSlide.style.transform = "perspective(1000px) translateZ(" + ((1 - progress) * -300) + "px) rotateY(" + (-(1 - progress) * 45) + "deg)";
							nextSlide.style.opacity = progress;
						} else if (effect === "iris") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							var radius = progress * 150;
							nextSlide.style.clipPath = "circle(" + radius + "% at 50% 50%)";
							nextSlide.style.webkitClipPath = "circle(" + radius + "% at 50% 50%)";
						} else if (effect === "stack") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							var currScale = 1 - (progress * 0.1);
							var nextY = (1 - progress) * 100;
							currSlide.style.transform = "scale(" + currScale + ")";
							nextSlide.style.transform = "translateY(" + nextY + "%)";
							nextSlide.style.boxShadow = "0 10px 30px rgba(0,0,0,0.5)";
						} else if (effect === "zoom_in") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = 1 - progress;
							var scaleVal = 1.3 - (progress * 0.3);
							nextSlide.style.opacity = progress;
							nextSlide.style.transform = "scale(" + scaleVal + ")";
						} else if (effect === "flip_v") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							var rotCurr = progress * -90;
							var rotNext = (1 - progress) * 90;
							currSlide.style.transform = "perspective(1000px) rotateX(" + rotCurr + "deg)";
							nextSlide.style.transform = "perspective(1000px) rotateX(" + rotNext + "deg)";
						} else if (effect === "book_fold") {
							currSlide.style.zIndex = "3";
							nextSlide.style.zIndex = "2";
							currSlide.style.transformOrigin = "left center";
							currSlide.style.opacity = 1 - (progress * 0.5);
							currSlide.style.transform = "perspective(1200px) rotateY(" + (-progress * 90) + "deg)";
							nextSlide.style.opacity = "1";
						} else if (effect === "blinds") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							var clipPct = progress * 100;
							nextSlide.style.clipPath = "polygon(0 0, " + clipPct + "% 0, " + clipPct + "% 100%, 0 100%)";
							nextSlide.style.webkitClipPath = "polygon(0 0, " + clipPct + "% 0, " + clipPct + "% 100%, 0 100%)";
						} else if (effect === "bounce_slide") {
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = "1";
							nextSlide.style.opacity = "1";
							currSlide.style.transform = "translateX(" + (-progress * 100) + "%)";
							
							var bounce;
							if (progress < 0.6) {
								bounce = (progress / 0.6) * 1.1;
							} else if (progress < 0.8) {
								bounce = 1.1 - ((progress - 0.6) / 0.2) * 0.15;
							} else {
								bounce = 0.95 + ((progress - 0.8) / 0.2) * 0.05;
							}
							var bounceX = (1 - bounce) * 100;
							nextSlide.style.transform = "translateX(" + bounceX + "%)";
						} else {
							// Default Fade
							currSlide.style.zIndex = "2";
							nextSlide.style.zIndex = "3";
							currSlide.style.opacity = 1 - progress;
							nextSlide.style.opacity = progress;
						}

						if (progress < 1) {
							requestAnimationFrame(animate);
						} else {
							currSlide.style.display = "none";
							resetSlideStyles(currSlide);
							currSlide.style.opacity = "0";

							resetSlideStyles(nextSlide);
							nextSlide.style.opacity = "1";
							nextSlide.style.zIndex = "2";
							currSlide.style.zIndex = "1";

							current = nextIndex;
							isAnimating = false;
						}
					}
					requestAnimationFrame(animate);
				}

				function startAutoSlide() {
					if (slides.length > 1) {
						slideTimer = setInterval(function() {
							var next = (current + 1) % slides.length;
							showSlide(next);
						}, intervalTime);
					}
				}

				function resetAutoSlide() {
					clearInterval(slideTimer);
					startAutoSlide();
				}

				startAutoSlide();

				if (nextBtn) {
					nextBtn.addEventListener("click", function(e) {
						e.preventDefault();
						if (isAnimating) return;
						var next = (current + 1) % slides.length;
						showSlide(next);
						resetAutoSlide();
					});
				}

				if (prevBtn) {
					prevBtn.addEventListener("click", function(e) {
						e.preventDefault();
						if (isAnimating) return;
						var prev = (current - 1 + slides.length) % slides.length;
						showSlide(prev);
						resetAutoSlide();
					});
				}
			});
		})();
	</script>';
}

// Helper to build recursive folder tree structure for the modal picker
function bds_scan_uploads_tree($base_dir, $base_url, $sub_path = '') {
	$current_dir = realpath($base_dir . '/' . $sub_path);
	$tree = array(
		'folders' => array(),
		'files' => array()
	);

	if (!$current_dir || !is_dir($current_dir)) return $tree;

	$items = scandir($current_dir);
	foreach ($items as $item) {
		if ($item === '.' || $item === '..') continue;
		$full_item_path = $current_dir . '/' . $item;
		$rel_item_path = ltrim($sub_path . '/' . $item, '/');

		if (is_dir($full_item_path)) {
			$tree['folders'][] = array(
				'name' => $item,
				'path' => $rel_item_path
			);
		} elseif (is_file($full_item_path)) {
			$ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
			if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'))) {
				$tree['files'][] = array(
					'name' => $item,
					'url' => $base_url . $rel_item_path
				);
			}
		}
	}
	return $tree;
}

// Backend Administration Interface
function bigdeal_slider_admin() {
	$data_file = GSDATAPATH . 'other/bigdeal_slider_data.json';
	
	if (isset($_POST['save_slider'])) {
		$slides = array();
		if (isset($_POST['slide_image'])) {
			foreach ($_POST['slide_image'] as $key => $img) {
				if (!empty($img)) {
					$slides[] = array(
						'image' => $_POST['slide_image'][$key],
						'title' => $_POST['slide_title'][$key],
						'caption' => $_POST['slide_caption'][$key],
						'link' => $_POST['slide_link'][$key]
					);
				}
			}
		}
		
		$settings = array(
			'effect' => $_POST['setting_effect'],
			'interval' => intval($_POST['setting_interval']),
			'transitionDuration' => intval($_POST['setting_duration']),
			'sliderHeight' => $_POST['setting_slider_height'],
			'titleSize' => $_POST['setting_title_size'],
			'captionSize' => $_POST['setting_caption_size'],
			'captionColor' => $_POST['setting_caption_color'],
			'captionBg' => $_POST['setting_caption_bg'],
			'captionAlign' => $_POST['setting_caption_align']
		);
		
		$output = array('slides' => $slides, 'settings' => $settings);
		
		if (!file_exists(GSDATAPATH . 'other')) {
			mkdir(GSDATAPATH . 'other', 0755, true);
		}
		
		if (file_put_contents($data_file, json_encode($output, JSON_UNESCAPED_SLASHES))) {
			echo '<p class="updated">Slider settings saved successfully!</p>';
		} else {
			echo '<p class="error">Error saving slider settings.</p>';
		}
	}

	$data = array('slides' => array(), 'settings' => array());
	if (file_exists($data_file)) {
		$data = json_decode(file_get_contents($data_file), true);
	}
	
	$slides = isset($data['slides']) ? $data['slides'] : array();
	$settings = isset($data['settings']) ? $data['settings'] : array();
	
	$effect = isset($settings['effect']) ? $settings['effect'] : 'fade';
	$interval = isset($settings['interval']) ? $settings['interval'] : 7000;
	$duration = isset($settings['transitionDuration']) ? $settings['transitionDuration'] : 1500;
	$slider_height = isset($settings['sliderHeight']) ? $settings['sliderHeight'] : '450px';
	$title_size = isset($settings['titleSize']) ? $settings['titleSize'] : '28px';
	$caption_size = isset($settings['captionSize']) ? $settings['captionSize'] : '22px';
	$caption_color = isset($settings['captionColor']) ? $settings['captionColor'] : '#ffffff';
	$caption_bg = isset($settings['captionBg']) ? $settings['captionBg'] : 'rgba(100, 100, 100, 0.3)';
	$caption_align = isset($settings['captionAlign']) ? $settings['captionAlign'] : 'left';

	// Scan uploads directory for tree navigation
	$uploads_path = realpath(GSDATAPATH . '../data/uploads');
	$site_uploads_url = rtrim(bds_get_site_url(), '/') . '/data/uploads/';
	
	$dir_map = array();
	if ($uploads_path && is_dir($uploads_path)) {
		$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_path, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
		$dir_map[''] = bds_scan_uploads_tree($uploads_path, $site_uploads_url, '');
		foreach ($iterator as $file) {
			if ($file->isDir()) {
				$rel = str_replace($uploads_path, '', $file->getPathname());
				$rel = ltrim(str_replace('\\', '/', $rel), '/');
				$dir_map[$rel] = bds_scan_uploads_tree($uploads_path, $site_uploads_url, $rel);
			}
		}
	}
?>
	<style>
		.bds-admin-wrap { max-width: 100%; box-sizing: border-box; }
		.bds-admin-wrap .slider-tabs { background: #f9f9f9; border: 1px solid #dfdfdf; padding: 20px; border-radius: 4px; margin-bottom: 20px; }
		.bds-admin-wrap .slider-tabs h4 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 8px; color: #333; font-size: 14px; }
		.bds-admin-wrap .row { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 10px; }
		.bds-admin-wrap .col { flex: 1; min-width: 200px; box-sizing: border-box; }
		.bds-admin-wrap label, .bds-admin-wrap .slide-inputs label { display: block; font-weight: bold; margin-bottom: 5px; font-size: 12px; color: #555; }
		.bds-admin-wrap .text, .bds-admin-wrap select { width: 100% !important; padding: 8px !important; box-sizing: border-box !important; border: 1px solid #ccc !important; border-radius: 3px !important; background: #fff !important; color: #333 !important; height: auto !important; }
		.bds-admin-wrap .slide-item { background: #fff; border: 1px solid #ccc; padding: 15px; margin-bottom: 15px; border-radius: 4px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; box-sizing: border-box; }
		.bds-admin-wrap .slide-preview { flex: 0 0 90px !important; width: 90px !important; height: 60px !important; overflow: hidden !important; background: #eee; border: 1px solid #ccc; border-radius: 3px; display: flex; align-items: center; justify-content: center; }
		.bds-admin-wrap .slide-thumb-preview { width: 90px !important; height: 60px !important; object-fit: cover !important; display: block !important; }
		.bds-admin-wrap .slide-inputs { flex: 3; min-width: 260px; box-sizing: border-box; }
		.bds-admin-wrap .slide-inputs p { margin: 0 0 10px 0; }
		.bds-admin-wrap .slide-actions { flex: 1; min-width: 100px; text-align: right; box-sizing: border-box; }
		.bds-info {padding: 15px 10px;border-left: 4px solid #CC0000;margin-bottom: 20px;background:#F9F9F9;}
		
		.bds-admin-wrap .btn, 
		.bds-admin-wrap input[type="submit"].submit { 
			display: inline-block !important; 
			background: #0074a2 !important; 
			color: #ffffff !important; 
			padding: 8px 14px !important; 
			text-decoration: none !important; 
			border-radius: 3px !important; 
			font-size: 12px !important; 
			border: none !important; 
			cursor: pointer !important; 
			font-weight: bold !important;
			line-height: normal !important;
			text-shadow: none !important;
		}
		.bds-admin-wrap .btn:hover, 
		.bds-admin-wrap input[type="submit"].submit:hover { 
			background: #005172 !important; 
			color: #ffffff !important; 
		}
		.bds-admin-wrap .btn.red { 
			background: #a00 !important; 
		}
		.bds-admin-wrap .btn.red:hover { 
			background: #700 !important; 
		}
		.bds-admin-wrap .image-input { 
			width: calc(100% - 130px) !important; 
			display: inline-block !important; 
		}

		#bds-modal-overlay {
			display: none;
			position: fixed;
			top: 0; left: 0; width: 100%; height: 100%;
			background: rgba(0,0,0,0.7);
			z-index: 99999;
			align-items: center;
			justify-content: center;
		}
		#bds-modal-box {
			background: #fff;
			width: 800px;
			max-width: 90%;
			max-height: 85vh;
			border-radius: 6px;
			display: flex;
			flex-direction: column;
			box-shadow: 0 5px 25px rgba(0,0,0,0.4);
			overflow: hidden;
		}
		#bds-modal-header {
			padding: 12px 20px;
			background: #f1f1f1;
			border-bottom: 1px solid #ddd;
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-weight: bold;
			font-size: 15px;
		}
		#bds-breadcrumbs {
			padding: 10px 20px;
			background: #fafafa;
			border-bottom: 1px solid #eee;
			font-size: 13px;
			color: #555;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.bds-up-btn {
			background: #e0e0e0;
			border: 1px solid #ccc;
			padding: 3px 8px;
			border-radius: 3px;
			cursor: pointer;
			font-size: 11px;
			font-weight: bold;
		}
		.bds-up-btn:hover { background: #d0d0d0; }
		#bds-modal-body {
			padding: 20px;
			overflow-y: auto;
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(115px, 1fr));
			gap: 15px;
		}
		.bds-picker-folder {
			border: 1px dashed #0074a2;
			border-radius: 4px;
			background: #f0f7ff;
			cursor: pointer;
			text-align: center;
			padding: 12px 8px;
			transition: all 0.2s;
		}
		.bds-picker-folder:hover {
			background: #d8ebff;
			transform: translateY(-2px);
		}
		.bds-folder-icon {
			width: 32px;
			height: 32px;
			margin: 0 auto 5px auto;
			display: block;
			fill: #0074a2;
		}
		.bds-picker-item {
			border: 1px solid #ccc;
			border-radius: 4px;
			background: #fafafa;
			cursor: pointer;
			text-align: center;
			padding: 8px;
			transition: all 0.2s;
		}
		.bds-picker-item:hover {
			border-color: #0074a2;
			background: #eef8ff;
			transform: translateY(-2px);
		}
		.bds-picker-thumb {
			width: 100%;
			height: 80px;
			object-fit: cover;
			border-radius: 3px;
			margin-bottom: 5px;
			background: #eee;
		}
		.bds-picker-name {
			font-size: 11px;
			color: #333;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}
		#bds-modal-close {
			background: none;
			border: none;
			font-size: 22px;
			cursor: pointer;
			color: #666;
		}
	</style>

	<div class="wrap bds-admin-wrap">
		<h2>BigDeal Image Slider Management</h2>
		   <div class="bds-info"><strong>Template Usage: </strong><code><em>&lt;?php get_bigdeal_slider(); ?&gt;</em></code></div>
		<form method="post" action="">
			<div class="slider-tabs">
				<h4>Global Slider Settings</h4>
				<div class="row">
					<div class="col">
						<label>Transition Effect:</label>
						<select name="setting_effect" class="text">
							<option value="random" <?php if($effect=='random') echo 'selected'; ?>>Random (Mix All Effects)</option>
							<option value="fade" <?php if($effect=='fade') echo 'selected'; ?>>Fade (Dissolve)</option>
							<option value="slide_left" <?php if($effect=='slide_left') echo 'selected'; ?>>Slide Left</option>
							<option value="slide_right" <?php if($effect=='slide_right') echo 'selected'; ?>>Slide Right</option>
							<option value="slide_up" <?php if($effect=='slide_up') echo 'selected'; ?>>Slide Up</option>
							<option value="slide_down" <?php if($effect=='slide_down') echo 'selected'; ?>>Slide Down</option>
							<option value="blur" <?php if($effect=='blur') echo 'selected'; ?>>Blur / Dissolve</option>
							<option value="flip" <?php if($effect=='flip') echo 'selected'; ?>>3D Flip (Horizontal)</option>
							<option value="flip_v" <?php if($effect=='flip_v') echo 'selected'; ?>>3D Flip (Vertical)</option>
							<option value="cube" <?php if($effect=='cube') echo 'selected'; ?>>3D Cube Rotate</option>
							<option value="wave_zoom" <?php if($effect=='wave_zoom') echo 'selected'; ?>>Diagonal Shutter / Wave Zoom</option>
							<option value="curtain" <?php if($effect=='curtain') echo 'selected'; ?>>Center Curtain Split</option>
							<option value="elastic_zoom" <?php if($effect=='elastic_zoom') echo 'selected'; ?>>Elastic Spring Zoom</option>
							<option value="carousel" <?php if($effect=='carousel') echo 'selected'; ?>>3D Carousel Depth</option>
							<option value="iris" <?php if($effect=='iris') echo 'selected'; ?>>Radial Iris Circle Reveal</option>
							<option value="stack" <?php if($effect=='stack') echo 'selected'; ?>>Stacked Card Fold</option>
							<option value="zoom_in" <?php if($effect=='zoom_in') echo 'selected'; ?>>Zoom In & Fade</option>
							<option value="book_fold" <?php if($effect=='book_fold') echo 'selected'; ?>>Page Turn / Book Fold</option>
							<option value="blinds" <?php if($effect=='blinds') echo 'selected'; ?>>Blinds Reveal</option>
							<option value="bounce_slide" <?php if($effect=='bounce_slide') echo 'selected'; ?>>Bounce / Elastic Slide</option>
						</select>
					</div>
					<div class="col">
						<label>Slide Interval (ms):</label>
						<input type="number" name="setting_interval" value="<?php echo $interval; ?>" class="text" />
					</div>
					<div class="col">
						<label>Transition Duration (ms):</label>
						<input type="number" name="setting_duration" value="<?php echo $duration; ?>" class="text" />
					</div>
				</div>

				<div class="row" style="margin-top: 15px;">
					<div class="col">
						<label>Slide Height (default 450px):</label>
						<input type="text" name="setting_slider_height" value="<?php echo htmlspecialchars($slider_height); ?>" class="text" placeholder="e.g. 450px or 500" />
					</div>
					<div class="col">
						<label>Title Font Size:</label>
						<input type="text" name="setting_title_size" value="<?php echo htmlspecialchars($title_size); ?>" class="text" placeholder="e.g. 28px" />
					</div>
					<div class="col">
						<label>Caption Font Size:</label>
						<input type="text" name="setting_caption_size" value="<?php echo htmlspecialchars($caption_size); ?>" class="text" placeholder="e.g. 22px" />
					</div>
				</div>

				<div class="row" style="margin-top: 15px;">
					<div class="col">
						<label>Caption Text Color:</label>
						<input type="text" name="setting_caption_color" value="<?php echo htmlspecialchars($caption_color); ?>" class="text" placeholder="e.g. #ffffff" />
					</div>
					<div class="col">
						<label>Caption Background Color/Opacity:</label>
						<input type="text" name="setting_caption_bg" value="<?php echo htmlspecialchars($caption_bg); ?>" class="text" placeholder="e.g. rgba(0,0,0,0.5)" />
					</div>
					<div class="col">
						<label>Caption Text Alignment:</label>
						<select name="setting_caption_align" class="text">
							<option value="left" <?php if($caption_align=='left') echo 'selected'; ?>>Left</option>
							<option value="center" <?php if($caption_align=='center') echo 'selected'; ?>>Center</option>
							<option value="right" <?php if($caption_align=='right') echo 'selected'; ?>>Right</option>
						</select>
					</div>
				</div>
			</div>

			<div class="slider-tabs">
				<h4>Slider Images & Captions</h4>
				<div id="slide-container">
					<?php if (!empty($slides)): ?>
						<?php foreach ($slides as $index => $slide): ?>
							<div class="slide-item">
								<div class="slide-preview">
									<img src="<?php echo htmlspecialchars($slide['image']); ?>" id="img_preview_<?php echo $index; ?>" class="slide-thumb-preview" style="<?php echo empty($slide['image']) ? 'display:none;' : ''; ?>" />
								</div>
								<div class="slide-inputs">
									<p>
										<label>Image URL:</label>
										<input type="text" name="slide_image[]" value="<?php echo htmlspecialchars($slide['image']); ?>" class="text image-input" id="img_val_<?php echo $index; ?>" oninput="updateThumb('img_val_<?php echo $index; ?>', 'img_preview_<?php echo $index; ?>')" />
										<a href="#" class="btn browse-btn" onclick="openFileBrowser('img_val_<?php echo $index; ?>', 'img_preview_<?php echo $index; ?>'); return false;">Select from Files</a>
									</p>
									<p>
										<label>Title (Optional Heading):</label>
										<input type="text" name="slide_title[]" value="<?php echo htmlspecialchars($slide['title']); ?>" class="text" />
									</p>
									<p>
										<label>Caption Text:</label>
										<input type="text" name="slide_caption[]" value="<?php echo htmlspecialchars($slide['caption']); ?>" class="text" />
									</p>
									<p>
										<label>Link URL (Optional):</label>
										<input type="text" name="slide_link[]" value="<?php echo htmlspecialchars($slide['link']); ?>" class="text" />
									</p>
								</div>
								<div class="slide-actions">
									<a href="#" class="btn red" onclick="removeSlide(this); return false;">Remove Slide</a>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				
				<p style="margin-top: 15px;">
					<a href="#" class="btn" id="add-slide-btn">+ Add New Slide</a>
				</p>
			</div>

			<p style="margin-top: 20px;">
				<input type="submit" name="save_slider" value="Save Changes" class="submit" />
			</p>
		</form>
	</div>

	<!-- Custom Navigable Image Picker Modal -->
	<div id="bds-modal-overlay">
		<div id="bds-modal-box">
			<div id="bds-modal-header">
				<span>Select Image from Uploads</span>
				<button id="bds-modal-close" onclick="closeFileBrowser(); return false;">&times;</button>
			</div>
			<div id="bds-breadcrumbs">
				<button id="bds-up-btn" class="bds-up-btn" onclick="navigateUpFolder(); return false;" style="display:none;">&uarr; Up One Level</button>
				<span id="bds-current-path">&#128193; /uploads/</span>
			</div>
			<div id="bds-modal-body"></div>
		</div>
	</div>

	<script type="text/javascript">
		var activeInputId = '';
		var activePreviewId = '';
		var bdsDirectoryMap = <?php echo json_encode($dir_map, JSON_UNESCAPED_SLASHES); ?>;
		var currentFolder = '';

		var folderSvgIcon = '<svg class="bds-folder-icon" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';

		function renderFolderView(folderPath) {
			currentFolder = folderPath;
			var body = document.getElementById('bds-modal-body');
			var pathSpan = document.getElementById('bds-current-path');
			var upBtn = document.getElementById('bds-up-btn');

			pathSpan.innerHTML = '&#128193; /uploads/' + folderPath;
			upBtn.style.display = folderPath ? 'inline-block' : 'none';

			body.innerHTML = '';
			var data = bdsDirectoryMap[folderPath] || { folders: [], files: [] };

			if (data.folders.length === 0 && data.files.length === 0) {
				body.innerHTML = '<p style="grid-column: 1 / -1; text-align: center; color: #777; padding: 20px;">This folder is empty.</p>';
				return;
			}

			data.folders.forEach(function(f) {
				var div = document.createElement('div');
				div.className = 'bds-picker-folder';
				div.onclick = function() { renderFolderView(f.path); };
				div.innerHTML = folderSvgIcon + '<div class="bds-picker-name">' + f.name + '</div>';
				body.appendChild(div);
			});

			data.files.forEach(function(file) {
				var div = document.createElement('div');
				div.className = 'bds-picker-item';
				div.onclick = function() { selectImageURL(file.url); };
				div.innerHTML = '<img src="' + file.url + '" class="bds-picker-thumb" /><div class="bds-picker-name">' + file.name + '</div>';
				body.appendChild(div);
			});
		}

		function navigateUpFolder() {
			if (!currentFolder) return;
			var parts = currentFolder.split('/');
			parts.pop();
			renderFolderView(parts.join('/'));
		}

		function openFileBrowser(inputId, previewId) {
			activeInputId = inputId;
			activePreviewId = previewId;
			renderFolderView('');
			document.getElementById('bds-modal-overlay').style.display = 'flex';
		}

		function closeFileBrowser() {
			document.getElementById('bds-modal-overlay').style.display = 'none';
		}

		function selectImageURL(fileUrl) {
			if (activeInputId && document.getElementById(activeInputId)) {
				document.getElementById(activeInputId).value = fileUrl;
				updateThumb(activeInputId, activePreviewId);
			}
			closeFileBrowser();
		}

		function updateThumb(inputId, previewId) {
			var val = document.getElementById(inputId).value;
			var prev = document.getElementById(previewId);
			if (val) {
				prev.src = val;
				prev.style.display = 'block';
			} else {
				prev.src = '';
				prev.style.display = 'none';
			}
		}

		document.getElementById('add-slide-btn').addEventListener('click', function(e) {
			e.preventDefault();
			var randId = 'img_' + Math.random().toString(36).substring(2, 9);
			var randPrev = 'prev_' + Math.random().toString(36).substring(2, 9);
			var container = document.getElementById('slide-container');
			
			var div = document.createElement('div');
			div.className = 'slide-item';
			div.innerHTML = '<div class="slide-preview"><img src="" id="' + randPrev + '" class="slide-thumb-preview" style="display:none;" /></div>' +
				'<div class="slide-inputs">' +
				'<p><label>Image URL:</label><input type="text" name="slide_image[]" value="" class="text image-input" id="' + randId + '" oninput="updateThumb(\'' + randId + '\', \'' + randPrev + '\')" /> <a href="#" class="btn browse-btn" onclick="openFileBrowser(\'' + randId + '\', \'' + randPrev + '\'); return false;">Select from Files</a></p>' +
				'<p><label>Title (Optional Heading):</label><input type="text" name="slide_title[]" value="" class="text" /></p>' +
				'<p><label>Caption Text:</label><input type="text" name="slide_caption[]" value="" class="text" /></p>' +
				'<p><label>Link URL (Optional):</label><input type="text" name="slide_link[]" value="" class="text" /></p>' +
				'</div><div class="slide-actions"><a href="#" class="btn red" onclick="removeSlide(this); return false;">Remove Slide</a></div>';
			container.appendChild(div);
		});

		function removeSlide(elem) {
			elem.closest('.slide-item').remove();
		}
	</script>
<?php
}