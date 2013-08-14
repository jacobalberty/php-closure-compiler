<?php

/**
 * Copyright tureki.org [tureki11@gmail.com]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * PHP Closure Compiler
 * A PHP Library to use Google Closure Compiler compress Javascript
 *
 * @copyright tureki.org
 * @author tureki
 * 
 **/
class phpcc {
	
	public function __construct($options) 
	{

		$this->options = array(
			'java_file'    => 'java',
			'jar_file'     => 'compiler.jar', 
			'output_path'  => '/',
			'optimization' => 'SIMPLE_OPTIMIZATIONS',
			'charset'      => 'utf-8'
		);

		$this->options = array_merge($this->options,$options);
		
		$this->reset();

	}
	
	/**
	 * Add javascript file to compiler list
	 * 
	 * @param string $file 
	 * @return class phpcc 
	 */
	public function add($file)
	{

		if(is_array($file)){

			$this->js_files = array_merge($this->js_files,$file);

		}else{

			$this->js_files[] = $file;

		}

		return $this;

	}

	/**
	 * Execute compiler.
	 * 
	 * @param string $filename 
	 * @return class phpcc
	 */
	public function exec($filename = 'all.min.js') 
	{
		
		$str_file = '';

		if($this->bol_single){

			$ary_result = array();
			
			$num_js     = count($this->js_files);

			for ($i=0; $i < $num_js; $i++) { 
		
				$str_file     = ' --js ' . $this->js_files[$i];
				
				$filename     = basename($this->js_files[$i]);
				
				$ary_result[] = $this->_get_argv($str_file,$filename);

			}

			$num_js = count($this->js_files_dir);

			for ($i=0; $i < $num_js; $i++) { 
		
				$str_file     = ' --js ' . $this->js_files_dir[$i];
				echo $this->js_files_dir[$i] ."|" . $this->js_dir . "\n";
				$filename     = str_replace($this->js_dir, '', $this->js_files_dir[$i]);
				echo $filename."\n";
				$ary_result[] = $this->_get_argv($str_file,$filename);

			}


			return $ary_result;

		}else{

			if(count($this->js_files_dir)>0){

				$this->js_files = array_merge($this->js_files,$this->js_files_dir);
				
			}

			$this->js_files = array_unique($this->js_files);
			
			sort($this->js_files);
			
			$num_js         = count($this->js_files);

			for ($i=0; $i < $num_js; $i++) { 
		
				$str_file .= ' --js ' . $this->js_files[$i];

			}	

			return $this->_get_argv($str_file,$filename);

		}

	}

	/**
	 * Help method will return "Closure Compiler --help" when setting success 
	 * 
	 * @return array 
	 */
	public function help()
	{

		$str_cmd = $this->_get_cmd() . ' --help';

		return $this->_exec($str_cmd);

	}

	/**
	 * Compress all js to one file.
	 * 
	 * @return class phpcc
	 */
	public function merge()
	{

		$this->bol_single = false;

		return $this;

	}

	/**
	 * Add command param. exp:--angular_pass
	 *
	 * @param string $param
	 * @param string $value 
	 * @return class phpcc
	 */
	public function param($param,$value=null)
	{

		if($value){

			$str_param = $param ." ". $value;	

		}else{

			$str_param = $param;

		}

		$this->shell_params[] = $str_param;

		return $this;

	}

	/**
	 * Reset all setting.
	 *
	 * @return class phpcc
	 */
	public function reset()
	{

		$this->bol_single   = false;
		
		$this->js_dir       = '';
		
		$this->js_files     = array();
		
		$this->js_files_dir = array();
		
		$this->shell_params = array();

		return $this;

	}

	/**
	 * Set directory you want to compiler
	 *
	 * @param string $path 
	 * @param array $ext 
	 * @return class phpcc
	 */
	public function setDir($path,$ext=array('js'))
	{

		$this->js_dir = $path;

		$cls_objects = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path) 
		);

		foreach($cls_objects as $str_name){

			$str_filetype = pathinfo($str_name, PATHINFO_EXTENSION);

			if (in_array(strtolower($str_filetype), $ext)) {

				$this->js_files_dir[] = $str_name;

			}
		    
		}

		return $this;
		
	}

	/**
	 * Do not merge javascript files
	 *
	 * @return class phpcc
	 */
	public function single()
	{

		$this->bol_single = true;

		return $this;

	}
	
	private function _create($file_path)
	{

		if(!file_exists($file_path)){

			$pathinfo = pathinfo($file_path);

			$path = $pathinfo["dirname"];

			try{
				if(!file_exists($path) || !is_writeable($path)){
					mkdir($path, 0777,true);
					touch($file_path);
				}
				return true;
			}catch(Exception $e){
				return false;
			}

		}

	}

	private function _exec($str_cmd)
	{

		exec($str_cmd. ' 2>&1', $out, $status);

		return array(
			'shell'   => $str_cmd,
			'out'     => $out,
			'status' => $status
		);

	}

	private function _get_argv($str_file,$filename)
	{

		$opt        = $this->_get_options();
		
		$str_output = $opt["output_path"].$filename;
		
		$str_param  = implode(" ", $this->shell_params);
		
		$str_cmd    = $this->_get_cmd();
		
		$str_cmd    .= ' '. $str_param.' '. $str_file;

		$this->_create($str_output);

		if(!empty($str_file)){
			$str_cmd .=
			' --compilation_level '.$opt['optimization'].
			' --charset '.$opt['charset'].
			' --js_output_file '.$str_output;
		}

		return $this->_exec($str_cmd);

	}

	private function _get_cmd()
	{

		$opt = $this->_get_options();

		$str_cmd = $opt['java_file'] . ' -jar ' . $opt['jar_file'];

		return $str_cmd;

	}

	private function _get_options()
	{

		return $this->options;

	}
	
}
?>