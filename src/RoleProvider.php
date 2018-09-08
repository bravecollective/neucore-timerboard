<?php

namespace Brave\TimerBoard;

use Brave\NeucoreApi\Api\ApplicationApi;
use Brave\Sso\Basics\SessionHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tkhamez\Slim\RoleAuth\RoleProviderInterface;

/**
 * Provides groups from Brave Core from an authenticated user.
 */
class RoleProvider implements RoleProviderInterface
{
    /**
     * This role is always added.
     */
    const ROLE_ANY = 'role:any';

    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @var SessionHandlerInterface
     */
    private $session;

    /**
     * @param ApplicationApi $api
     * @param SessionHandlerInterface $session
     */
    public function __construct(ApplicationApi $api, SessionHandlerInterface $session)
    {
        $this->api = $api;
        $this->session = $session;
    }

    /**
     * @param ServerRequestInterface $request
     * @return string[]
     */
    public function getRoles(ServerRequestInterface $request = null)
    {
        $roles = [self::ROLE_ANY];

        /* @var $eveAuth \Brave\Sso\Basics\EveAuthentication */
        $eveAuth = $this->session->get('eveAuth', null);
        if ($eveAuth === null) {
            return $roles;
        }

        // try cache
        $coreGroups = $this->session->get('coreGroups', null);
        if (is_array($coreGroups) && $coreGroups['time'] > (time() - 60*60)) {
            return $coreGroups['roles'];
        }

        // get groups from Core
        try {
            $groups = $this->api->groupsV1($eveAuth->getCharacterId());
        } catch (\Exception $e) {
            error_log((string)$e);
            return $roles;
        }
        foreach ($groups as $group) {
            $roles[] = $group->getName();
        }

        // cache roles
        $this->session->set('coreGroups', [
            'time' => time(),
            'roles' => $roles
        ]);

        return $roles;
    }

    public function clear()
    {
        $this->session->set('coreGroups', null);
    }
}
