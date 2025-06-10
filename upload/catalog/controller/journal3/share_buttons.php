<?php

class ControllerJournal3ShareButtons extends Controller {

	public function index($args) {
		return
			'
			<!-- AddToAny BEGIN -->
			<div class="a2a_kit a2a_kit_size_32 a2a_default_style">
				<a class="a2a_dd" href="https://www.addtoany.com/share"></a>
				<a class="a2a_button_facebook"></a>
				<a class="a2a_button_x"></a>
				<a class="a2a_button_whatsapp"></a>
				<a class="a2a_button_email"></a>
				<style>
				.a2a_kit a {
						transform: scale(.85); 
						filter: grayscale(100%);
						opacity: .7; 
				  }
				.a2a_kit .a2a_s_x{
						background: #757575 !important;
				}
				</style>
			</div>
			<script>
			var a2a_config = a2a_config || {};
			a2a_config.onclick = 1;
			</script>
			<script async="" src="https://static.addtoany.com/menu/page.js"></script>
			<!-- AddToAny END -->
		'
			;
	}

}

class_alias('ControllerJournal3ShareButtons', '\Opencart\Catalog\Controller\Journal3\ShareButtons');
