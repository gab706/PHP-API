<?php

    class Database {
        private PDO $connection;
        private ?PDOStatement $query = null;
        private bool $show_errors = true;

        public function __construct(
            string $host = 'localhost',
            string $user = 'root',
            string $password = '',
            string $name = '',
            string $charset = 'utf8'
        ) {
            try {
                $this->connection = new PDO("mysql:host=$host;dbname=$name;charset=$charset", $user, $password);
                $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                $this->error($e);
            }
        }

        public function query(string $query, ...$args): self {
            try {
                $this->close();
                $this->query = $this->connection->prepare($query);

                if ($args) {
                    $types = '';
                    foreach ($args as $arg) {
                        $types .= $this->_gettype($arg);
                    }
                    $this->bindParams($types, ...$args);
                }

                $this->query->execute();
            } catch (PDOException $e) {
                $this->error($e);
            }
            return $this;
        }

        public function fetchAll(?callable $callback = null): array {
            $result = [];
            if ($this->query !== null) {
                $this->query->execute();
                while ($row = $this->query->fetch(PDO::FETCH_ASSOC)) {
                    if ($callback !== null && is_callable($callback)) {
                        $value = call_user_func($callback, $row);
                        if ($value === 'break')
                            break;
                    } else
                        $result[] = $row;
                }
                $this->close();
            }
            return $result;
        }

        public function fetchArray(): array {
            $result = [];
            if ($this->query !== null) {
                $this->query->execute();
                $columns = $this->query->fetch(PDO::FETCH_ASSOC);
                if ($columns) {
                    do
                        $result[] = $columns;
                    while ($columns = $this->query->fetch(PDO::FETCH_ASSOC));
                }
                $this->close();
            }
            return $result;
        }

        public function getRowCount(): int {
            if ($this->query !== null)
                return $this->query->rowCount();
            return 0;
        }

        public function lastInsertID() {
            return $this->connection->lastInsertId();
        }

        private function _gettype($var): string {
            return is_string($var) ? 's' : (is_float($var) ? 'd' : (is_int($var) ? 'i' : 'b'));
        }

        private function bindParams(string $types, ...$args): void {
            $this->query->bindParam(1, $args[0], $types);
            for ($i = 1; $i < count($args); $i++)
                $this->query->bindParam($i + 1, $args[$i], $types);
        }

        private function error(Exception $error): void {
            if ($this->show_errors)
                echo "Unhandled Exception: " . $error->getMessage();
        }

        public function getConn(): PDO {
            return $this->connection;
        }

        public function close(): bool {
            if ($this->query !== null)
                $this->query->closeCursor();
            return true;
        }
    }