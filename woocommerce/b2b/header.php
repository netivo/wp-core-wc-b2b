<header class="header header--shop js-header <?php echo is_front_page() ? 'header--home' : ''; ?> ">
	<?php get_template_part( "parts/header/desktop/top" ); ?>

    <div class="header__main">
        <div class="header__container">

			<?php get_template_part( "parts/header/desktop/logo" ); ?>

            <div class="navigation__right">
				<?php get_template_part( "parts/header/desktop/myaccount" ); ?>
				<?php get_template_part( "parts/header/desktop/widget" ); ?>
            </div>

        </div>
    </div>
</header>