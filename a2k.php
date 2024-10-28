<?php
/*
Plugin Name: Attributron 2000
Plugin URI: 
Description: Easily add attribution to attachments and have them displayed on your posts.
Version: 1.0.0.2
Author: Decarbonated Web Services
Author URI: http://www.decarbonated.com/
License: GPL2
*/

if (!class_exists('DWS_Attributron_2000')) {
	class DWS_Attributron_2000 {
		var $slug = 'attributron-2000';
		var $name = 'Attributron 2000';
		var $access = 'manage_options';
		var $installdir;
		var $ver = "1.0";

		function __construct() {
			define('WP_DEBUG', true);
			$this->installdir = WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__),"",plugin_basename(__FILE__));

			add_action('admin_menu', array(&$this, 'action_admin_menu'));
			
			add_filter("the_content",array(&$this, 'filter_the_content'));
			add_filter("prepend_attachment",array(&$this, 'filter_prepend_attachment'));
			add_filter("attachment_fields_to_edit", array(&$this, 'filter_attachment_fields_to_edit'), null, 2);
			add_filter("attachment_fields_to_save", array(&$this, 'filter_attachment_fields_to_save'), null , 2);

			register_activation_hook(__FILE__, array(&$this, 'plugin_construct'));
			register_deactivation_hook(__FILE__, array(&$this, 'plugin_destruct'));
		}
		
		function action_admin_menu() {
			add_settings_section($this->slug, $this->name, array(&$this, 'select_image_text'), 'media');
			add_settings_field($this->slug . '-use-images-on-post', __('Use Images on Post Page',"dws_attributron-2000"), array(&$this, 'select_image_on_post'), 'media', $this->slug);
			add_settings_field($this->slug . '-use-images-on-attachment', __('Use Images on Attachment Page',"dws_attributron-2000"), array(&$this, 'select_image_on_attachment'), 'media', $this->slug);
			add_settings_field($this->slug . '-flickr-api-key', __('Flickr API Key',"dws_attributron-2000"), array(&$this, 'flickr_api_key'), 'media', $this->slug);
			// add_settings_field($this->slug . '-flickr-api-secret', __('Flickr API Secret',"dws_attributron-2000"), array(&$this, 'flickr_api_secret'), 'media', $this->slug);
			register_setting('media', 'dws_' . $this->slug);
		}
		
		function filter_prepend_attachment($content) {
			global $post;
			$options = get_option('dws_' . $this->slug);
			$return = "";

			$attachment_id = $post->ID;
			$meta = get_post_custom($attachment_id);
			if (get_post_meta($attachment_id,"_credit",true)) {
				if (get_post_meta($attachment_id,"_link",true)) {
					$author = get_post_meta($attachment_id,"_credit",true);
					$author = get_post_meta($attachment_id,"_credit",true);
					$link = get_post_meta($attachment_id,"_link",true);
					$author = "<a href='$link' title='". __("Link to","dws_attributron-2000") . " $author'>$author</a>";
				} else {
					$author = get_post_meta($attachment_id,"_credit",true);
				}
				$title = "<a href='" . get_permalink($attachment_id) . "'>" . get_the_title($attachment_id) . "</a>";
				$copyright = $this->license_slug_to_html(get_post_meta($attachment_id,"_copyright",true));
				if ($copyright) {
					$return .= "<p><span class='a2k-title'>$title</span> <span class='a2k-copyright'>$copyright</span> <span class='a2k-author'>$author</a>";
				} else {
					$return .= "<p><span class='a2k-title'>$title</span> <span class='a2k-copyright'>by</span> <span class='a2k-author'>$author</a>";
				}
				$content .= "<div class='a2k-container'><p class='a2k-sources'>". __("Source:","dws_attributron-2000") ."</p>$return</div>";
			}		
			return $content;
		}
		
		function filter_the_content($content) {
			global $post;
			$options = get_option('dws_' . $this->slug);
			$return = "";
			
			if (is_single() || is_page()) {
				$attachments = get_children("post_parent=$post->ID&post_type=attachment");

				if ($attachments) {
					foreach ($attachments as $attachment_id => $attachment) {
						$meta = get_post_custom($attachment_id);
						if (get_post_meta($attachment_id,"_credit",true)) {
							if (get_post_meta($attachment_id,"_link",true)) {
								$author = get_post_meta($attachment_id,"_credit",true);
								$author = get_post_meta($attachment_id,"_credit",true);
								$link = get_post_meta($attachment_id,"_link",true);
								$author = "<a href='$link' title='". __("Link to","dws_attributron-2000") . " $author'>$author</a>";
							} else {
								$author = get_post_meta($attachment_id,"_credit",true);
							}
							$title = "<a href='" . get_permalink($attachment_id) . "'>" . get_the_title($attachment_id) . "</a>";
							$copyright = $this->license_slug_to_html(get_post_meta($attachment_id,"_copyright",true));
							if ($copyright) {
								$return .= "<p><span class='a2k-title'>$title</span> <span class='a2k-copyright'>$copyright</span> <span class='a2k-author'>$author</a>";
							} else {
								$return .= "<p><span class='a2k-title'>$title</span> <span class='a2k-copyright'>by</span> <span class='a2k-author'>$author</a>";
							}
						}
					}
					$content .= "<div class='a2k-container'><p class='a2k-sources'>". __("Sources:","dws_attributron-2000") ."</p>$return</div>";
				}
			}
			return $content;
		}
		
		function license_slug_to_html($slug) {
			global $post;
			$options = get_option('dws_' . $this->slug);
			if (is_single() || is_page()) $use_images = $options["use-images-on-post"];
			if (is_attachment()) $use_images = $options["use-images-on-attachment"];

			switch ($slug) {
				case "c":
					$copyright = "&copy;";
					break;
				case "cc-by":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by/3.0/80x15.png" /></a>';
					}
					break;
				case "cc-by-sa":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by/3.0/">CC BY-SA</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-sa/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by-sa/3.0/80x15.png" /></a>';
					}
					break;
				case "cc-by-nc":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc/3.0/">CC BY-NC</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by-nc/3.0/80x15.png" /></a>';
					}
					break;
				case "cc-by-nd":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nd/3.0/">CC BY-ND</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nd/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by-nd/3.0/80x15.png" /></a>';
					}
					break;
				case "cc-by-nc-sa":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">CC BY-NC-SA</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/88x31.png" /></a>';
					}
					break;
				case "cc-by-nc-nd":
					$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/">CC BY-NC-ND</a>';
					if ($use_images) {
						$copyright = '<a rel="license" href="http://creativecommons.org/licenses/by-nc-nd/3.0/"><img alt="'. __("Creative Commons License","dws_attributron-2000"). '" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-nd/3.0/88x31.png" /></a>';
					}
					break;
				case "pd":
					$copyright = "<a rel='license' href='http://en.wikipedia.org/wiki/Public_domain'>". __("Public Domain","dws_attributron-2000") ."</a>";
					break;
				case "fu":
					$copyright = "<a rel='license' href='http://en.wikipedia.org/wiki/Fair_use'>". __("Fair Use","dws_attributron-2000") ."</a>";
					break;
			}
			return $copyright;
		}
		
		function select_image_text() {
			echo '<p>'. __("For more information about Flickr API keys, visit their <a href='http://www.flickr.com/services/api/misc.api_keys.html'>Flickr Services</a> page.</p>","dws_attributron-2000") ."</p>";
		}
		
		function select_image_on_post() {
			$options = get_option('dws_' . $this->slug);
			?>
				<input type="checkbox" <?php echo checked( 1, $options["use-images-on-post"], false ); ?> value="1" id="<?php echo $this->slug . '-use-images-on-post'; ?>" name="<?php echo 'dws_' . $this->slug; ?>[use-images-on-post]">
			<?php
		}
		
		function select_image_on_attachment() {
			$options = get_option('dws_' . $this->slug);
			?>
				<input type="checkbox" <?php echo checked( 1, $options["use-images-on-attachment"], false ); ?> value="1" id="<?php echo $this->slug . '-use-images-on-attachment'; ?>" name="<?php echo 'dws_' . $this->slug; ?>[use-images-on-attachment]">
			<?php
		}
		
		function flickr_api_key() {
			$options = get_option('dws_' . $this->slug);
			?>
				<input type="text" value="<?php echo $options["flickr-api-key"]; ?>" id="<?php echo $this->slug . '-flickr-api-key'; ?>" name="<?php echo 'dws_' . $this->slug; ?>[flickr-api-key]" />
			<?php
		}
		/*function flickr_api_secret() {
			$options = get_option('dws_' . $this->slug);
			?>
				<input type="text" value="<?php echo $options["flickr-api-secret"]; ?>" id="<?php echo $this->slug . '-flickr-api-secret'; ?>" name="<?php echo 'dws_' . $this->slug; ?>[flickr-api-secret]" />
			<?php
		}*/
		
		function filter_attachment_fields_to_edit($form_fields, $post) {
			$options = get_option('dws_' . $this->slug);
			// http://net.tutsplus.com/tutorials/wordpress/creating-custom-fields-for-attachments-in-wordpress/  Thanks!
			$form_fields["credit"] = array(
				"label" => __("Author"),
				"input" => "text",
				"value" => get_post_meta($post->ID, "_credit", true)
			);
			
			$form_fields["link"] = array(
				"label" => __("Author Link"),
				"input" => "text",
				"value" => get_post_meta($post->ID, "_link", true),
				"helps"	=> "If you have a Flickr API key entered into the <a href='options-media.php' target='_blank'>plugin settings</a>, you can paste the Flickr image URL here to auto-populate the fields."
			);

			$form_fields["copyright"] = array(
				"label" => __("Copyright"),
				"input" => "html",
				"html"	=> "<select name='attachments[{$post->ID}][copyright]' id='attachments[{$post->ID}][copyright]'> 
								<option value='u'>" . __("Unknown") . "</option> 
								<option value='c'>" . __("Standard Copyright") . "</option> 
								<option value='cc-by'>" . __("Creative Commons: Attribution") . "</option> 
								<option value='cc-by-sa'>" . __("Creative Commons: Attribution-ShareAlike") . "</option> 
								<option value='cc-by-nd'>" . __("Creative Commons: Attribution-NoDerivs") . "</option> 
								<option value='cc-by-nc'>" . __("Creative Commons: Attribution-NonCommercial") . "</option> 
								<option value='cc-by-nc-sa'>" . __("Creative Commons: Attribution-NonCommercial-ShareAlike") . "</option> 
								<option value='cc-by-nc-nd'>" . __("Creative Commons: Attribution-NonCommercial-NoDerivs") . "</option> 
								<option value='pd'>" . __("Public Domain") . "</option> 
								<option value='fu'>" . __("Fair Use") . "</option> 
							</select>
							<script type='text/javascript'>
								jQuery(function($) {
									var attach_link = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[link\\\\]\");
									var attach_author = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[credit\\\\]\");
									var attach_copyright = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[copyright\\\\]\");
									var attach_description = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[post_content\\\\]\");
									var attach_title = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[post_title\\\\]\");
									var attach_caption = $(\"#attachments\\\\[{$post->ID}\\\\]\\\\[post_excerpt\\\\]\");
									var flickr_API_key = '{$options["flickr-api-key"]}';
									
									attach_copyright.val('" . get_post_meta($post->ID,"_copyright",true) . "');
									
									attach_link.focusout(function() {
										if ($(this).val().indexOf('flickr.com') != -1) {
											var p = new RegExp('[0-9]+',[\"i\"]);
											var m = p.exec($(this).val());
											if (m != null) {
											  var flickrID = m[0];
											}
											$.get('https://secure.flickr.com/services/rest/', {
												method: 'flickr.photos.getInfo',
												api_key: flickr_API_key,
												photo_id: flickrID,
												format: 'json',
												nojsoncallback: 1
											},function(data){
												data = $.parseJSON(data);
												if (data.stat == 'ok') {
													$.get('https://secure.flickr.com/services/rest/', {
														method: 'flickr.people.getInfo',
														api_key: flickr_API_key,
														user_id: data.photo.owner.nsid,
														format: 'json',
														nojsoncallback: 1
													},function(userdata){
														userdata = $.parseJSON(userdata);
														if (userdata.stat == 'ok') {
															attach_link.val(userdata.person.profileurl._content);
														}
													});

													attach_author.val(data.photo.owner.username);
													attach_title.val(data.photo.title._content);
													attach_description.val(data.photo.description._content);
													switch (data.photo.license) {
														case '1':
															attach_copyright.val('cc-by-nc-sa');
															break;
														case '2':
															attach_copyright.val('cc-by-nc');
															break;
														case '3':
															attach_copyright.val('cc-by-nd');
															break;
														case '4':
															attach_copyright.val('cc-by');
															break;
														case '5':
															attach_copyright.val('cc-by-sa');
															break;
														case '6':
															attach_copyright.val('cc-by-nd');
															break;
														case '7':
															attach_copyright.val('pd');
															break;
														default:
															attach_copyright.val('u');
													}
												}
											});
										}
									});
								});
							</script>",
				"value" => get_post_meta($post->ID, "_copyright", true)
			);

			return $form_fields;
		}
		
		function filter_attachment_fields_to_save($post, $attachment) {
			if (isset($attachment['credit']) ){
				update_post_meta($post['ID'], '_credit', $attachment['credit']);
				update_post_meta($post['ID'], '_copyright', $attachment['copyright']);
				update_post_meta($post['ID'], '_link', $attachment['link']);
			}
			return $post;
		}

		function plugin_construct() {
			$options = array();
			$options['use-images-on-attachment'] = 'on';
			update_option('dws_' . $this->slug,$options);
		}
	
		function plugin_destruct() {
			delete_option('dws_' . $this->slug);
		}

	}
	$local_blogs = new DWS_Attributron_2000();
}
