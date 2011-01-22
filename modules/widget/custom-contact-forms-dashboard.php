<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsDashboard')) {
	class CustomContactFormsDashboard extends CustomContactFormsAdmin {
		function install() {
			wp_add_dashboard_widget('custom-contact-forms-dashboard', __('Custom Contact Forms - Saved Form Submissions', 'custom-contact-forms'), array(&$this, 'display'));	
		}
		
		function isDashboardPage() {
			return (is_admin() && preg_match('/index\.php$/', $_SERVER['REQUEST_URI']));
		}
		
		function insertDashboardStyles() {
			wp_register_style('CCFDashboard', plugins_url() . '/custom-contact-forms/css/custom-contact-forms-dashboard.css');
            wp_enqueue_style('CCFDashboard');
		}
		
		function insertDashboardScripts() {
			wp_register_script('CCFDashboardJS', plugins_url() . '/custom-contact-forms/js/custom-contact-forms-dashboard.js');
            wp_enqueue_script('CCFDashboardJS');
		}
		
		function display() {
			ccf_utils::load_module('export/custom-contact-forms-user-data.php');
			$user_data_array = parent::selectAllUserData();
			?>
			<table id="ccf-dashboard" cellpadding="0" cellspacing="0">
			  <tbody>
			<?php
			$i = 0;
			foreach ($user_data_array as $data_object) {
				if ($i > 3) break;
				$data = new CustomContactFormsUserData(array('form_id' => $data_object->data_formid, 'data_time' => $data_object->data_time, 'form_page' => $data_object->data_formpage, 'encoded_data' => $data_object->data_value));	
				?>
				<tr class="<?php if ($i % 2 == 1) echo 'even'; ?>">
					<td class="date"><?php echo date('m/d/y', $data->getDataTime()); ?></td>
					<td class="slug">
					<?php
					if ($data->getFormID() > 0) {
						$data_form = parent::selectForm($data->getFormID());
						$this_form = $data_form->form_slug;
					} else
						$this_form = __('Custom HTML Form', 'custom-contact-forms');
					echo $this_form;
					?>
					</td>
					<td class="form-page"><?php echo $data->getFormPage(); ?></td>
					<td>
						<input class="ccf-view-submission" type="button" value="<?php _e('View', 'custom-contact-forms'); ?>" />
						<div class="view-submission-popover">
							<div class="close">&times;</div>
							<div class="top">
								<div class="left"><?php _e('CCF Saved Form Submission', 'custom-contact-forms'); ?></div>
								<div class="right"><p><span><?php echo date('F j, Y, g:i a', $data->getDataTime()); ?></span></p><p><?php _e('Form Submitted:', 'custom-contact-forms'); ?> <span><?php echo $this_form; ?></span></p></div>
							</div>
							<div class="separate"></div>
							<ul>
								<?php
								$data_array = $data->getDataArray();
								foreach ($data_array as $item_key => $item_value) {
								?>
								<li>
								  <div><?php echo $item_key; ?></div>
								  <p><?php echo $item_value; ?></p>
								</li>
								<?php
								}
								?>
							</ul>
							<div class="separate"></div>
							<a href="admin.php?page=ccf-saved-form-submissions"><?php _e('View All Submissions', 'custom-contact-forms'); ?></a>
						</div>
					</td>
				</tr>
				<?php
				$i++;
			}
			?>
			  </tbody>
			</table>
			<a href="admin.php?page=ccf-saved-form-submissions"><?php _e('View All Submissions', 'custom-contact-forms'); ?></a>
			<?php
		}
	}
}
?>