<?php
/*
	Custom Contact Forms Plugin
	By Taylor Lovett - http://www.taylorlovett.com
	Plugin URL: http://www.taylorlovett.com/wordpress-plugins
*/
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
		foreach ($GLOBALS[ccf_tables_array] as $table)
			$out .= $this->exportTable($table);
		if ($backup_options) {
			$out .= "\n\n### BEGIN WP Options Table Query\n\n" . $this->generateOptionsUpdateQuery();
			$out .= "\n\n### END WP Options Table Query\n";
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
				if (array_key_exists($k, $GLOBALS[ccf_fixed_fields])) {
					$no_insert = 1;
					break;
				}
				$v = str_replace(';', '\;', $v);
				$cols .= "$k, ";
				$vals .= "'$v', ";
			} if ($no_insert != 1) {
				$vals = substr($vals, 0, strlen($vals) - 2);
				$cols = substr($cols, 0, strlen($cols) - 2);
				$statement = 'INSERT INTO `' . $table . '` (' . $cols . ') VALUES (' . CustomContactFormsStatic::escapeSemiColons($vals) . ');';
				$statement = $statement . "\n\n";
				$out .= $statement;
			}
		}
		return $out;
	}
	
	function exportToFile($export_content = NULL) {
		if ($export_content == NULL) $export_content = $this->getLastExportContent();
		$export_file = "ccf-export-" . strtolower(date('j-M-Y--h-i-s')) . '.sql';
		if (($export_handle = @fopen($this->getExportsPath() . $export_file, 'w')) == false)
			return false;
		fwrite($export_handle, $export_content);
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
	
	function importFromFile($file) {
		$path = CCF_BASE_PATH. 'import/';
		$file_name = basename(time() . $file['name']);
		if (move_uploaded_file($file['tmp_name'], $path . $file_name)) {
			$data = file_get_contents($path . $file_name);
			$commands = $this->parseMultiQuery($data);
			$errors = 0;
			foreach($commands as $command) {
				if (!parent::query($command)) $errors++;
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
			$commands[$k] = $v . ')';
			if ($unescape_semicolons)
				$commands[$k] = CustomContactFormsStatic::unescapeSemiColons($commands[$k]);
			if ($replace_table_prefix)
				$commands[$k] = preg_replace('/^INSERT INTO `.+?customcontactforms_(.+?)`/is', 'INSERT INTO `' . $prefix . 'customcontactforms_$1`', $commands[$k]);
		}
		array_pop($commands);
		return $commands;
	}
	
	function generateOptionsUpdateQuery($option_name = NULL) {
		if ($option_name == NULL) $option_name = $this->option_name;
		$prefix = CustomContactFormsStatic::getWPTablePrefix();
		$options = serialize(get_option($option_name));
		return 'UPDATE `' . $prefix . "options` SET `option_value`='$options' WHERE `option_name`='$option_name';";
	}
}
?>