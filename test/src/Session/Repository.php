<?php

namespace ActiveCollab\Authentication\Test\Session;

use ActiveCollab\Authentication\AuthenticatedUser\AuthenticatedUserInterface;
use ActiveCollab\Authentication\Session\RepositoryInterface;
use ActiveCollab\Authentication\Session\SessionInterface;

/**
 * @package ActiveCollab\Authentication\Test\Session
 */
class Repository implements RepositoryInterface
{
    /**
     * @var SessionInterface[]
     */
    private $sessions;

    /**
     * @param array $sessions
     */
    public function __construct(array $sessions = [])
    {
        $this->sessions = $sessions;
    }

    /**
     * Find session by session ID
     *
     * @param  string                $session_id
     * @return SessionInterface|null
     */
    public function getById($session_id)
    {
        return isset($this->sessions[$session_id]) ? $this->sessions[$session_id] : null;
    }

    /**
     * @var array
     */
    private $used_session = [];

    /**
     * {@inheritdoc}
     */
    public function getUsageById($session_id)
    {
        return empty($this->used_session[$session_id]) ? 0 : $this->used_session[$session_id];
    }

    /**
     * {@inheritdoc}
     */
    public function recordSessionUsage($session_or_session_id)
    {
        $session_id = $session_or_session_id instanceof SessionInterface ? $session_or_session_id->getSessionId() : $session_or_session_id;

        if (empty($this->used_session[$session_id])) {
            $this->used_session[$session_id] = 0;
        }

        $this->used_session[$session_id]++;
    }

    /**
     * Create a new session
     *
     * @param  AuthenticatedUserInterface $user
     * @param  \DateTimeInterface|null    $expires_at
     * @return SessionInterface
     */
    public function createSession(AuthenticatedUserInterface $user, \DateTimeInterface $expires_at = null)
    {
        $session_id = isset($this->sessions[$user->getEmail()]) ? $this->sessions[$user->getEmail()] : sha1(time());

        return new Session($session_id, $user->getEmail(), $expires_at);
    }
}