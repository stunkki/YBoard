<?php
namespace YBoard\Model;

use YBoard\Abstracts\UserSubModel;
use YBoard\Library\Database;

class UserThreadHide extends UserSubModel
{
    protected $threads = [];

    public function add(int $threadId) : bool
    {
        $q = $this->db->prepare("INSERT IGNORE INTO user_thread_hide (user_id, thread_id) VALUES (:user_id, :thread_id)");
        $q->bindValue('user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue('thread_id', $threadId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function remove(int $threadId) : bool
    {
        $q = $this->db->prepare("DELETE FROM user_thread_hide WHERE user_id = :user_id AND thread_id = :thread_id");
        $q->bindValue('user_id', $this->userId, Database::PARAM_INT);
        $q->bindValue('thread_id', $threadId, Database::PARAM_INT);
        $q->execute();

        return true;
    }

    public function exists(int $threadId) : bool
    {
        return in_array($threadId, $this->threads);
    }

    public function getAll()
    {
        return $this->threads;
    }

    protected function load() : bool
    {
        $q = $this->db->prepare("SELECT thread_id FROM user_thread_hide WHERE user_id = :user_id");
        $q->bindValue('user_id', $this->userId, Database::PARAM_INT);
        $q->execute();

        $this->threads = $q->fetchAll(Database::FETCH_COLUMN);

        return true;
    }
}
