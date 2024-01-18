<?php
    session_regenerate_id();
    include __ROOT . '/Model/Database.php';

    class RouteManager {
        public $db;
        public $routes;

        public function __construct($routes) {
            $this->db = new Database(__DB_HOST, __DB_USER, __DB_PASSWORD, __DB_NAME);
            $this->routes = array('current' => null, 'available' => array());
            $_SESSION['hiddenKeys'] = array();

            if (isset($_REQUEST['key']) && strpos($_REQUEST['key'], '.') !== false) 
                $_SESSION['key'] = $_REQUEST['key'];

            foreach ($routes as $route => $file)
                $this->routes['available'][$route] = $file;
        }

        public function run() {
            if (!isset($this->routes['available'][$this->choppedURL()]))
                return $this->reject('Unknown Endpoint ' . $this->choppedURL(), 404);

            $this->routes['current'] = $this->db->query("SELECT * FROM API_endpoints WHERE endpoint = ? AND method = ?", $this->choppedURL(), $_SERVER['REQUEST_METHOD'])->fetchArray();

            if (!$this->routes['current'])
                return $this->reject('Error: Endpoint not in Database OR POST request needed', 500);

            if ($this->routes['current']['permission']) {
                $this->securityCheck();
                
                $routeKeys = $this->db->query("SELECT name, permission FROM API_data WHERE endpoint = ?", $this->choppedURL())->fetchAll();
                
                foreach ($routeKeys as $routeKey => &$routeValue) 
                    if (!($_SESSION['key']['data_permission'] & $routeValue['permission'])) 
                        array_push($_SESSION['hiddenKeys'], $routeValue['name']);
            }

            include_once __ROOT . '/' . $this->routes['available'][$this->choppedURL()];
            exit();
        }
        
        
        private function choppedURL() {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

            if ($path !== null) {
                $segments = array_filter(explode('/', trim($path, '/')), fn($s) => strpos($s, '=') === false);
                return count($segments) <= 2 ? '/' . implode('/', $segments) : '/';
            }
        
            return '/';
        }

        private function securityCheck() {
            if (!isset($_SESSION['key']))
                return $this->reject('Missing one or more fields [key]', 400);
            
            $splitKey = explode('.', $_SESSION['key']);
            $currentRoute = $this->routes['current'];

            $_SESSION['key'] = $this->db->query("SELECT * FROM API_keys WHERE identifier = ?", $splitKey[0])->fetchArray();

            if (!isset($_SESSION['key']) || !password_verify($splitKey[1], $_SESSION['key']['secret']))
                return $this->reject('Invalid Key Used', 403);

            $_SESSION['key']['secret'] = $splitKey[1];

            $keyPermission = $_SESSION['key']['endpoint_permission'];
            $currentPermission = $currentRoute['permission'];
                
            if (!($keyPermission & $currentPermission))
                return $this->reject('You cannot access this endpoint', 403);
                
            if ($_SESSION['key']['disabled'])
                return $this->reject('API Key Disabled', 403);

            $keyType = $_SESSION['key']['type'];
            $currentType = $currentRoute['type'];

            if (!((($keyType == 1 && $currentType == 1) || ($keyType == 0 && $currentType == 0)) || ($keyType == 2 || $currentType == 2)))
                return $this->reject('You cannot access this endpoint', 400);

            if ($keyType === 1 && !(isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'api.techtu.com.au') !== false))
                return $this->reject('Invalid Key Used', 403);
        }


        public function reject($reason, $code) {
            http_response_code($code);
            echo json_encode([
                'success' => false,
                'reason' => $reason
            ]);
            exit();
        }
        
        public function removeHiddenVariables($data) {
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $data[$key] = $this->removeHiddenVariables($value);
                    } elseif (in_array($key, $_SESSION['hiddenKeys'])) {
                        unset($data[$key]);
                    }
                }
            } elseif (is_object($data)) {
                foreach ($data as $key => $value) {
                    if (in_array($key, $_SESSION['hiddenKeys'])) {
                        unset($data->$key);
                    }
                }
            }
            return $data;
        }
    }
