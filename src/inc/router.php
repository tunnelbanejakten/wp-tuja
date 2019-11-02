<?php

	namespace tuja;

use Exception;

class Router {
		static protected $controllers = array();
		
		protected $routes = array();
		protected $keys = array();
		
		
		public function __construct($routes = array()) {
			$this->routes = $routes;
		}
		
		
		public function route($input) {
			$input = trim($input, '/');

			// Try finding a route in the routes.php file that matches the url
			foreach($this->routes as $route => $controller) {
				$this->keys = array();
				
				$regex = preg_replace_callback('/\{([^\}]+)\}/', array($this, 'extract_args'), $route);
				$regex = '/^' . str_replace('/', '\/', $regex) . '$/';
				
				if(preg_match($regex, $input, $matches)) {
					if(!is_array($controller)) {
						throw new Exception('Invalid controller.');
					}

					if(isset($controller[0]) && file_exists(Plugin::PATH . '/frontend/' . $controller[0] . '.php')) {
						require_once Plugin::PATH . '/frontend/' . $controller[0] . '.php';
						$controller[0] = __NAMESPACE__ . '\\' . $controller[0];
					} else {
						return false;
					}
					
					ob_start();
					
					try {
						call_user_func($controller, (empty($this->keys) ? array() : array_combine($this->keys, array_slice($matches, 1))));
					}
					catch(Exception $e) {
						echo '<p class="error">' . $e->getMessage() . '</p>';
					}
					
					return ob_get_clean();
				}
			}


			// Try finding a URI that matches
			if(isset($_SERVER['REQUEST_METHOD']) && !empty($input)) {
				try {
					Helper::get_controller('Search_Controller')->search_uri($input);
					return true;
				} catch(Exception $e) {
					// Log::error('No URI found');
				}
			}
			
			return false;
		}
		
		
		protected function extract_args($matches = array()) {
			$this->keys[] = $matches[1];
			
			// The slash will be escaped later
			return '([^/]+)';
		}
	}
