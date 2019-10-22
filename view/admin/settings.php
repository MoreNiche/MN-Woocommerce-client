<div class="wrap">
	<h1><?php echo WC_AffiliateTracking_Admin::get_config( 'page_title' ) ?></h1>
	<form method="post" action="options.php">
		<?php
		settings_fields( WC_AffiliateTracking_Admin::get_config( 'option_name' ) );
		do_settings_sections( WC_AffiliateTracking_Admin::get_config( 'menu_slug' ) );
		submit_button();
		?>
	</form>
</div>