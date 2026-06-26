<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$s      = st_get_settings();
$home   = home_url('/');
$gas    = home_url('/gas-tracker/');
$places = home_url('/suggested-places/');
$driver = home_url('/driver-portal/');
$cur    = trailingslashit( get_permalink() ?: $home );
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
<?php wp_head(); ?>
</head>
<body <?php body_class('st-site'); ?>>
<?php wp_body_open(); ?>

<nav class="st-nav" id="st-nav">
  <a href="<?php echo esc_url($home); ?>" class="st-nav-brand">
    <div class="st-nav-logo-icon">🚖</div>
    <div class="st-nav-text">
      <span class="st-nav-title">A Superior Transportation</span>
      <span class="st-nav-sub">Houghton · Hancock · U.P.</span>
    </div>
  </a>
  <button class="st-nav-hamburger" id="st-hamburger" aria-label="Menu" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <div class="st-nav-links" id="st-nav-links">
    <a href="<?php echo esc_url($home);?>" <?php if(trailingslashit(home_url('/')) === $cur) echo 'class="st-active"';?>>Home</a>
    <a href="<?php echo esc_url($home);?>#booking">Book a Ride</a>
    <a href="<?php echo esc_url($gas);?>" <?php if(is_page('gas-tracker')) echo 'class="st-active"';?>>⛽ Gas Tracker</a>
    <a href="<?php echo esc_url($places);?>" <?php if(is_page('suggested-places')) echo 'class="st-active"';?>>📍 Places</a>
    <a href="<?php echo esc_url($driver);?>" <?php if(is_page('driver-portal')) echo 'class="st-active"';?>>Driver Login</a>
    <a href="tel:<?php echo preg_replace('/\D/','',$s['phone']);?>" class="st-nav-cta">📞 <?php echo esc_html($s['phone']);?></a>
  </div>
</nav>
