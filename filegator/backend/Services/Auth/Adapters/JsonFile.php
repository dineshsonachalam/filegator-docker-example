<?php

/*
 * This file is part of the FileGator package.
 *
 * (c) Milos Stojanovic <alcalbg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE file
 */

namespace Filegator\Services\Auth\Adapters;

use Filegator\Services\Auth\AuthInterface;
use Filegator\Services\Auth\User;
use Filegator\Services\Auth\UsersCollection;
use Filegator\Services\Service;
use Filegator\Services\Session\SessionStorageInterface as Session;
use Filegator\Utils\PasswordHash;

class JsonFile implements Service, AuthInterface
{
    use PasswordHash;

    const SESSION_KEY = 'json_auth';

    const GUEST_USERNAME = 'guest';

    protected $session;

    protected $file;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function init(array $config = [])
    {
        if (! file_exists($config['file'])) {
            copy($config['file'].'.blank', $config['file']);
        }

        $this->file = $config['file'];
    }

    public function user(): ?User
    {
        return $this->session ? $this->session->get(self::SESSION_KEY, null) : null;
    }

    public function authenticate($username, $password): bool
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "http://api:5000/user/validate/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS =>"{\n\t\"username\":\"{$username}\",\n\t\"password\":\"${password}\"\n}",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json"
        ),
        ));
        $user_validation_response = json_decode(curl_exec($curl), true);
        curl_close($curl);
        if ($user_validation_response['validation'] ==  "success"){
            $user_data = array(
                "username"    => "{$username}",
                "name"        => "{$username}",
                "role"        => "user",
                "homedir"     => "/",
                "permissions" => "{$user_validation_response['permissions']}",

            );
            $user = $this->mapToUserObject($user_data);
            $this->store($user);
            return true;
        }
        return false;
    }

    public function forget()
    {
        return $this->session->invalidate();
    }

    public function store(User $user)
    {
        return $this->session->set(self::SESSION_KEY, $user);
    }

    public function update($username, User $user, $password = ''): User
    {
        $all_users = $this->getUsers();

        if ($username != $user->getUsername() && $this->find($user->getUsername())) {
            throw new \Exception('Username already taken');
        }

        foreach ($all_users as &$u) {
            if ($u['username'] == $username) {
                $u['username'] = $user->getUsername();
                $u['name'] = $user->getName();
                $u['role'] = $user->getRole();
                $u['homedir'] = $user->getHomeDir();
                $u['permissions'] = $user->getPermissions(true);

                if ($password) {
                    $u['password'] = $this->hashPassword($password);
                }

                $this->saveUsers($all_users);

                return $this->find($user->getUsername()) ?: $user;
            }
        }

        throw new \Exception('User not found');
    }

    public function add(User $user, $password): User
    {
        if ($this->find($user->getUsername())) {
            throw new \Exception('Username already taken');
        }

        $all_users = $this->getUsers();

        $all_users[] = [
            'username' => $user->getUsername(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
            'homedir' => $user->getHomeDir(),
            'permissions' => $user->getPermissions(true),
            'password' => $this->hashPassword($password),
        ];

        $this->saveUsers($all_users);

        return $this->find($user->getUsername()) ?: $user;
    }

    public function delete(User $user)
    {
        $all_users = $this->getUsers();

        foreach ($all_users as $key => $u) {
            if ($u['username'] == $user->getUsername()) {
                unset($all_users[$key]);
                $this->saveUsers($all_users);

                return true;
            }
        }

        throw new \Exception('User not found');
    }

    public function find($username): ?User
    {
        foreach ($this->getUsers() as $user) {
            if ($user['username'] == $username) {
                return $this->mapToUserObject($user);
            }
        }

        return null;
    }

    public function getGuest(): User
    {
        $guest = $this->find(self::GUEST_USERNAME);

        if (! $guest || ! $guest->isGuest()) {
            throw new \Exception('No guest account');
        }

        return $guest;
    }

    public function allUsers(): UsersCollection
    {
        $users = new UsersCollection();

        foreach ($this->getUsers() as $user) {
            $users->addUser($this->mapToUserObject($user));
        }

        return $users;
    }

    protected function mapToUserObject(array $user): User
    {
        $new = new User();

        $new->setUsername($user['username']);
        $new->setName($user['name']);
        $new->setRole($user['role']);
        $new->setHomedir($user['homedir']);
        $new->setPermissions($user['permissions'], true);

        return $new;
    }

    protected function getUsers(): array
    {
        $users = json_decode((string) file_get_contents($this->file), true);

        return is_array($users) ? $users : [];
    }

    protected function saveUsers(array $users)
    {
        return file_put_contents($this->file, json_encode($users), LOCK_EX);
    }
}
