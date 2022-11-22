<?php
    session_regenerate_id();
    include(__ROOT . '/src/models/Database.php');

    class RouteManager {
        public $DB;
        private $routes = array();
        private $currentRoute;
        private $identifier;
        private $key;

        public function __construct() {
            $this->DB = new Database(__DB_HOST, __DB_USER, __DB_PASSWORD, __DB_NAME);
            $this->identifier = isset($_GET['key']) ? explode('.', $_GET['key'])[0] : null;
            $this->key = isset($_GET['key']) ? explode('.', $_GET['key'])[1] : null;
        }

        public function addRoute($endpoint, $file) {
            $this->routes[$endpoint] = $file;
        }

        public function run() {
            foreach ($this->routes as $parent => $value) {
                $urlsSnipped = $this->snipRequest($parent);

                if (!array_key_exists('/'. join('/', $urlsSnipped[1]), $this->routes))
                    $this->rejectRequest('Unknown Endpoint', 404);

                if (join('/', $urlsSnipped[0]) !== join('/', $urlsSnipped[1]))
                    continue;

                $this->currentRoute = $this->DB->query("SELECT * FROM API_endpoints WHERE url = ?", '/' . join('/', $urlsSnipped[1]))->fetchArray();

                if (!count($this->currentRoute))
                    $this->rejectRequest('Error: Endpoint not in Database', 500);

                if ($this->currentRoute['permission'])
                    $this->isKeyValid();

                include_once(__ROOT . '/' . $value);

                if (isset($_GET['r']))
                    header('Location: ' . $_GET['r']);

                exit();
            }
        }

        private function snipRequest($endpoint): array {
            $request = strtok(rtrim(filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL), '/'), '?');

            $parts = explode('/', $endpoint);
            $url_parts = explode('/', $request);
            array_shift($parts);
            array_shift($url_parts);

            return array($parts, $url_parts);
        }

        private function isKeyValid() {
            if ($this->key === null)
                $this->rejectRequest('Missing one or more fields [key]', 400);

            $_SESSION['KEY'] = $this->DB->query("SELECT * FROM API_keys WHERE identifier = ?", $this->identifier)->fetchArray();

            if (!count($_SESSION['KEY']) || !password_verify($this->key, $_SESSION['KEY']['key']))
                $this->rejectRequest('Invalid API Key', 403);

            if (!($_SESSION['KEY']['permission'] & $this->currentRoute['permission']))
                $this->rejectRequest('You cannot access this endpoint', 403);

            if ($_SESSION['KEY']['disabled'])
                $this->rejectRequest('Your key has been disabled, please contact an Admin', 403);

            $this->DB->query("UPDATE API_keys SET requests = requests + 1 WHERE identifier = ?", $this->identifier);
        }

        public function rejectRequest($reason, $error) {
            http_response_code($error);
            echo json_encode(array(
                'success' => false,
                'reason' => $reason,
                'error' => $error
            ));
            exit();
        }
    }