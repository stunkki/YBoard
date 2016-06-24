<?php

namespace YBoard\Model;

use YBoard;
use YBoard\Library\Text;
use YBoard\Library\Database;

class User extends YBoard\Model
{

    public $id;
    public $sessionId;
    public $username;
    public $userClass;
    public $csrfToken;
    public $ip;
    public $isRegistered;

    public function load(int $userId)
    {
        $q = $this->db->prepare("SELECT * FROM user_accounts WHERE id = ? LIMIT 1");
        $q->execute([$userId]);

        if ($q->rowCount() == 0) {
            return false;
        }

        $user = $q->fetch();
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->userClass = $user['user_class'];
        $this->isRegistered = empty($user['password']) ? false : true;

        return true;
    }

    public function update($key, $val, $userId = false)
    {
        if (!$userId) {
            $userId = $this->id;
        }

        if (empty($userId)) {
            return false;
        }

        if (!is_array($key) && is_array($val) || is_array($key) && !is_array($val)) {
            return false;
        }
        if (!is_array($key) && !is_array($val)) {
            $key = [$key];
            $val = [$val];
        }

        if (count($key) != count($val) || count($key) == 0) {
            return false;
        }

        $bind = [];
        $update = '';
        $i = 0;
        foreach ($key AS $curKey) {
            if ($curKey == 'lastIp') {
                $update .= 'last_ip = :lastIp,';
                $bind[$curKey] = inet_pton($val[$i]);
            } else {
                continue;
            }

            $this->$curKey = $val[$i];
            ++$i;
        }

        if (empty($update)) {
            return false;
        }

        // Remove last comma
        $update = substr($update, 0, -1);

        $q = $this->db->prepare("UPDATE user_accounts SET " . $update . " WHERE id = :userId LIMIT 1");
        $q->bindValue('userId', (int)$userId);
        foreach ($bind as $key => $val) {
            $q->bindValue($key, $val);
        }

        $q->execute();
        return $q !== false;
    }

    public function create()
    {
        $this->username = Text::randomStr();

        $q = $this->db->prepare("INSERT INTO user_accounts (username) VALUES (:username)");
        $q->bindParam('username', $this->username);
        $q->execute();

        if (!$q) {
            return false;
        }

        $this->id = $this->db->lastInsertId();

        return true;
    }

    public function delete($userId)
    {
        $q = $this->db->prepare("DELETE FROM user_accounts WHERE id = :userId LIMIT 1");
        $q->bindParam('userId', $userId);
        $q->execute();
        // Relations will handle the deletion of rest of the data, so we don't have to care.
        // Thank you relations!

        return $q !== false;
    }

    public function createSession($userId)
    {
        $sessionId = random_bytes(32);
        $csrfToken = random_bytes(32);

        $q = $this->db->prepare("INSERT INTO user_sessions (user_id, session_id, csrf_token, ip) VALUES (:userId, :sessionId, :csrfToken, INET6_ATON(:ip))");
        $q->bindValue('userId', (int)$userId);
        $q->bindValue('sessionId', $sessionId);
        $q->bindParam('csrfToken', $csrfToken);
        $q->bindValue('ip', $_SERVER['REMOTE_ADDR']);
        $q->execute();

        if ($q === false) {
            throw new \Exception('Cannot create session for user');
        }

        $this->sessionId = $sessionId;
        $this->csrfToken = bin2hex($csrfToken);
        return true;
    }

    public function loadSession($userId, $sessionId)
    {
        $q = $this->db->prepare("SELECT user_id, session_id, csrf_token FROM user_sessions WHERE user_id = :userId AND session_id = :sessionId LIMIT 1");
        $q->bindValue('userId', (int)$userId);
        $q->bindValue('sessionId', $sessionId);
        $q->execute();

        // Invalid session
        if ($q->rowCount() != 1) {
            return false;
        }

        // Update last active -timestamp and IP-address
        $session = $q->fetch();
        $q = $this->db->prepare("UPDATE user_sessions SET last_active = NOW(), ip = INET6_ATON(:ip) WHERE user_id = :userId AND session_id = :sessionId LIMIT 1");
        $q->bindValue('userId', (int)$session['user_id']);
        $q->bindValue('sessionId', $session['session_id']);
        $q->bindValue('ip', $_SERVER['REMOTE_ADDR']);
        $q->execute();

        $this->csrfToken = bin2hex($session['csrf_token']);
        return true;
    }

    public function destroyCurrentSession() {
        return $this->destroySession($this->id, $this->sessionId);
    }

    public function destroySession($userId, $sessionId)
    {
        $q = $this->db->prepare("DELETE FROM user_sessions WHERE user_id = :userId AND session_id = :sessionId LIMIT 1");
        $q->bindValue('userId', (int)$userId);
        $q->bindValue('sessionId', $sessionId);
        $q->execute();

        if ($q === false) {
            throw new \Exception('Cannot destroy user session');
        }

        return true;
    }
}
