<?php

namespace Cheryl;

class User extends \Cheryl\User_Base {

	public static function users() {
		return Cheryl::me()->config['users'];
	}

	public function __construct($u) {
		if (is_string($u)) {
			$type = strtolower(Cheryl::me()->config['authentication']['type']);

			if ($type == 'simple') {
				foreach (self::users() as $user) {
					if ($user['username'] == $u) {
						$u = $user;
						break;
					}
				}

			} elseif ($type == 'pdo' && class_exists('PDO')) {

				$q = Cheryl::me()->config->authentication->pdo->prepare('SELECT * FROM '.Cheryl::me()->config['authentication']['permission_table'].' WHERE user=:username');
				$q->bindValue(':username', $u, PDO::PARAM_STR);
				$q->execute();
				$rows = $q->fetchAll(PDO::FETCH_ASSOC);

				$u = array(
					'user' => $u,
					'permissions' => $rows
				);
			} elseif ($type == 'mysql' && function_exists('mysql_connect')) {
				// @todo #18
			}
		}

		parent::__construct($u);
	}

	public static function login() {
		if (Cheryl::me()->tipsy()->request()->request()['__username']) {
			// log in attempt
			$pass = Cheryl::me()->tipsy()->request()->request()['__password'];
		} else {
			return false;
		}

		$type = strtolower(Cheryl::me()->config['authentication']['type']);

		// simple authentication. store users in an array
		if ($type == 'simple') {
			foreach (self::users() as $user) {

				if ($user['username'] == Cheryl::me()->tipsy()->request()->request()['__username']) {
					$u = $user;
					break;
				}
			}

			if ($u && (!$u['password'] || password_verify($pass, $u['password']))) {
				// successfuly send username and password
				return new User($u);
			}

			return false;

		// use php data objects only if we have the libs
		} elseif ($type == 'pdo' && class_exists('PDO')) {

			$q = Cheryl::me()->config->authentication->pdo->prepare('SELECT * FROM '.Cheryl::me()->config->authentication->user_table.' WHERE user=:username AND hash=:hash LIMIT 1');
			$q->bindValue(':username', Cheryl::tipsy()->request()->request()['__username'], PDO::PARAM_STR);
			$q->bindValue(':hash', $pass, PDO::PARAM_STR);
			$q->execute();
			$rows = $q->fetchAll(PDO::FETCH_ASSOC);

			if (count($rows)) {
				return new User($rows[0]);
			}

		// use php data objects only if we have the libs
		} elseif ($type == 'mysql' && function_exists('mysql_connect')) {
			// @todo #18
		} else {
			return false;
		}
	}
}


class User_Base {
	public function __construct($u = null) {
		if (is_array($u)) {
			foreach ($u as $key => $value) {
				$this->{$key} = $value;
			}
		} elseif(is_object($u)) {
			foreach (get_object_vars($u) as $key => $value) {
				$this->{$key} = $value;
			}
		} elseif (is_string($u)) {

		}
	}

	public static function users() {
		return array();
	}

	public static function login() {
		return false;
	}

	public function permission($permission) {
		if ($this->permissions == 'all' || is_array($this->permissions) && $this->permissions[$perimssions] || is_object($this->permissions) && $this->permissions->{$perimssions}) {
			return true;
		} else {
			return false;
		}
	}
}
