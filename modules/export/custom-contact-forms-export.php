<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
if (!class_exists('CustomContactFormsExport')) {
	class CustomContactFormsExport extends CustomContactFormsDB {
		
		var $last_export_content;
		var $last_export_file;
		var $exports_path;
		var $option_name;
		
		function CustomContactFormsExport($option_name) {
			$this->exports_path = CCF_BASE_PATH . 'export/';
			$this->option_name = $option_name;
		}
		
		function exportAll($backup_options = true) {
			$out = '';
			foreach ($GLOBALS['ccf_tables_array'] as $table)
				$out .= $this->exportTable($table);
			if ($backup_options) {
				$out .= "\n" . $this->generateOptionsUpdateQuery() . "\n";
			}
			$this->last_export_content = $out;
			return $out;
		}
		
		function exportTable($table) {
			$out = '';
			$data = parent::selectAllFromTable($table, ARRAY_A);
			foreach ($data as $row) {
				$no_insert = 0;
				$cols = '';
				$vals = '';
				foreach ($row as $k => $v) {
					$v = str_replace(';', '\;', $v);
					$cols .= "$k, ";
					$vals .= "'$v', ";
				}
				$vals = substr($vals, 0, strlen($vals) - 2);
				$cols = substr($cols, 0, strlen($cols) - 2);
				$statement = 'INSERT INTO `' . $table . '` (' . $cols . ') VALUES (' . $this->escapeSemiColons($vals) . ');';
				$statement = $statement . "\n\n";
				$out .= $statement;
			}
			return $out;
		}
		
		function exportToFile($export_content = NULL) {
			if ($export_content == NULL) $export_content = $this->getLastExportContent();
			$export_file = "ccf-export-" . strtolower(date('j-M-Y--h-i-s')) . '.sql';
			if (($export_handle = @fopen($this->getExportsPath() . $export_file, 'w')) == false)
				return false;
			$comment = '## ' . __('Custom Contact Forms Export File', 'custom-contact-forms') . "\n";
			$comment .= '## '. __('It is recommended that you do not edit this file. The order of the', 'custom-contact-forms') . "\n";
			$comment .= '## ' . __('queries is important if you intend to use this file through the CCF', 'custom-contact-forms') . "\n";
			$comment .= '## ' . __('exporter. The query to update general settings MUST be the last query', 'custom-contact-forms') . "\n";
			$comment .= '## ' . __('in this file.', 'custom-contact-forms') . "\n\n";
			fwrite($export_handle, $comment . $export_content);
			fclose($export_handle);
			$this->last_export_file = $export_file;
			return $export_file;
		}
		
		function getExportsPath() {
			return $this->exports_path;
		}
		
		function getLastExportContent() {
			return $this->last_export_content;
		}
		
		function importFromFile($file, $settings = array('mode' => 'clear_import', 'import_general_settings' => false, 'import_forms' => true,'import_fields' => true, 'import_field_options' => true, 'import_styles' => true, 'import_saved_submissions' => false)) {
			$path = CCF_BASE_PATH. 'import/';
			$file_name = basename(time() . $file['name']);
			if (move_uploaded_file($file['tmp_name'], $path . $file_name)) {
				$data = file_get_contents($path . $file_name);
				$data = preg_replace('/^#.*?[\n\r]*$/ims', '', $data);
				$commands = $this->parseMultiQuery($data);
				$errors = 0;
				if ($settings['mode'] == 'clear_import') parent::emptyAllTables();
				foreach($commands as $command) {
					if (preg_match('/^[\s]*UPDATE/is', $command)) {
						if ($settings['import_general_settings']) 
							if (!parent::query($command)) $errors++;
					} elseif (preg_match('/^[\s]*INSERT INTO/is', $command)) {
						$table_name = $this->extractTableFromQuery($command);
						$no_query = 0;
						if ($settings['import_forms'] == 0) if ($table_name == CCF_FORMS_TABLE) $no_query = 1;
						if ($settings['import_fields'] == 0) if ($table_name == CCF_FIELDS_TABLE) $no_query = 1;
						if ($settings['import_field_options'] == 0) if ($table_name == CCF_FIELD_OPTIONS_TABLE) $no_query = 1;
						if ($settings['import_styles'] == 0) if ($table_name == CCF_STYLES_TABLE) $no_query = 1;
						if ($settings['import_saved_submissions'] == 0) if ($table_name == CCF_USER_DATA_TABLE) $no_query = 1;
						if ($no_query == 0)
							if (!parent::query($command)) $errors++;
					}
				}
				return ($errors == 0) ? true : $errors;
			}
			return false;
		}
		
		function parseMultiQuery($sql, $unescape_semicolons = true, $replace_table_prefix = true) {
			if (empty($sql)) return false;
			$prefix = CustomContactFormsStatic::getWPTablePrefix();
			$commands = preg_split('/\);[\n\r]*/ims', $sql);
			foreach ($commands as $k => $v) {
				if (preg_match('/^[\s]*INSERT INTO/is', $v)) $commands[$k] = $v . ')';
				if ($unescape_semicolons)
					$commands[$k] = $this->unescapeSemiColons($commands[$k]);
				if ($replace_table_prefix)
					$commands[$k] = preg_replace('/^([a-zA-Z0-9 \s]+?)`.+?customcontactforms_(.+?)`/is', '$1 `' . $prefix . 'customcontactforms_$2`', $commands[$k]);
			}
			return $commands;
		}
		
		function generateOptionsUpdateQuery($option_name = NULL) {
			if ($option_name == NULL) $option_name = $this->option_name;
			$prefix = CustomContactFormsStatic::getWPTablePrefix();
			$options = serialize(get_option($option_name));
			return 'UPDATE `' . $prefix . "options` SET `option_value`='$options' WHERE `option_name`='$option_name';";
		}
		
		function extractTableFromQuery($query) {
			return preg_replace('/^[\s]*?INSERT INTO[\s]*?`(.*?)`.*/is', '$1', $query);
		}
		
		function escapeSemiColons($value) {
			return str_replace(';', '\;', $value);
		}
		
		function unescapeSemiColons($value) {
			return str_replace('\;', ';', $value);
		}
	}
}
?>